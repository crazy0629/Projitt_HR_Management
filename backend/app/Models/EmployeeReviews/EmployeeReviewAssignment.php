<?php

namespace App\Models\EmployeeReviews;

use App\Models\PerformanceReview\PerformanceReviewCycle;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EmployeeReviewAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'cycle_id',
        'reviewee_id',
        'reviewer_id',
        'review_type',
        'form_id',
        'status',
        'assigned_at',
        'due_date',
        'started_at',
        'completed_at',
        'assignment_metadata',
        'completion_notes',
        'assigned_by',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'due_date' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'assignment_metadata' => 'array',
    ];

    // Relationships
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PerformanceReviewCycle::class, 'cycle_id');
    }

    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(EmployeeReviewForm::class, 'form_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function reviewItem(): HasOne
    {
        return $this->hasOne(EmployeeReviewItem::class, 'assignment_id');
    }

    // Scopes
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByReviewType(Builder $query, string $reviewType): Builder
    {
        return $query->where('review_type', $reviewType);
    }

    public function scopeForReviewer(Builder $query, int $reviewerId): Builder
    {
        return $query->where('reviewer_id', $reviewerId);
    }

    public function scopeForReviewee(Builder $query, int $revieweeId): Builder
    {
        return $query->where('reviewee_id', $revieweeId);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', ['completed']);
    }

    public function scopeUpcoming(Builder $query, int $days = 7): Builder
    {
        return $query->whereBetween('due_date', [now(), now()->addDays($days)])
            ->whereNotIn('status', ['completed']);
    }

    // Helper methods
    public function isOverdue(): bool
    {
        return $this->due_date &&
               $this->due_date->isPast() &&
               ! in_array($this->status, ['completed']);
    }

    public function getDaysRemaining(): ?int
    {
        if (! $this->due_date || $this->isCompleted()) {
            return null;
        }

        return max(0, now()->diffInDays($this->due_date, false));
    }

    public function getTimeSpent(): ?int
    {
        if (! $this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? now();

        return $this->started_at->diffInMinutes($endTime);
    }

    public function canStart(): bool
    {
        return in_array($this->status, ['pending']);
    }

    public function canComplete(): bool
    {
        return in_array($this->status, ['pending', 'in_progress']);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function start(): self
    {
        if (! $this->canStart()) {
            throw new \InvalidArgumentException('Assignment cannot be started');
        }

        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return $this->fresh();
    }

    public function complete(?string $notes = null): self
    {
        if (! $this->canComplete()) {
            throw new \InvalidArgumentException('Assignment cannot be completed');
        }

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completion_notes' => $notes,
        ]);

        return $this->fresh();
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'pending' => '#F59E0B', // Amber
            'in_progress' => '#3B82F6', // Blue
            'completed' => '#10B981', // Green
            'overdue' => '#EF4444', // Red
            default => '#6B7280' // Gray
        };
    }

    public function getStatusIcon(): string
    {
        return match ($this->status) {
            'pending' => 'clock',
            'in_progress' => 'play-circle',
            'completed' => 'check-circle',
            'overdue' => 'alert-triangle',
            default => 'help-circle'
        };
    }

    public function getReviewTypeLabel(): string
    {
        return match ($this->review_type) {
            'self' => 'Self Review',
            'manager' => 'Manager Review',
            'peer' => 'Peer Review',
            'subordinate' => 'Subordinate Review',
            default => ucfirst($this->review_type).' Review'
        };
    }

    public function getPriorityLevel(): string
    {
        if ($this->isOverdue()) {
            return 'urgent';
        }

        $daysRemaining = $this->getDaysRemaining();
        if ($daysRemaining !== null && $daysRemaining <= 3) {
            return 'high';
        }

        if ($this->review_type === 'manager') {
            return 'medium';
        }

        return 'normal';
    }

    // Static helper methods
    public static function getStatuses(): array
    {
        return [
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'overdue' => 'Overdue',
        ];
    }

    public static function getReviewTypes(): array
    {
        return [
            'self' => 'Self Review',
            'manager' => 'Manager Review',
            'peer' => 'Peer Review',
            'subordinate' => 'Subordinate Review',
        ];
    }

    public static function createAssignment(
        int $cycleId,
        int $revieweeId,
        int $reviewerId,
        string $reviewType,
        int $formId,
        ?Carbon $dueDate = null,
        ?array $metadata = null,
        ?int $assignedBy = null
    ): self {
        return self::create([
            'cycle_id' => $cycleId,
            'reviewee_id' => $revieweeId,
            'reviewer_id' => $reviewerId,
            'review_type' => $reviewType,
            'form_id' => $formId,
            'status' => 'pending',
            'assigned_at' => now(),
            'due_date' => $dueDate,
            'assignment_metadata' => $metadata,
            'assigned_by' => $assignedBy ?? \Illuminate\Support\Facades\Auth::guard('sanctum')->id() ?? auth()->id(),
        ]);
    }
}
