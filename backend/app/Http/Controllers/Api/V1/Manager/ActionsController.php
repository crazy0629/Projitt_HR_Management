<?php

namespace App\Http\Controllers\Api\V1\Manager;

use App\Http\Controllers\Controller;
use App\Models\LearningPath\LearningPath;
use App\Models\ManagerReviews\PromotionRecommendation;
use App\Models\ManagerReviews\TeamMember;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ActionsController extends Controller
{
    /**
     * Create a promotion recommendation
     */
    public function createPromotion(Request $request): JsonResponse
    {
        $request->validate([
            'employee_user_id' => 'required|integer|exists:users,id',
            'cycle_id' => 'sometimes|integer|exists:performance_review_cycles,id',
            'target_role_id' => 'required|integer|exists:roles,id',
            'justification' => 'required|string|min:10|max:1000',
            'comp_adjustment_min' => 'sometimes|numeric|min:0',
            'comp_adjustment_max' => 'sometimes|numeric|min:0|gte:comp_adjustment_min',
            'workflow_id' => 'sometimes|string|max:255',
        ]);

        $managerId = Auth::id();
        $employeeId = $request->get('employee_user_id');

        // Verify employee is in manager's team
        $teamMember = TeamMember::findByEmployee($employeeId);
        if (! $teamMember || ! $this->canManagerAccessEmployee($managerId, $employeeId)) {
            return response()->json([
                'error' => 'Employee not found in your team',
                'code' => '403-SCOPE',
            ], 403);
        }

        // Check for existing pending recommendation
        $existingRecommendation = PromotionRecommendation::forEmployee($employeeId)
            ->pending()
            ->first();

        if ($existingRecommendation) {
            return response()->json([
                'error' => 'Employee already has a pending promotion recommendation',
                'code' => '409-DUPLICATE',
            ], 409);
        }

        try {
            $promotion = DB::transaction(function () use ($request, $managerId, $employeeId) {
                // Get employee's current role
                $employee = \App\Models\User::with('currentRole')->find($employeeId);
                $currentRoleId = $employee->currentRole?->id;

                return PromotionRecommendation::createRecommendation(
                    employeeId: $employeeId,
                    proposedById: $managerId,
                    targetRoleId: $request->get('target_role_id'),
                    justification: $request->get('justification'),
                    cycleId: $request->get('cycle_id'),
                    currentRoleId: $currentRoleId,
                    compMin: $request->get('comp_adjustment_min'),
                    compMax: $request->get('comp_adjustment_max'),
                    workflowId: $request->get('workflow_id'),
                    metadata: [
                        'created_by_manager' => true,
                        'source' => 'manager_recommendation',
                    ]
                );
            });

            return response()->json([
                'message' => 'Promotion recommendation created successfully',
                'promotion' => $promotion->load(['employee', 'targetRole', 'currentRole']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create promotion recommendation',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get manager's promotion recommendations
     */
    public function getPromotions(Request $request): JsonResponse
    {
        $managerId = Auth::id();
        $status = $request->get('status');
        $limit = min($request->get('limit', 25), 100);
        $cursor = $request->get('cursor');

        $query = PromotionRecommendation::proposedBy($managerId)
            ->with(['employee', 'targetRole', 'currentRole', 'reviewCycle', 'approvedBy']);

        if ($status) {
            $query->byStatus($status);
        }

        if ($cursor) {
            $query->where('id', '<', $cursor);
        }

        $promotions = $query->orderBy('id', 'desc')
            ->limit($limit + 1)
            ->get();

        $hasMore = $promotions->count() > $limit;
        if ($hasMore) {
            $promotions->pop();
        }

        // Transform the data
        $promotions->transform(function ($promotion) {
            return [
                'id' => $promotion->id,
                'employee' => [
                    'id' => $promotion->employee->id,
                    'name' => $promotion->employee->name,
                    'email' => $promotion->employee->email,
                ],
                'current_role' => $promotion->currentRole?->name,
                'target_role' => $promotion->targetRole->name,
                'justification' => $promotion->justification,
                'compensation_range' => $promotion->getCompensationAdjustmentRange(),
                'status' => $promotion->status,
                'status_color' => $promotion->getStatusColor(),
                'status_icon' => $promotion->getStatusIcon(),
                'promotion_level' => $promotion->getPromotionLevel(),
                'cycle' => $promotion->reviewCycle?->name,
                'created_at' => $promotion->created_at,
                'approved_at' => $promotion->approved_at,
                'approved_by' => $promotion->approvedBy?->name,
                'approval_notes' => $promotion->approval_notes,
                'can_withdraw' => $promotion->canWithdraw(),
                'time_to_decision' => $promotion->getTimeToDecision(),
            ];
        });

        return response()->json([
            'promotions' => $promotions,
            'pagination' => [
                'has_more' => $hasMore,
                'next_cursor' => $hasMore ? $promotions->last()['id'] : null,
            ],
        ]);
    }

    /**
     * Withdraw a promotion recommendation
     */
    public function withdrawPromotion(Request $request, int $promotionId): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:withdraw',
        ]);

        $managerId = Auth::id();

        $promotion = PromotionRecommendation::where('id', $promotionId)
            ->where('proposed_by_user_id', $managerId)
            ->first();

        if (! $promotion) {
            return response()->json([
                'error' => 'Promotion recommendation not found',
                'code' => '404-NOT-FOUND',
            ], 404);
        }

        if (! $promotion->canWithdraw()) {
            return response()->json([
                'error' => 'Cannot withdraw this promotion recommendation',
                'code' => '422-INVALID-ACTION',
            ], 422);
        }

        try {
            $promotion->withdraw();

            return response()->json([
                'message' => 'Promotion recommendation withdrawn successfully',
                'promotion' => $promotion->fresh(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to withdraw promotion recommendation',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add employee to succession pool
     */
    public function createSuccession(Request $request): JsonResponse
    {
        $request->validate([
            'employee_user_id' => 'required|integer|exists:users,id',
            'role_id' => 'required|integer|exists:roles,id',
            'readiness' => 'required|in:ready_now,3_6m,6_12m,12_24m',
            'notes' => 'sometimes|string|max:1000',
            'learning_path_id' => 'sometimes|integer|exists:learning_paths,id',
            'mentor_user_id' => 'sometimes|integer|exists:users,id',
        ]);

        $managerId = Auth::id();
        $employeeId = $request->get('employee_user_id');

        // Verify employee is in manager's team
        if (! $this->canManagerAccessEmployee($managerId, $employeeId)) {
            return response()->json([
                'error' => 'Employee not found in your team',
                'code' => '403-SCOPE',
            ], 403);
        }

        try {
            $succession = DB::transaction(function () use ($request, $managerId, $employeeId) {
                // Create or get succession pool for the role
                $successionPool = \App\Models\ManagerReviews\SuccessionPool::firstOrCreate([
                    'role_id' => $request->get('role_id'),
                ], [
                    'created_by_user_id' => $managerId,
                    'priority_level' => 'medium',
                ]);

                // Check if candidate already exists
                $existingCandidate = \App\Models\ManagerReviews\ManagerSuccessionCandidate::where([
                    'succession_pool_id' => $successionPool->id,
                    'employee_user_id' => $employeeId,
                    'status' => 'active',
                ])->first();

                if ($existingCandidate) {
                    throw new \Exception('Employee is already in the succession pool for this role');
                }

                // Create succession candidate
                return \App\Models\ManagerReviews\ManagerSuccessionCandidate::create([
                    'succession_pool_id' => $successionPool->id,
                    'employee_user_id' => $employeeId,
                    'readiness' => $request->get('readiness'),
                    'learning_path_id' => $request->get('learning_path_id'),
                    'mentor_user_id' => $request->get('mentor_user_id'),
                    'notes' => $request->get('notes'),
                    'source' => 'manager',
                    'nominated_by_user_id' => $managerId,
                ]);
            });

            return response()->json([
                'message' => 'Employee added to succession pool successfully',
                'succession_candidate' => $succession->load(['employee', 'successionPool.role', 'learningPath', 'mentor']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to add employee to succession pool',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign career path to employee
     */
    public function assignCareerPath(Request $request): JsonResponse
    {
        $request->validate([
            'employee_user_id' => 'required|integer|exists:users,id',
            'target_role_id' => 'required|integer|exists:roles,id',
            'learning_path_id' => 'required|integer|exists:learning_paths,id',
            'notes' => 'sometimes|string|max:1000',
        ]);

        $managerId = Auth::id();
        $employeeId = $request->get('employee_user_id');

        // Verify employee is in manager's team
        if (! $this->canManagerAccessEmployee($managerId, $employeeId)) {
            return response()->json([
                'error' => 'Employee not found in your team',
                'code' => '403-SCOPE',
            ], 403);
        }

        try {
            $careerPath = DB::transaction(function () use ($request, $managerId, $employeeId) {
                // Check for existing active assignment
                $existingAssignment = \App\Models\ManagerReviews\CareerPathAssigned::where([
                    'employee_user_id' => $employeeId,
                    'target_role_id' => $request->get('target_role_id'),
                    'status' => 'active',
                ])->first();

                if ($existingAssignment) {
                    throw new \Exception('Employee already has an active career path assignment for this role');
                }

                // Get learning path details for duration estimate
                $learningPath = LearningPath::find($request->get('learning_path_id'));
                $targetCompletionDate = $learningPath ?
                    now()->addDays($learningPath->estimated_duration_days ?? 180) :
                    now()->addDays(180);

                return \App\Models\ManagerReviews\CareerPathAssigned::create([
                    'employee_user_id' => $employeeId,
                    'target_role_id' => $request->get('target_role_id'),
                    'learning_path_id' => $request->get('learning_path_id'),
                    'assigned_by_user_id' => $managerId,
                    'notes' => $request->get('notes'),
                    'target_completion_date' => $targetCompletionDate,
                    'status' => 'active',
                ]);
            });

            return response()->json([
                'message' => 'Career path assigned successfully',
                'career_path_assignment' => $careerPath->load(['employee', 'targetRole', 'learningPath', 'assignedBy']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to assign career path',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available roles for promotion/succession
     */
    public function getAvailableRoles(Request $request): JsonResponse
    {
        $roles = Role::where('is_active', true)
            ->orderBy('level')
            ->orderBy('name')
            ->get(['id', 'name', 'level', 'department']);

        return response()->json(['roles' => $roles]);
    }

    /**
     * Get available learning paths
     */
    public function getLearningPaths(Request $request): JsonResponse
    {
        $roleId = $request->get('role_id');

        $query = LearningPath::active();

        if ($roleId) {
            $query->whereHas('roles', function ($q) use ($roleId) {
                $q->where('role_id', $roleId);
            });
        }

        $learningPaths = $query->orderBy('title')
            ->get(['id', 'title', 'description', 'estimated_duration_days']);

        return response()->json(['learning_paths' => $learningPaths]);
    }

    /**
     * Check if manager can access employee
     */
    private function canManagerAccessEmployee(int $managerId, int $employeeId): bool
    {
        // Check direct reports
        $directReport = TeamMember::active()
            ->reportsTo($managerId)
            ->forEmployee($employeeId)
            ->exists();

        if ($directReport) {
            return true;
        }

        // Check indirect reports (team members)
        $teamMember = TeamMember::findByEmployee($employeeId);
        if (! $teamMember) {
            return false;
        }

        // Get all subordinates of the manager
        $managerTeamMember = TeamMember::findByEmployee($managerId);
        if (! $managerTeamMember) {
            return false;
        }

        $allSubordinates = $managerTeamMember->getAllSubordinates();

        return $allSubordinates->contains('employee_user_id', $employeeId);
    }
}
