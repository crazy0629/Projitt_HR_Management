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
        'employee_id',
        'proposed_by_id',
        'current_role_id',
        'proposed_role_id',
        'justification',
        'current_salary',
        'proposed_salary',
        'priority',
        'comp_adjustment_min',
        'comp_adjustment_max',
        'workflow_id',
        'status',
        'meta',
        'approval_notes',
        'approved_by_id',
        'approved_at',
        'effective_date',
    ];

    protected $casts = [
        'current_salary' => 'decimal:2',
        'proposed_salary' => 'decimal:2',
        'comp_adjustment_min' => 'decimal:2',
        'comp_adjustment_max' => 'decimal:2',
        'meta' => 'array',
        'approved_at' => 'datetime',
        'effective_date' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function reviewCycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceReviewCycle::class, 'review_cycle_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function proposedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by_id');
    }

    public function currentRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'current_role_id');
    }

    public function proposedRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'proposed_role_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

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
        return $query->where('employee_id', $employeeId);
    }

    public function scopeProposedBy(Builder $query, int $managerId): Builder
    {
        return $query->where('proposed_by_id', $managerId);
    }

    public function scopeInCycle(Builder $query, int $cycleId): Builder
    {
        return $query->where('review_cycle_id', $cycleId);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

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
        return $this->isPending() && !$this->approved_at;
    }

    public function canApprove(): bool
    {
        return $this->isPending();
    }

    public function withdraw(): self
    {
        if (!$this->canWithdraw()) {
            throw new \InvalidArgumentException('Cannot withdraw this promotion recommendation');
        }

        $this->update(['status' => 'withdrawn']);

        return $this->fresh();
    }

    public function approve(int $approvedByUserId, ?string $notes = null, ?\DateTime $effectiveDate = null): self
    {
        if (!$this->canApprove()) {
            throw new \InvalidArgumentException('Cannot approve this promotion recommendation');
        }

        $this->update([
            'status' => 'approved',
            'approved_by_id' => $approvedByUserId,
            'approved_at' => now(),
            'approval_notes' => $notes,
            'effective_date' => $effectiveDate ?? now(),
        ]);

        return $this->fresh();
    }

    public function reject(int $rejectedByUserId, string $reason): self
    {
        if (!$this->canApprove()) {
            throw new \InvalidArgumentException('Cannot reject this promotion recommendation');
        }

        $this->update([
            'status' => 'rejected',
            'approved_by_id' => $rejectedByUserId,
            'approved_at' => now(),
            'approval_notes' => $reason,
        ]);

        return $this->fresh();
    }

    public function getCompensationAdjustmentRange(): ?array
    {
        if ($this->comp_adjustment_min === null && $this->comp_adjustment_max === null) {
            return null;
        }

        $avg = null;
        if ($this->comp_adjustment_min !== null && $this->comp_adjustment_max !== null) {
            $avg = ($this->comp_adjustment_min + $this->comp_adjustment_max) / 2;
        }

        return [
            'min' => $this->comp_adjustment_min,
            'max' => $this->comp_adjustment_max,
            'average' => $avg,
        ];
    }

    public function getPromotionLevel(): string
    {
        $currentLevel = $this->currentRole->level ?? 1;
        $targetLevel = $this->proposedRole->level ?? 1;

        $diff = $targetLevel - $currentLevel;

        return match (true) {
            $diff >= 2 => 'double_promotion',
            $diff === 1 => 'standard_promotion',
            $diff === 0 => 'lateral_move',
            default => 'demotion',
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'pending' => '#F59E0B',
            'approved' => '#10B981',
            'rejected' => '#EF4444',
            'withdrawn' => '#6B7280',
            default => '#6B7280',
        };
    }

    public function getStatusIcon(): string
    {
        return match ($this->status) {
            'pending' => 'clock',
            'approved' => 'check-circle',
            'rejected' => 'x-circle',
            'withdrawn' => 'minus-circle',
            default => 'help-circle',
        };
    }

    public function getTimeToDecision(): ?int
    {
        return $this->approved_at ? $this->created_at->diffInDays($this->approved_at) : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Static Factories & Utilities
    |--------------------------------------------------------------------------
    */

    public static function getStatuses(): array
    {
        return [
            'pending' => 'Pending Review',
            'under_review' => 'Under Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'withdrawn' => 'Withdrawn',
        ];
    }

    public static function createRecommendation(
        int $employeeId,
        int $proposedById,
        int $proposedRoleId,
        string $justification,
        ?int $cycleId = null,
        ?int $currentRoleId = null,
        ?float $compMin = null,
        ?float $compMax = null,
        ?string $workflowId = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'employee_id' => $employeeId,
            'proposed_by_id' => $proposedById,
            'proposed_role_id' => $proposedRoleId,
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

    public static function getManagerRecommendations(int $managerId, ?string $status = null)
    {
        $query = self::proposedBy($managerId)
            ->with(['employee', 'proposedRole', 'currentRole', 'reviewCycle']);

        if ($status) {
            $query->byStatus($status);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public static function getTeamRecommendations(array $employeeIds, ?string $status = null)
    {
        $query = self::whereIn('employee_id', $employeeIds)
            ->with(['employee', 'proposedRole', 'currentRole', 'proposedBy', 'reviewCycle']);

        if ($status) {
            $query->byStatus($status);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
