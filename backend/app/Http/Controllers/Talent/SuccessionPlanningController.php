<?php

namespace App\Http\Controllers\Talent;

use App\Http\Controllers\Controller;
use App\Models\Talent\SuccessionCandidate;
use App\Models\Talent\SuccessionRole;
use App\Services\Talent\SuccessionPlanningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SuccessionPlanningController extends Controller
{
    protected $successionService;

    public function __construct(SuccessionPlanningService $successionService)
    {
        $this->successionService = $successionService;
    }

    /**
     * Get succession plan overview
     */
    public function index(Request $request)
    {
        $roleId = $request->filled('role_id') ? $request->role_id : null;
        $plan = $this->successionService->getSuccessionPlan($roleId);

        return response()->json([
            'success' => true,
            'data' => $plan,
        ]);
    }

    /**
     * Create a new succession role
     */
    public function createRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
            'incumbent_id' => 'nullable|exists:users,id',
            'criticality' => 'required|in:low,medium,high,critical',
            'risk_level' => 'required|in:low,medium,high',
            'replacement_timeline' => 'required|in:immediate,short,medium,long',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $successionRole = $this->successionService->createSuccessionRole(
                $request->role_id,
                $request->incumbent_id,
                $request->only(['criticality', 'risk_level', 'replacement_timeline'])
            );

            return response()->json([
                'success' => true,
                'message' => 'Succession role created successfully',
                'data' => $successionRole->load(['role', 'incumbent']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Add a succession candidate
     */
    public function addCandidate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'succession_role_id' => 'required|exists:succession_roles,id',
            'employee_id' => 'required|exists:users,id',
            'target_role_id' => 'nullable|exists:roles,id',
            'readiness' => 'required|in:ready,developing,long_term',
            'learning_path_id' => 'nullable|exists:learning_paths,id',
            'target_ready_date' => 'nullable|date',
            'strengths' => 'nullable|array',
            'development_areas' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $candidate = $this->successionService->addSuccessionCandidate(
                $request->succession_role_id,
                $request->employee_id,
                $request->only([
                    'target_role_id', 'readiness', 'learning_path_id',
                    'target_ready_date', 'strengths', 'development_areas',
                ])
            );

            return response()->json([
                'success' => true,
                'message' => 'Succession candidate added successfully',
                'data' => $candidate->load(['employee', 'targetRole', 'learningPath']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update candidate readiness
     */
    public function updateCandidateReadiness(Request $request, $candidateId)
    {
        $validator = Validator::make($request->all(), [
            'readiness' => 'required|in:ready,developing,long_term',
            'target_ready_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $candidate = $this->successionService->updateCandidateReadiness(
                $candidateId,
                $request->readiness,
                $request->target_ready_date
            );

            return response()->json([
                'success' => true,
                'message' => 'Candidate readiness updated successfully',
                'data' => $candidate,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Assign learning path to candidate
     */
    public function assignLearningPath(Request $request, $candidateId)
    {
        $validator = Validator::make($request->all(), [
            'learning_path_id' => 'required|exists:learning_paths,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $candidate = $this->successionService->assignLearningPath(
                $candidateId,
                $request->learning_path_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Learning path assigned successfully',
                'data' => $candidate->load('learningPath'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get succession metrics
     */
    public function metrics()
    {
        $metrics = $this->successionService->getSuccessionMetrics();

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * Get employee succession opportunities
     */
    public function employeeOpportunities($employeeId)
    {
        $opportunities = $this->successionService->getEmployeeSuccessionOpportunities($employeeId);

        return response()->json([
            'success' => true,
            'data' => $opportunities,
        ]);
    }

    /**
     * Get critical role gaps
     */
    public function criticalGaps()
    {
        $gaps = $this->successionService->getCriticalRoleGaps();

        return response()->json([
            'success' => true,
            'data' => $gaps,
        ]);
    }

    /**
     * Promote candidate to ready status
     */
    public function promoteCandidate(Request $request, $candidateId)
    {
        $validator = Validator::make($request->all(), [
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $candidate = $this->successionService->promoteCandidate($candidateId, $request->note);

            return response()->json([
                'success' => true,
                'message' => 'Candidate promoted to ready status',
                'data' => $candidate,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get succession readiness benchmark
     */
    public function readinessBenchmark()
    {
        $benchmark = $this->successionService->benchmarkSuccessionReadiness();

        return response()->json([
            'success' => true,
            'data' => $benchmark,
        ]);
    }

    /**
     * Get succession role details
     */
    public function showRole($id)
    {
        $role = SuccessionRole::with([
            'role',
            'incumbent',
            'candidates.employee',
            'candidates.learningPath',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $role,
        ]);
    }

    /**
     * Get succession candidate details
     */
    public function showCandidate($id)
    {
        $candidate = SuccessionCandidate::with([
            'employee',
            'targetRole',
            'learningPath',
            'successionRole.role',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $candidate,
        ]);
    }
}
