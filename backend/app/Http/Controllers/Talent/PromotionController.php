<?php

namespace App\Http\Controllers\Talent;

use App\Http\Controllers\Controller;
use App\Models\Talent\PromotionCandidate;
use App\Models\Talent\PromotionWorkflow;
use App\Services\Talent\PromotionWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PromotionController extends Controller
{
    protected $promotionService;

    public function __construct(PromotionWorkflowService $promotionService)
    {
        $this->promotionService = $promotionService;
    }

    /**
     * Get all promotions with filtering and pagination
     */
    public function index(Request $request)
    {
        $query = PromotionCandidate::with(['employee', 'currentRole', 'proposedRole', 'workflow']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('employee_id')) {
            $query->byEmployee($request->employee_id);
        }

        if ($request->filled('department')) {
            $query->byDepartment($request->department);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $promotions = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $promotions,
        ]);
    }

    /**
     * Create a new promotion request
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:users,id',
            'current_role_id' => 'nullable|exists:roles,id',
            'proposed_role_id' => 'nullable|exists:roles,id',
            'justification' => 'required|string|min:10',
            'comp_adjustment' => 'nullable|array',
            'comp_adjustment.type' => 'required_with:comp_adjustment|in:amount,percentage',
            'comp_adjustment.value' => 'required_with:comp_adjustment|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $promotion = $this->promotionService->createPromotion(
                $request->employee_id,
                $request->only(['current_role_id', 'proposed_role_id', 'justification', 'comp_adjustment'])
            );

            return response()->json([
                'success' => true,
                'message' => 'Promotion request created successfully',
                'data' => $promotion->load(['employee', 'currentRole', 'proposedRole', 'workflow']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get specific promotion details
     */
    public function show($id)
    {
        $promotion = PromotionCandidate::with([
            'employee',
            'currentRole',
            'proposedRole',
            'workflow',
            'approvals.approver',
            'creator',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $promotion,
        ]);
    }

    /**
     * Update promotion request (only in draft state)
     */
    public function update(Request $request, $id)
    {
        $promotion = PromotionCandidate::findOrFail($id);

        if (! $promotion->canEdit()) {
            return response()->json([
                'success' => false,
                'message' => 'Promotion cannot be edited in its current state',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'current_role_id' => 'nullable|exists:roles,id',
            'proposed_role_id' => 'nullable|exists:roles,id',
            'justification' => 'sometimes|string|min:10',
            'comp_adjustment' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $promotion->update($request->only([
            'current_role_id',
            'proposed_role_id',
            'justification',
            'comp_adjustment',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Promotion request updated successfully',
            'data' => $promotion->load(['employee', 'currentRole', 'proposedRole']),
        ]);
    }

    /**
     * Submit promotion for approval
     */
    public function submit($id)
    {
        try {
            $promotion = $this->promotionService->submitForApproval($id);

            return response()->json([
                'success' => true,
                'message' => 'Promotion submitted for approval successfully',
                'data' => $promotion->load(['approvals.approver']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Withdraw promotion request
     */
    public function withdraw(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $promotion = $this->promotionService->withdrawPromotion($id, $request->reason);

            return response()->json([
                'success' => true,
                'message' => 'Promotion request withdrawn successfully',
                'data' => $promotion,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Process approval decision
     */
    public function processApproval(Request $request, $approvalId)
    {
        $validator = Validator::make($request->all(), [
            'decision' => 'required|in:approved,rejected',
            'note' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // We need to find the promotion ID from the approval
            $approval = \App\Models\Talent\PromotionApproval::findOrFail($approvalId);
            $promotion = $this->promotionService->processApproval(
                $approval->promotion_id,
                $approvalId,
                $request->decision,
                $request->note
            );

            return response()->json([
                'success' => true,
                'message' => 'Approval processed successfully',
                'data' => $promotion->load(['approvals.approver']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get pending approvals for current user
     */
    public function pendingApprovals()
    {
        $pendingApprovals = $this->promotionService->getPromotionsForApprover(Auth::id(), 'pending');

        return response()->json([
            'success' => true,
            'data' => $pendingApprovals,
        ]);
    }

    /**
     * Get promotion statistics and analytics
     */
    public function stats(Request $request)
    {
        $startDate = $request->date_from ? \Carbon\Carbon::parse($request->date_from) : null;
        $endDate = $request->date_to ? \Carbon\Carbon::parse($request->date_to) : null;

        $stats = $this->promotionService->getPromotionMetrics($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get available workflows
     */
    public function workflows()
    {
        $workflows = PromotionWorkflow::active()->get();

        return response()->json([
            'success' => true,
            'data' => $workflows,
        ]);
    }

    /**
     * Get promotion timeline/history
     */
    public function timeline($id)
    {
        $promotion = PromotionCandidate::with([
            'approvals' => function ($q) {
                $q->orderBy('step_order')->with('approver');
            },
        ])->findOrFail($id);

        $timeline = [];

        // Creation event
        $timeline[] = [
            'type' => 'created',
            'date' => $promotion->created_at,
            'actor' => $promotion->creator->name ?? 'System',
            'description' => 'Promotion request created',
        ];

        // Submission event
        if ($promotion->submitted_at) {
            $timeline[] = [
                'type' => 'submitted',
                'date' => $promotion->submitted_at,
                'actor' => $promotion->creator->name ?? 'System',
                'description' => 'Promotion submitted for approval',
            ];
        }

        // Approval events
        foreach ($promotion->approvals as $approval) {
            if ($approval->decided_at) {
                $timeline[] = [
                    'type' => $approval->decision,
                    'date' => $approval->decided_at,
                    'actor' => $approval->approver->name,
                    'description' => ucfirst($approval->decision).' by '.$approval->approver->name,
                    'note' => $approval->decision_note,
                ];
            }
        }

        // Final status event
        if ($promotion->approved_at) {
            $timeline[] = [
                'type' => 'completed',
                'date' => $promotion->approved_at,
                'actor' => 'System',
                'description' => 'Promotion approved and processed',
            ];
        }

        usort($timeline, fn ($a, $b) => $a['date']->compare($b['date']));

        return response()->json([
            'success' => true,
            'data' => $timeline,
        ]);
    }
}
