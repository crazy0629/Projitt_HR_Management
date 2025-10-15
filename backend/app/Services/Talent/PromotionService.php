<?php

namespace App\Services\Talent;

use App\Models\Talent\PromotionCandidate;
use App\Models\Talent\PromotionWorkflow;
use App\Models\User\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PromotionService
{
    /**
     * Create a new promotion request
     */
    public function createPromotion($employeeId, $data)
    {
        DB::beginTransaction();

        try {
            // Validate employee exists and is eligible
            $employee = User::findOrFail($employeeId);
            $this->validatePromotionEligibility($employee);

            // Determine appropriate workflow
            $workflow = $this->determineWorkflow($data);

            // Create promotion candidate
            $promotion = PromotionCandidate::createForEmployee($employeeId, array_merge($data, [
                'workflow_id' => $workflow->id,
            ]));

            DB::commit();

            Log::info('Promotion request created', [
                'promotion_id' => $promotion->id,
                'employee_id' => $employeeId,
                'workflow_id' => $workflow->id,
            ]);

            return $promotion;

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to create promotion', [
                'employee_id' => $employeeId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Submit promotion for approval
     */
    public function submitPromotion($promotionId)
    {
        $promotion = PromotionCandidate::findOrFail($promotionId);

        if (! $promotion->canSubmit()) {
            throw new \Exception('Promotion cannot be submitted in its current state');
        }

        DB::beginTransaction();

        try {
            $promotion->submit();

            // Send notifications to approvers
            $this->notifyNextApprover($promotion);

            DB::commit();

            return $promotion;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Process approval decision
     */
    public function processApproval($approvalId, $decision, $note = null)
    {
        DB::beginTransaction();

        try {
            $promotion = PromotionCandidate::whereHas('approvals', function ($q) use ($approvalId) {
                $q->where('id', $approvalId);
            })->firstOrFail();

            if ($decision === 'approved') {
                $promotion->approve($approvalId, $note);
            } else {
                $promotion->reject($approvalId, $note);
            }

            // Notify relevant parties
            if ($promotion->isApproved()) {
                $this->notifyPromotionApproved($promotion);
            } elseif ($promotion->isRejected()) {
                $this->notifyPromotionRejected($promotion);
            } else {
                $this->notifyNextApprover($promotion);
            }

            DB::commit();

            return $promotion;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Get promotion statistics
     */
    public function getPromotionStats($filters = [])
    {
        $query = PromotionCandidate::query();

        // Apply filters
        if (! empty($filters['department'])) {
            $query->byDepartment($filters['department']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['date_range'])) {
            $query->whereBetween('created_at', $filters['date_range']);
        }

        return [
            'total' => $query->count(),
            'by_status' => $query->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'avg_approval_time' => $this->getAverageApprovalTime($query),
            'pending_approvals' => PromotionCandidate::inReview()->count(),
        ];
    }

    /**
     * Get promotions pending approval for user
     */
    public function getPendingApprovals($userId)
    {
        return PromotionCandidate::whereHas('currentApproval', function ($q) use ($userId) {
            $q->where('approver_id', $userId);
        })->with(['employee', 'currentRole', 'proposedRole', 'currentApproval'])->get();
    }

    /**
     * Withdraw promotion request
     */
    public function withdrawPromotion($promotionId, $reason = null)
    {
        $promotion = PromotionCandidate::findOrFail($promotionId);

        if (! $promotion->canWithdraw()) {
            throw new \Exception('Promotion cannot be withdrawn in its current state');
        }

        $promotion->withdraw($reason);

        return $promotion;
    }

    // Private helper methods
    private function validatePromotionEligibility($employee)
    {
        // Check if employee has any active promotions
        $activePromotions = PromotionCandidate::byEmployee($employee->id)
            ->whereIn('status', ['draft', 'submitted', 'in_review'])
            ->count();

        if ($activePromotions > 0) {
            throw new \Exception('Employee already has an active promotion request');
        }

        // Additional business rules can be added here
        // e.g., minimum tenure, performance requirements, etc.
    }

    private function determineWorkflow($data)
    {
        // If compensation adjustment is involved, use finance workflow
        if (! empty($data['comp_adjustment'])) {
            return PromotionWorkflow::getFinanceWorkflow();
        }

        // Otherwise use default workflow
        return PromotionWorkflow::getDefault();
    }

    private function getAverageApprovalTime($query)
    {
        $approved = $query->clone()->approved()
            ->whereNotNull('submitted_at')
            ->whereNotNull('approved_at')
            ->selectRaw('AVG(DATEDIFF(approved_at, submitted_at)) as avg_days')
            ->first();

        return $approved ? round($approved->avg_days, 1) : 0;
    }

    private function notifyNextApprover($promotion)
    {
        $nextApprover = $promotion->getNextApprover();

        if ($nextApprover) {
            // Integration point for notification system
            Log::info('Promotion approval notification', [
                'promotion_id' => $promotion->id,
                'approver_id' => $nextApprover->id,
                'employee_name' => $promotion->employee->name,
            ]);
        }
    }

    private function notifyPromotionApproved($promotion)
    {
        // Notify employee, manager, HR
        Log::info('Promotion approved notification', [
            'promotion_id' => $promotion->id,
            'employee_id' => $promotion->employee_id,
        ]);
    }

    private function notifyPromotionRejected($promotion)
    {
        // Notify employee and manager
        Log::info('Promotion rejected notification', [
            'promotion_id' => $promotion->id,
            'employee_id' => $promotion->employee_id,
            'reason' => $promotion->rejection_reason,
        ]);
    }
}
