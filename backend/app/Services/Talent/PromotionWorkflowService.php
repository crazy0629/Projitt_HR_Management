<?php

namespace App\Services\Talent;

use App\Models\Talent\PromotionCandidate;
use App\Models\Talent\PromotionWorkflow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PromotionWorkflowService
{
    public function createPromotion($employeeId, $data)
    {
        DB::beginTransaction();

        try {
            // Determine appropriate workflow based on compensation adjustment
            $workflow = $this->determineWorkflow($data);

            $promotion = PromotionCandidate::createForEmployee($employeeId, array_merge($data, [
                'workflow_id' => $workflow->id,
            ]));

            DB::commit();

            return $promotion->load(['employee', 'currentRole', 'proposedRole', 'workflow']);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to create promotion', [
                'employee_id' => $employeeId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function submitForApproval($promotionId)
    {
        DB::beginTransaction();

        try {
            $promotion = PromotionCandidate::findOrFail($promotionId);
            $promotion->submit();

            // Send notifications to approvers
            $this->notifyApprovers($promotion);

            DB::commit();

            return $promotion->load(['approvals.approver']);

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function processApproval($promotionId, $approvalId, $decision, $note = null)
    {
        DB::beginTransaction();

        try {
            $promotion = PromotionCandidate::findOrFail($promotionId);

            if ($decision === 'approved') {
                $promotion->approve($approvalId, $note);
            } else {
                $promotion->reject($approvalId, $note);
            }

            // Notify relevant parties
            $this->notifyApprovalDecision($promotion, $decision, $note);

            DB::commit();

            return $promotion->fresh()->load(['approvals.approver', 'employee']);

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function withdrawPromotion($promotionId, $reason = null)
    {
        DB::beginTransaction();

        try {
            $promotion = PromotionCandidate::findOrFail($promotionId);
            $promotion->withdraw($reason);

            DB::commit();

            return $promotion;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function getPromotionsForApprover($approverId, $status = 'pending')
    {
        return PromotionCandidate::whereHas('approvals', function ($query) use ($approverId, $status) {
            $query->where('approver_id', $approverId)
                ->where('decision', $status);
        })
            ->with(['employee', 'currentRole', 'proposedRole', 'approvals.approver'])
            ->get();
    }

    public function getPromotionMetrics($startDate = null, $endDate = null)
    {
        $query = PromotionCandidate::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return [
            'total_promotions' => $query->count(),
            'approved' => $query->clone()->approved()->count(),
            'rejected' => $query->clone()->rejected()->count(),
            'in_review' => $query->clone()->inReview()->count(),
            'average_review_time' => $query->clone()->approved()->avg(DB::raw('DATEDIFF(approved_at, submitted_at)')),
            'by_department' => $query->clone()
                ->join('users', 'promotion_candidates.employee_id', '=', 'users.id')
                ->groupBy('users.department')
                ->selectRaw('users.department, COUNT(*) as count')
                ->pluck('count', 'department'),
        ];
    }

    private function determineWorkflow($data)
    {
        // If there's a compensation adjustment, use finance workflow
        if (isset($data['comp_adjustment']) && ! empty($data['comp_adjustment'])) {
            return PromotionWorkflow::getFinanceWorkflow();
        }

        return PromotionWorkflow::getDefault();
    }

    private function notifyApprovers($promotion)
    {
        // In a real implementation, this would send email/Slack notifications
        Log::info('Promotion submitted for approval', [
            'promotion_id' => $promotion->id,
            'employee_id' => $promotion->employee_id,
            'approvers' => $promotion->approvals->pluck('approver_id')->toArray(),
        ]);
    }

    private function notifyApprovalDecision($promotion, $decision, $note)
    {
        // In a real implementation, this would send notifications
        Log::info('Promotion approval decision made', [
            'promotion_id' => $promotion->id,
            'decision' => $decision,
            'note' => $note,
        ]);
    }
}
