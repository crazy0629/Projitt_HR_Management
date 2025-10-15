<?php

namespace App\Http\Controllers\PerformanceReview;

use App\Http\Controllers\Controller;
use App\Models\PerformanceReview\PerformanceAction;
use App\Models\PerformanceReview\PerformanceReview;
use App\Models\PerformanceReview\PerformanceReviewFeedback;
use App\Models\PerformanceReview\PerformanceReviewScore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PerformanceReviewController extends Controller
{
    /**
     * Display a listing of performance reviews.
     */
    public function index(Request $request)
    {
        try {
            $query = PerformanceReview::with(['cycle', 'employee', 'scores.reviewer']);

            // Filter by cycle
            if ($request->has('cycle_id') && $request->cycle_id) {
                $query->where('cycle_id', $request->cycle_id);
            }

            // Filter by status
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // Filter by department
            if ($request->has('department') && $request->department) {
                $query->where('department_name', $request->department);
            }

            // Filter by employee
            if ($request->has('employee_id') && $request->employee_id) {
                $query->where('employee_id', $request->employee_id);
            }

            // Filter by potential status
            if ($request->has('potential_status') && $request->potential_status) {
                $query->where('potential_status', $request->potential_status);
            }

            // Search by employee name
            if ($request->has('search') && $request->search) {
                $query->where('employee_name', 'like', '%'.$request->search.'%');
            }

            // Sort options
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $reviews = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'status' => 'success',
                'data' => $reviews,
                'message' => 'Performance reviews retrieved successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving performance reviews: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve performance reviews',
            ], 500);
        }
    }

    /**
     * Display the specified performance review.
     */
    public function show($id)
    {
        try {
            $review = PerformanceReview::with([
                'cycle',
                'employee',
                'scores.reviewer',
                'feedback',
                'actions.targetRole',
                'actions.mentor',
                'actions.learningPath',
            ])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $review,
                'message' => 'Performance review retrieved successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving performance review: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Performance review not found',
            ], 404);
        }
    }

    /**
     * Submit a review score by a reviewer.
     */
    public function submitScore(Request $request, $reviewId)
    {
        try {
            $review = PerformanceReview::findOrFail($reviewId);
            $reviewerId = Auth::id();

            // Find the score record for this reviewer
            $scoreRecord = $review->scores()
                ->where('reviewer_id', $reviewerId)
                ->first();

            if (! $scoreRecord) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not assigned to review this employee',
                ], 403);
            }

            if ($scoreRecord->status === 'completed') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You have already completed this review',
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'scores' => 'required|array|min:1',
                'scores.*' => 'numeric|min:1|max:5',
                'comments' => 'nullable|string|max:2000',
                'strengths' => 'nullable|string|max:1000',
                'opportunities' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            // Update the score record
            $scoreRecord->update([
                'scores' => $request->scores,
                'comments' => $request->comments,
                'strengths' => $request->strengths,
                'opportunities' => $request->opportunities,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // If this was pending, mark as started if not already
            if (! $scoreRecord->started_at) {
                $scoreRecord->update(['started_at' => now()]);
            }

            // Update review progress
            $review->updateProgress();

            DB::commit();

            Log::info('Performance review score submitted', [
                'review_id' => $review->id,
                'reviewer_id' => $reviewerId,
                'score_type' => $scoreRecord->type,
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $scoreRecord->fresh(),
                'message' => 'Review score submitted successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error submitting review score: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit review score',
            ], 500);
        }
    }

    /**
     * Generate AI feedback for a completed review.
     */
    public function generateAIFeedback($reviewId)
    {
        try {
            $review = PerformanceReview::with(['scores' => function ($query) {
                $query->where('status', 'completed');
            }])->findOrFail($reviewId);

            if ($review->status !== 'completed') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'AI feedback can only be generated for completed reviews',
                ], 422);
            }

            // Check if feedback already exists
            $existingFeedback = $review->feedback;
            if ($existingFeedback) {
                return response()->json([
                    'status' => 'success',
                    'data' => $existingFeedback,
                    'message' => 'AI feedback already exists',
                ]);
            }

            DB::beginTransaction();

            // Create feedback record
            $feedback = PerformanceReviewFeedback::create([
                'review_id' => $review->id,
                'generated_by' => Auth::id(),
            ]);

            // Generate the AI summary
            $feedback->generateAISummary();

            DB::commit();

            Log::info('AI feedback generated', [
                'review_id' => $review->id,
                'generated_by' => Auth::id(),
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $feedback,
                'message' => 'AI feedback generated successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error generating AI feedback: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate AI feedback',
            ], 500);
        }
    }

    /**
     * Add manager summary to review feedback.
     */
    public function addManagerSummary(Request $request, $reviewId)
    {
        try {
            $review = PerformanceReview::findOrFail($reviewId);

            $validator = Validator::make($request->all(), [
                'manager_summary' => 'required|string|max:2000',
                'development_recommendations' => 'nullable|string|max:1000',
                'potential_status' => 'nullable|in:developing,solid,ready,high_potential',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            // Get or create feedback record
            $feedback = $review->feedback;
            if (! $feedback) {
                $feedback = PerformanceReviewFeedback::create([
                    'review_id' => $review->id,
                    'generated_by' => Auth::id(),
                ]);
            }

            // Update feedback with manager summary
            $feedback->addManagerSummary($request->manager_summary, Auth::id());

            if ($request->has('development_recommendations')) {
                $feedback->addDevelopmentRecommendations($request->development_recommendations);
            }

            // Update review potential status
            if ($request->has('potential_status')) {
                $review->update(['potential_status' => $request->potential_status]);
            }

            DB::commit();

            Log::info('Manager summary added', [
                'review_id' => $review->id,
                'manager_id' => Auth::id(),
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $feedback->fresh(),
                'message' => 'Manager summary added successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adding manager summary: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add manager summary',
            ], 500);
        }
    }

    /**
     * Create performance actions based on review results.
     */
    public function createActions(Request $request, $reviewId)
    {
        try {
            $review = PerformanceReview::findOrFail($reviewId);

            $validator = Validator::make($request->all(), [
                'actions' => 'required|array|min:1',
                'actions.*.action_type' => 'required|in:promotion,succession_pool,career_path,assign_mentor,learning_path,improvement_plan,role_change,salary_adjustment',
                'actions.*.title' => 'required|string|max:255',
                'actions.*.description' => 'nullable|string|max:1000',
                'actions.*.priority' => 'required|in:low,medium,high,urgent',
                'actions.*.target_date' => 'nullable|date|after:today',
                'actions.*.assigned_to' => 'nullable|integer|exists:users,id',
                'actions.*.target_role_id' => 'nullable|integer|exists:roles,id',
                'actions.*.mentor_id' => 'nullable|integer|exists:users,id',
                'actions.*.learning_path_id' => 'nullable|integer|exists:learning_paths,id',
                'actions.*.metadata' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            $createdActions = [];

            foreach ($request->actions as $actionData) {
                $action = PerformanceAction::create([
                    'review_id' => $review->id,
                    'action_type' => $actionData['action_type'],
                    'title' => $actionData['title'],
                    'description' => $actionData['description'] ?? null,
                    'priority' => $actionData['priority'],
                    'target_date' => $actionData['target_date'] ?? null,
                    'assigned_to' => $actionData['assigned_to'] ?? null,
                    'target_role_id' => $actionData['target_role_id'] ?? null,
                    'mentor_id' => $actionData['mentor_id'] ?? null,
                    'learning_path_id' => $actionData['learning_path_id'] ?? null,
                    'metadata' => $actionData['metadata'] ?? null,
                    'created_by' => Auth::id(),
                    'status' => 'pending',
                ]);

                $createdActions[] = $action;
            }

            DB::commit();

            Log::info('Performance actions created', [
                'review_id' => $review->id,
                'action_count' => count($createdActions),
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $createdActions,
                'message' => count($createdActions).' performance actions created successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating performance actions: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create performance actions',
            ], 500);
        }
    }

    /**
     * Get reviews assigned to the current user as a reviewer.
     */
    public function myAssignedReviews(Request $request)
    {
        try {
            $reviewerId = Auth::id();

            $query = PerformanceReviewScore::with([
                'review.cycle',
                'review.employee',
            ])->where('reviewer_id', $reviewerId);

            // Filter by status
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // Filter by review type
            if ($request->has('type') && $request->type) {
                $query->where('type', $request->type);
            }

            // Sort by due date or creation date
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            if ($sortBy === 'due_date') {
                $query->join('performance_reviews', 'performance_review_scores.review_id', '=', 'performance_reviews.id')
                    ->orderBy('performance_reviews.due_date', $sortOrder)
                    ->select('performance_review_scores.*');
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }

            $assignments = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'status' => 'success',
                'data' => $assignments,
                'message' => 'Assigned reviews retrieved successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving assigned reviews: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve assigned reviews',
            ], 500);
        }
    }

    /**
     * Get performance analytics and reports.
     */
    public function analytics(Request $request)
    {
        try {
            $query = PerformanceReview::query();

            // Filter by cycle if specified
            if ($request->has('cycle_id') && $request->cycle_id) {
                $query->where('cycle_id', $request->cycle_id);
            }

            // Filter by date range
            if ($request->has('start_date') && $request->start_date) {
                $query->whereHas('cycle', function ($q) use ($request) {
                    $q->where('period_start', '>=', $request->start_date);
                });
            }

            if ($request->has('end_date') && $request->end_date) {
                $query->whereHas('cycle', function ($q) use ($request) {
                    $q->where('period_end', '<=', $request->end_date);
                });
            }

            // Overall statistics
            $totalReviews = $query->count();
            $completedReviews = $query->where('status', 'completed')->count();
            $avgScore = $query->where('status', 'completed')->avg('final_score');

            // Score distribution
            $scoreDistribution = [
                'excellent' => $query->where('final_score', '>=', 4.5)->count(),
                'good' => $query->whereBetween('final_score', [3.5, 4.4])->count(),
                'satisfactory' => $query->whereBetween('final_score', [2.5, 3.4])->count(),
                'needs_improvement' => $query->where('final_score', '<', 2.5)->count(),
            ];

            // Potential status distribution
            $potentialDistribution = [
                'high_potential' => $query->where('potential_status', 'high_potential')->count(),
                'ready' => $query->where('potential_status', 'ready')->count(),
                'solid' => $query->where('potential_status', 'solid')->count(),
                'developing' => $query->where('potential_status', 'developing')->count(),
                'not_assessed' => $query->whereNull('potential_status')->count(),
            ];

            // Department performance
            $departmentStats = $query->where('status', 'completed')
                ->selectRaw('department_name, COUNT(*) as count, AVG(final_score) as avg_score')
                ->groupBy('department_name')
                ->orderBy('avg_score', 'desc')
                ->get();

            // Top performers
            $topPerformers = $query->where('status', 'completed')
                ->where('final_score', '>=', 4.0)
                ->orderBy('final_score', 'desc')
                ->limit(10)
                ->get(['employee_name', 'department_name', 'final_score', 'potential_status']);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'overview' => [
                        'total_reviews' => $totalReviews,
                        'completed_reviews' => $completedReviews,
                        'completion_rate' => $totalReviews > 0 ? round(($completedReviews / $totalReviews) * 100, 1) : 0,
                        'average_score' => $avgScore ? round($avgScore, 2) : null,
                    ],
                    'score_distribution' => $scoreDistribution,
                    'potential_distribution' => $potentialDistribution,
                    'department_performance' => $departmentStats,
                    'top_performers' => $topPerformers,
                ],
                'message' => 'Performance analytics retrieved successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving performance analytics: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve performance analytics',
            ], 500);
        }
    }

    /**
     * Get succession planning candidates.
     */
    public function successionCandidates(Request $request)
    {
        try {
            $query = PerformanceReview::with(['employee', 'actions'])
                ->where('status', 'completed')
                ->where('final_score', '>=', 3.5);

            // Filter by potential status
            if ($request->has('potential_status') && $request->potential_status) {
                $query->where('potential_status', $request->potential_status);
            } else {
                // Default to high potential and ready for growth
                $query->whereIn('potential_status', ['high_potential', 'ready']);
            }

            // Filter by department
            if ($request->has('department') && $request->department) {
                $query->where('department_name', $request->department);
            }

            // Filter by minimum score
            if ($request->has('min_score') && $request->min_score) {
                $query->where('final_score', '>=', $request->min_score);
            }

            $candidates = $query->orderBy('final_score', 'desc')
                ->paginate($request->get('per_page', 20));

            return response()->json([
                'status' => 'success',
                'data' => $candidates,
                'message' => 'Succession candidates retrieved successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving succession candidates: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve succession candidates',
            ], 500);
        }
    }
}
