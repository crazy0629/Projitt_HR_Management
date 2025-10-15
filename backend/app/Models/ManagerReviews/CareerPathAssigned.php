<?php

namespace App\Models\ManagerReviews;

use App\Models\LearningPath\LearningPath;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerPathAssigned extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_user_id',
        'target_role_id',
        'learning_path_id',
        'assigned_by_user_id',
        'status',
        'progress_percentage',
        'notes',
        'target_completion_date',
        'started_at',
        'completed_at',
        'milestones',
        'meta',
    ];

    protected $casts = [
        'progress_percentage' => 'decimal:2',
        'target_completion_date' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'milestones' => 'array',
        'meta' => 'array',
    ];

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_user_id');
    }

    public function targetRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'target_role_id');
    }

    public function learningPath(): BelongsTo
    {
        return $this->belongsTo(LearningPath::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeForEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_user_id', $employeeId);
    }

    public function scopeForRole(Builder $query, int $roleId): Builder
    {
        return $query->where('target_role_id', $roleId);
    }

    public function scopeAssignedBy(Builder $query, int $managerId): Builder
    {
        return $query->where('assigned_by_user_id', $managerId);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('target_completion_date', '<', now())
            ->whereIn('status', ['active', 'in_progress']);
    }

    public function scopeDueSoon(Builder $query, int $days = 30): Builder
    {
        return $query->whereBetween('target_completion_date', [now(), now()->addDays($days)])
            ->whereIn('status', ['active', 'in_progress']);
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isOverdue(): bool
    {
        return $this->target_completion_date < now() && ! $this->isCompleted();
    }

    public function isDueSoon(int $days = 30): bool
    {
        return $this->target_completion_date <= now()->addDays($days) &&
               $this->target_completion_date >= now() &&
               ! $this->isCompleted();
    }

    public function start(): self
    {
        if ($this->status !== 'active') {
            throw new \InvalidArgumentException('Can only start active career path assignments');
        }

        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return $this->fresh();
    }

    public function complete(?string $notes = null): self
    {
        if (! in_array($this->status, ['active', 'in_progress'])) {
            throw new \InvalidArgumentException('Can only complete active or in-progress career path assignments');
        }

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'progress_percentage' => 100.00,
            'notes' => $notes ? $this->notes."\n\n".now()->format('Y-m-d H:i').': Completed - '.$notes : $this->notes,
        ]);

        return $this->fresh();
    }

    public function updateProgress(float $percentage, ?string $notes = null): self
    {
        if ($percentage < 0 || $percentage > 100) {
            throw new \InvalidArgumentException('Progress percentage must be between 0 and 100');
        }

        $updates = [
            'progress_percentage' => $percentage,
        ];

        // Auto-start if not already started
        if ($this->status === 'active' && $percentage > 0) {
            $updates['status'] = 'in_progress';
            $updates['started_at'] = now();
        }

        // Auto-complete if 100%
        if ($percentage >= 100 && ! $this->isCompleted()) {
            $updates['status'] = 'completed';
            $updates['completed_at'] = now();
        }

        if ($notes) {
            $updates['notes'] = $this->notes."\n\n".now()->format('Y-m-d H:i').": Progress updated to {$percentage}% - ".$notes;
        }

        $this->update($updates);

        return $this->fresh();
    }

    public function addMilestone(string $title, string $description, ?\DateTime $targetDate = null, bool $completed = false): self
    {
        $milestones = $this->milestones ?? [];

        $milestone = [
            'id' => uniqid(),
            'title' => $title,
            'description' => $description,
            'target_date' => $targetDate?->format('Y-m-d'),
            'completed' => $completed,
            'completed_at' => $completed ? now()->format('Y-m-d H:i:s') : null,
            'created_at' => now()->format('Y-m-d H:i:s'),
        ];

        $milestones[] = $milestone;

        $this->update(['milestones' => $milestones]);

        return $this->fresh();
    }

    public function completeMilestone(string $milestoneId, ?string $notes = null): self
    {
        $milestones = $this->milestones ?? [];

        foreach ($milestones as &$milestone) {
            if ($milestone['id'] === $milestoneId) {
                $milestone['completed'] = true;
                $milestone['completed_at'] = now()->format('Y-m-d H:i:s');
                if ($notes) {
                    $milestone['completion_notes'] = $notes;
                }
                break;
            }
        }

        $this->update(['milestones' => $milestones]);

        // Update overall progress based on completed milestones
        $this->recalculateProgress();

        return $this->fresh();
    }

    public function getMilestoneProgress(): array
    {
        $milestones = $this->milestones ?? [];
        $total = count($milestones);
        $completed = count(array_filter($milestones, fn ($m) => $m['completed'] ?? false));

        return [
            'total' => $total,
            'completed' => $completed,
            'percentage' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
        ];
    }

    public function recalculateProgress(): self
    {
        // Calculate progress based on learning path completion and milestones
        $learningPathProgress = 0;
        $milestoneProgress = $this->getMilestoneProgress()['percentage'];

        // If learning path is associated, check enrollment progress
        if ($this->learning_path_id) {
            // This would integrate with your LMS enrollment system
            $enrollment = \App\Models\LMS\PathEnrollment::where('employee_user_id', $this->employee_user_id)
                ->where('learning_path_id', $this->learning_path_id)
                ->first();

            $learningPathProgress = $enrollment?->completion_percentage ?? 0;
        }

        // Weight the progress (70% learning path, 30% milestones)
        $overallProgress = ($learningPathProgress * 0.7) + ($milestoneProgress * 0.3);

        $this->updateProgress($overallProgress);

        return $this;
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'active' => '#6B7280', // Gray
            'in_progress' => '#3B82F6', // Blue
            'completed' => '#10B981', // Green
            'cancelled' => '#EF4444', // Red
            'paused' => '#F59E0B', // Amber
            default => '#6B7280'
        };
    }

    public function getStatusIcon(): string
    {
        return match ($this->status) {
            'active' => 'play',
            'in_progress' => 'clock',
            'completed' => 'check-circle',
            'cancelled' => 'x-circle',
            'paused' => 'pause',
            default => 'help-circle'
        };
    }

    public function getDaysToCompletion(): ?int
    {
        if ($this->isCompleted() || ! $this->target_completion_date) {
            return null;
        }

        return now()->diffInDays($this->target_completion_date, false);
    }

    public function getExpectedCompletionDate(): ?\DateTime
    {
        if ($this->isCompleted() || $this->progress_percentage <= 0) {
            return $this->completed_at;
        }

        // Estimate based on current progress rate
        $daysSinceStart = $this->started_at ? now()->diffInDays($this->started_at) : 0;

        if ($daysSinceStart <= 0) {
            return $this->target_completion_date;
        }

        $progressRate = $this->progress_percentage / $daysSinceStart;
        $remainingProgress = 100 - $this->progress_percentage;
        $estimatedDaysRemaining = $progressRate > 0 ? ceil($remainingProgress / $progressRate) : null;

        return $estimatedDaysRemaining ? now()->addDays($estimatedDaysRemaining) : null;
    }

    // Static helper methods
    public static function getStatusOptions(): array
    {
        return [
            'active' => 'Active',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'paused' => 'Paused',
        ];
    }

    public static function createAssignment(
        int $employeeId,
        int $targetRoleId,
        int $learningPathId,
        int $assignedById,
        ?\DateTime $targetCompletionDate = null,
        ?string $notes = null,
        ?array $milestones = null
    ): self {
        $assignment = self::create([
            'employee_user_id' => $employeeId,
            'target_role_id' => $targetRoleId,
            'learning_path_id' => $learningPathId,
            'assigned_by_user_id' => $assignedById,
            'status' => 'active',
            'progress_percentage' => 0,
            'target_completion_date' => $targetCompletionDate ?? now()->addDays(180),
            'notes' => $notes,
            'milestones' => $milestones ?? [],
        ]);

        return $assignment;
    }

    public static function getAssignmentsForManager(int $managerId): \Illuminate\Database\Eloquent\Collection
    {
        return self::assignedBy($managerId)
            ->with(['employee', 'targetRole', 'learningPath'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public static function getEmployeeAssignments(int $employeeId, ?string $status = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = self::forEmployee($employeeId)
            ->with(['targetRole', 'learningPath', 'assignedBy']);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public static function getOverdueAssignments(): \Illuminate\Database\Eloquent\Collection
    {
        return self::overdue()
            ->with(['employee', 'targetRole', 'assignedBy'])
            ->orderBy('target_completion_date')
            ->get();
    }

    public static function getUpcomingDeadlines(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return self::dueSoon($days)
            ->with(['employee', 'targetRole', 'assignedBy'])
            ->orderBy('target_completion_date')
            ->get();
    }
}
