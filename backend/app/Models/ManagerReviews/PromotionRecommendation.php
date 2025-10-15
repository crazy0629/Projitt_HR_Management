<?php

namespace App\Models\ManagerReviews;

use App\Models\PerformanceReview\PerformanceReviewCycle;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionRecommendation extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_cycle_id',
        'employee_user_id',
        'proposed_by_user_id',
        'current_role_id',
        'target_role_id',
        'justification',
        'comp_adjustment_min',
        'comp_adjustment_max',
        'workflow_id',
        'status',
        'meta',
        'approval_notes',
        'approved_by_user_id',
        'approved_at',
        'effective_date',
    ];

    protected $casts = [
        'comp_adjustment_min' => 'decimal:2',
        'comp_adjustment_max' => 'decimal:2',
        'meta' => 'array',
        'approved_at' => 'datetime',
        'effective_date' => 'datetime',
    ];

    // Relationships
    public function reviewCycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceReviewCycle::class, 'review_cycle_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_user_id');
    }

    public function proposedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by_user_id');
    }

    public function currentRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'current_role_id');
    }

    public function targetRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'target_role_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    // Scopes
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeForEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_user_id', $employeeId);
    }

    public function scopeProposedBy(Builder $query, int $managerId): Builder
    {
        return $query->where('proposed_by_user_id', $managerId);
    }

    public function scopeInCycle(Builder $query, int $cycleId): Builder
    {
        return $query->where('review_cycle_id', $cycleId);
    }

    // Helper methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function canWithdraw(): bool
    {
        return $this->isPending() && ! $this->approved_at;
    }

    public function canApprove(): bool
    {
        return $this->isPending();
    }

    public function withdraw(): self
    {
        if (! $this->canWithdraw()) {
            throw new \InvalidArgumentException('Cannot withdraw this promotion recommendation');
        }

        $this->update(['status' => 'withdrawn']);

        return $this->fresh();
    }

    public function approve(int $approvedByUserId, ?string $notes = null, ?\DateTime $effectiveDate = null): self
    {
        if (! $this->canApprove()) {
            throw new \InvalidArgumentException('Cannot approve this promotion recommendation');
        }

        $this->update([
            'status' => 'approved',
            'approved_by_user_id' => $approvedByUserId,
            'approved_at' => now(),
            'approval_notes' => $notes,
            'effective_date' => $effectiveDate ?? now(),
        ]);

        return $this->fresh();
    }

    public function reject(int $rejectedByUserId, string $reason): self
    {
        if (! $this->canApprove()) {
            throw new \InvalidArgumentException('Cannot reject this promotion recommendation');
        }

        $this->update([
            'status' => 'rejected',
            'approved_by_user_id' => $rejectedByUserId,
            'approved_at' => now(),
            'approval_notes' => $reason,
        ]);

        return $this->fresh();
    }

    public function getCompensationAdjustmentRange(): array
    {
        return [
            'min' => $this->comp_adjustment_min,
            'max' => $this->comp_adjustment_max,
            'average' => ($this->comp_adjustment_min + $this->comp_adjustment_max) / 2,
        ];
    }

    public function getPromotionLevel(): string
    {
        // This would need to be implemented based on role hierarchy
        $currentLevel = $this->currentRole->level ?? 1;
        $targetLevel = $this->targetRole->level ?? 1;

        $levelDifference = $targetLevel - $currentLevel;

        return match (true) {
            $levelDifference >= 2 => 'double_promotion',
            $levelDifference === 1 => 'standard_promotion',
            $levelDifference === 0 => 'lateral_move',
            default => 'demotion'
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'pending' => '#F59E0B', // Amber
            'approved' => '#10B981', // Green
            'rejected' => '#EF4444', // Red
            'withdrawn' => '#6B7280', // Gray
            default => '#6B7280'
        };
    }

    public function getStatusIcon(): string
    {
        return match ($this->status) {
            'pending' => 'clock',
            'approved' => 'check-circle',
            'rejected' => 'x-circle',
            'withdrawn' => 'minus-circle',
            default => 'help-circle'
        };
    }

    public function getTimeToDecision(): ?int
    {
        if (! $this->approved_at) {
            return null;
        }

        return $this->created_at->diffInDays($this->approved_at);
    }

    // Static helper methods
    public static function getStatuses(): array
    {
        return [
            'pending' => 'Pending Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'withdrawn' => 'Withdrawn',
        ];
    }

    public static function createRecommendation(
        int $employeeId,
        int $proposedById,
        int $targetRoleId,
        string $justification,
        ?int $cycleId = null,
        ?int $currentRoleId = null,
        ?float $compMin = null,
        ?float $compMax = null,
        ?string $workflowId = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'employee_user_id' => $employeeId,
            'proposed_by_user_id' => $proposedById,
            'target_role_id' => $targetRoleId,
            'justification' => $justification,
            'review_cycle_id' => $cycleId,
            'current_role_id' => $currentRoleId,
            'comp_adjustment_min' => $compMin,
            'comp_adjustment_max' => $compMax,
            'workflow_id' => $workflowId,
            'meta' => $metadata,
            'status' => 'pending',
        ]);
    }

    public static function getManagerRecommendations(int $managerId, ?string $status = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = self::proposedBy($managerId)
            ->with(['employee', 'targetRole', 'currentRole', 'reviewCycle']);

        if ($status) {
            $query->byStatus($status);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public static function getTeamRecommendations(array $employeeIds, ?string $status = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = self::whereIn('employee_user_id', $employeeIds)
            ->with(['employee', 'targetRole', 'currentRole', 'proposedBy', 'reviewCycle']);

        if ($status) {
            $query->byStatus($status);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
