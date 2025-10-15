<?php

namespace App\Http\Controllers\PerformanceReview;

use App\Http\Controllers\Controller;
use App\Models\PerformanceReview\PerformanceReview;
use App\Models\PerformanceReview\PerformanceReviewCycle;
use App\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PerformanceCycleController extends Controller
{
    /**
     * Display a listing of performance review cycles.
     */
    public function index(Request $request)
    {
        try {
            $query = PerformanceReviewCycle::with(['creator', 'reviews']);

            // Filter by status
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // Search by name
            if ($request->has('search') && $request->search) {
                $query->where('name', 'like', '%'.$request->search.'%');
            }

            // Sort options
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $cycles = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'status' => 'success',
                'data' => $cycles,
                'message' => 'Performance review cycles retrieved successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving performance cycles: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve performance cycles',
            ], 500);
        }
    }

    /**
     * Store a newly created performance review cycle.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:performance_review_cycles,name',
                'description' => 'nullable|string',
                'period_start' => 'required|date',
                'period_end' => 'required|date|after:period_start',
                'frequency' => 'required|in:quarterly,semi_annual,annual',
                'competencies' => 'required|array|min:1',
                'competencies.*' => 'string|max:255',
                'assignments' => 'required|array|min:1',
                'assignments.*' => 'in:self_review,manager_review,peer_review,direct_report',
                'employee_ids' => 'nullable|array',
                'employee_ids.*' => 'integer|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            $cycle = PerformanceReviewCycle::create([
                'name' => $request->name,
                'description' => $request->description,
                'period_start' => $request->period_start,
                'period_end' => $request->period_end,
                'frequency' => $request->frequency,
                'competencies' => $request->competencies,
                'assignments' => $request->assignments,
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            // Create performance reviews for selected employees
            if ($request->has('employee_ids') && ! empty($request->employee_ids)) {
                $this->createReviewsForEmployees($cycle, $request->employee_ids);
            }

            $cycle->updateCompletionStats();

            DB::commit();

            Log::info('Performance review cycle created', [
                'cycle_id' => $cycle->id,
                'cycle_name' => $cycle->name,
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $cycle->load(['creator', 'reviews']),
                'message' => 'Performance review cycle created successfully',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating performance cycle: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create performance cycle',
            ], 500);
        }
    }

    /**
     * Display the specified performance review cycle.
     */
    public function show($id)
    {
        try {
            $cycle = PerformanceReviewCycle::with([
                'creator',
                'reviews.employee',
                'reviews.scores.reviewer',
            ])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $cycle,
                'message' => 'Performance review cycle retrieved successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving performance cycle: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Performance cycle not found',
            ], 404);
        }
    }

    /**
     * Update the specified performance review cycle.
     */
    public function update(Request $request, $id)
    {
        try {
            $cycle = PerformanceReviewCycle::findOrFail($id);

            // Only allow updates to draft cycles
            if ($cycle->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only draft cycles can be updated',
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255|unique:performance_review_cycles,name,'.$id,
                'description' => 'nullable|string',
                'period_start' => 'sometimes|date',
                'period_end' => 'sometimes|date|after:period_start',
                'frequency' => 'sometimes|in:quarterly,semi_annual,annual',
                'competencies' => 'sometimes|array|min:1',
                'competencies.*' => 'string|max:255',
                'assignments' => 'sometimes|array|min:1',
                'assignments.*' => 'in:self_review,manager_review,peer_review,direct_report',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $cycle->update($request->only([
                'name', 'description', 'period_start', 'period_end',
                'frequency', 'competencies', 'assignments',
            ]));

            Log::info('Performance review cycle updated', [
                'cycle_id' => $cycle->id,
                'updated_by' => Auth::id(),
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $cycle->load(['creator', 'reviews']),
                'message' => 'Performance review cycle updated successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating performance cycle: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update performance cycle',
            ], 500);
        }
    }

    /**
     * Activate a performance review cycle.
     */
    public function activate($id)
    {
        try {
            $cycle = PerformanceReviewCycle::findOrFail($id);

            if ($cycle->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only draft cycles can be activated',
                ], 422);
            }

            if ($cycle->reviews()->count() === 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot activate cycle without any assigned reviews',
                ], 422);
            }

            DB::beginTransaction();

            $cycle->update(['status' => 'active']);

            // Create scoring assignments for each review
            foreach ($cycle->reviews as $review) {
                $this->createScoringAssignments($review, $cycle->assignments);
            }

            DB::commit();

            Log::info('Performance review cycle activated', [
                'cycle_id' => $cycle->id,
                'activated_by' => Auth::id(),
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $cycle->load(['creator', 'reviews']),
                'message' => 'Performance review cycle activated successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error activating performance cycle: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to activate performance cycle',
            ], 500);
        }
    }

    /**
     * Complete a performance review cycle.
     */
    public function complete($id)
    {
        try {
            $cycle = PerformanceReviewCycle::findOrFail($id);

            if ($cycle->status !== 'active') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only active cycles can be completed',
                ], 422);
            }

            $completedReviews = $cycle->reviews()->where('status', 'completed')->count();
            $totalReviews = $cycle->reviews()->count();

            if ($completedReviews < $totalReviews) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Cannot complete cycle. {$completedReviews}/{$totalReviews} reviews completed.",
                    'data' => [
                        'completed_reviews' => $completedReviews,
                        'total_reviews' => $totalReviews,
                        'completion_rate' => $totalReviews > 0 ? round(($completedReviews / $totalReviews) * 100, 1) : 0,
                    ],
                ], 422);
            }

            $cycle->update(['status' => 'completed']);
            $cycle->updateCompletionStats();

            Log::info('Performance review cycle completed', [
                'cycle_id' => $cycle->id,
                'completed_by' => Auth::id(),
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $cycle->load(['creator', 'reviews']),
                'message' => 'Performance review cycle completed successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error completing performance cycle: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to complete performance cycle',
            ], 500);
        }
    }

    /**
     * Add employees to a performance review cycle.
     */
    public function addEmployees(Request $request, $id)
    {
        try {
            $cycle = PerformanceReviewCycle::findOrFail($id);

            if ($cycle->status === 'completed') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot add employees to completed cycle',
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'employee_ids' => 'required|array|min:1',
                'employee_ids.*' => 'integer|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            $newReviews = $this->createReviewsForEmployees($cycle, $request->employee_ids);

            // If cycle is active, create scoring assignments
            if ($cycle->status === 'active') {
                foreach ($newReviews as $review) {
                    $this->createScoringAssignments($review, $cycle->assignments);
                }
            }

            $cycle->updateCompletionStats();

            DB::commit();

            Log::info('Employees added to performance cycle', [
                'cycle_id' => $cycle->id,
                'employee_count' => count($request->employee_ids),
                'added_by' => Auth::id(),
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $cycle->load(['creator', 'reviews']),
                'message' => count($request->employee_ids).' employees added to cycle successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adding employees to performance cycle: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add employees to cycle',
            ], 500);
        }
    }

    /**
     * Get cycle statistics and analytics.
     */
    public function analytics($id)
    {
        try {
            $cycle = PerformanceReviewCycle::with(['reviews.scores'])->findOrFail($id);

            $totalReviews = $cycle->reviews()->count();
            $completedReviews = $cycle->reviews()->where('status', 'completed')->count();
            $pendingReviews = $cycle->reviews()->where('status', 'pending')->count();
            $inProgressReviews = $cycle->reviews()->where('status', 'in_progress')->count();
            $overdueReviews = $cycle->reviews()->where('due_date', '<', now())
                ->whereIn('status', ['pending', 'in_progress'])->count();

            $avgScore = $cycle->reviews()->where('status', 'completed')
                ->whereNotNull('final_score')->avg('final_score');

            // Score distribution
            $scoreRanges = [
                '4.5-5.0' => $cycle->reviews()->where('final_score', '>=', 4.5)->count(),
                '3.5-4.4' => $cycle->reviews()->whereBetween('final_score', [3.5, 4.4])->count(),
                '2.5-3.4' => $cycle->reviews()->whereBetween('final_score', [2.5, 3.4])->count(),
                '1.0-2.4' => $cycle->reviews()->whereBetween('final_score', [1.0, 2.4])->count(),
            ];

            // Department breakdown
            $departmentStats = $cycle->reviews()
                ->selectRaw('department_name, COUNT(*) as total, 
                           SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                           AVG(CASE WHEN status = "completed" THEN final_score END) as avg_score')
                ->groupBy('department_name')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'cycle_info' => [
                        'id' => $cycle->id,
                        'name' => $cycle->name,
                        'status' => $cycle->status,
                        'period' => $cycle->formatted_period,
                        'duration_days' => $cycle->duration_in_days,
                    ],
                    'completion_stats' => [
                        'total_reviews' => $totalReviews,
                        'completed_reviews' => $completedReviews,
                        'pending_reviews' => $pendingReviews,
                        'in_progress_reviews' => $inProgressReviews,
                        'overdue_reviews' => $overdueReviews,
                        'completion_rate' => $totalReviews > 0 ? round(($completedReviews / $totalReviews) * 100, 1) : 0,
                    ],
                    'performance_stats' => [
                        'average_score' => $avgScore ? round($avgScore, 2) : null,
                        'score_distribution' => $scoreRanges,
                    ],
                    'department_breakdown' => $departmentStats,
                ],
                'message' => 'Cycle analytics retrieved successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving cycle analytics: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve cycle analytics',
            ], 500);
        }
    }

    /**
     * Create performance reviews for specified employees.
     */
    private function createReviewsForEmployees($cycle, $employeeIds)
    {
        $newReviews = [];
        $existingEmployeeIds = $cycle->reviews()->pluck('employee_id')->toArray();

        foreach ($employeeIds as $employeeId) {
            // Skip if employee already has a review in this cycle
            if (in_array($employeeId, $existingEmployeeIds)) {
                continue;
            }

            $employee = User::find($employeeId);
            if (! $employee) {
                continue;
            }

            $review = PerformanceReview::create([
                'cycle_id' => $cycle->id,
                'employee_id' => $employeeId,
                'employee_name' => $employee->name,
                'employee_email' => $employee->email,
                'department_name' => $employee->department ?? 'Unknown',
                'status' => 'pending',
                'progress' => 0,
                'total_reviewers' => count($cycle->assignments),
                'completed_reviewers' => 0,
                'due_date' => $cycle->period_end,
            ]);

            $newReviews[] = $review;
        }

        return $newReviews;
    }

    /**
     * Create scoring assignments for a review based on cycle assignments.
     */
    private function createScoringAssignments($review, $assignments)
    {
        foreach ($assignments as $assignmentType) {
            $reviewerId = $this->getReviewerId($review->employee_id, $assignmentType);

            if ($reviewerId) {
                $reviewer = User::find($reviewerId);

                $review->scores()->create([
                    'reviewer_id' => $reviewerId,
                    'reviewer_name' => $reviewer->name,
                    'type' => $assignmentType,
                    'status' => 'pending',
                ]);
            }
        }

        $review->updateProgress();
    }

    /**
     * Get reviewer ID based on assignment type.
     */
    private function getReviewerId($employeeId, $assignmentType)
    {
        switch ($assignmentType) {
            case 'self_review':
                return $employeeId;
            case 'manager_review':
                // In a real system, you'd query the manager relationship
                // For now, return the first admin user
                return User::where('role', 'admin')->first()?->id;
            case 'peer_review':
                // In a real system, you'd select peers from the same department
                // For now, return a random colleague
                return User::where('id', '!=', $employeeId)->inRandomOrder()->first()?->id;
            case 'direct_report':
                // In a real system, you'd query direct reports
                // For now, return null
                return null;
            default:
                return null;
        }
    }

    /**
     * Remove the specified performance review cycle.
     */
    public function destroy($id)
    {
        try {
            $cycle = PerformanceReviewCycle::findOrFail($id);

            if ($cycle->status === 'active') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete active cycles',
                ], 422);
            }

            $cycle->delete();

            Log::info('Performance review cycle deleted', [
                'cycle_id' => $cycle->id,
                'deleted_by' => Auth::id(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Performance review cycle deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting performance cycle: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete performance cycle',
            ], 500);
        }
    }
}
