<?php

namespace App\Models\PerformanceReview;

use App\Models\LearningPath\LearningPath;
use App\Models\User\Role;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_id',
        'action_type',
        'title',
        'description',
        'target_role_id',
        'mentor_id',
        'learning_path_id',
        'metadata',
        'priority',
        'status',
        'target_date',
        'notes',
        'assigned_to',
        'created_by',
        'completed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'target_date' => 'date',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $dates = [
        'target_date',
        'completed_at',
        'created_at',
        'updated_at',
    ];

    // Relationships
    public function review()
    {
        return $this->belongsTo(PerformanceReview::class, 'review_id');
    }

    public function targetRole()
    {
        return $this->belongsTo(Role::class, 'target_role_id');
    }

    public function mentor()
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function learningPath()
    {
        return $this->belongsTo(LearningPath::class, 'learning_path_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('target_date', '<', now())
            ->whereIn('status', ['pending', 'in_progress']);
    }

    public function scopeByActionType($query, $type)
    {
        return $query->where('action_type', $type);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeSuccessionActions($query)
    {
        return $query->whereIn('action_type', ['promotion', 'succession_pool', 'career_path']);
    }

    public function scopeDevelopmentActions($query)
    {
        return $query->whereIn('action_type', ['learning_path', 'assign_mentor', 'improvement_plan']);
    }

    // Helper methods
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isInProgress()
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function isOverdue()
    {
        return $this->target_date &&
               $this->target_date < now() &&
               ! $this->isCompleted() &&
               ! $this->isCancelled();
    }

    public function getActionTypeLabel()
    {
        $labels = [
            'promotion' => 'Promotion',
            'succession_pool' => 'Add to Succession Pool',
            'career_path' => 'Career Path Planning',
            'assign_mentor' => 'Assign Mentor',
            'learning_path' => 'Learning Path Assignment',
            'improvement_plan' => 'Performance Improvement Plan',
            'role_change' => 'Role Change',
            'salary_adjustment' => 'Salary Adjustment',
        ];

        return $labels[$this->action_type] ?? ucfirst(str_replace('_', ' ', $this->action_type));
    }

    public function getPriorityLabel()
    {
        $labels = [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'urgent' => 'Urgent',
        ];

        return $labels[$this->priority] ?? 'Unknown';
    }

    public function getPriorityColor()
    {
        $colors = [
            'low' => 'success',
            'medium' => 'info',
            'high' => 'warning',
            'urgent' => 'danger',
        ];

        return $colors[$this->priority] ?? 'secondary';
    }

    public function getStatusLabel()
    {
        $labels = [
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        return $labels[$this->status] ?? 'Unknown';
    }

    public function getStatusColor()
    {
        $colors = [
            'pending' => 'warning',
            'in_progress' => 'info',
            'completed' => 'success',
            'cancelled' => 'secondary',
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    public function getDaysUntilDue()
    {
        if (! $this->target_date) {
            return null;
        }

        return now()->diffInDays($this->target_date, false);
    }

    public function getDaysOverdue()
    {
        if (! $this->isOverdue()) {
            return 0;
        }

        return now()->diffInDays($this->target_date, false);
    }

    public function markAsStarted($userId = null)
    {
        $this->status = 'in_progress';
        if ($userId) {
            $this->assigned_to = $userId;
        }
        $this->save();

        return $this;
    }

    public function markAsCompleted($notes = null)
    {
        $this->status = 'completed';
        $this->completed_at = now();
        if ($notes) {
            $this->notes = $notes;
        }
        $this->save();

        return $this;
    }

    public function cancel($reason = null)
    {
        $this->status = 'cancelled';
        if ($reason) {
            $this->notes = $reason;
        }
        $this->save();

        return $this;
    }

    public function isSuccessionAction()
    {
        return in_array($this->action_type, ['promotion', 'succession_pool', 'career_path']);
    }

    public function isDevelopmentAction()
    {
        return in_array($this->action_type, ['learning_path', 'assign_mentor', 'improvement_plan']);
    }

    public function getFormattedMetadata()
    {
        if (! $this->metadata) {
            return [];
        }

        return collect($this->metadata)->map(function ($value, $key) {
            return [
                'key' => ucfirst(str_replace('_', ' ', $key)),
                'value' => $value,
            ];
        })->values()->toArray();
    }

    // Static factory methods for common actions
    public static function createPromotion($reviewId, $targetRoleId, $assignedTo, $createdBy, $targetDate = null)
    {
        return self::create([
            'review_id' => $reviewId,
            'action_type' => 'promotion',
            'title' => 'Promotion Recommendation',
            'target_role_id' => $targetRoleId,
            'assigned_to' => $assignedTo,
            'created_by' => $createdBy,
            'target_date' => $targetDate ?? now()->addMonths(3),
            'priority' => 'high',
        ]);
    }

    public static function createLearningPathAssignment($reviewId, $learningPathId, $assignedTo, $createdBy, $targetDate = null)
    {
        return self::create([
            'review_id' => $reviewId,
            'action_type' => 'learning_path',
            'title' => 'Learning Path Assignment',
            'learning_path_id' => $learningPathId,
            'assigned_to' => $assignedTo,
            'created_by' => $createdBy,
            'target_date' => $targetDate ?? now()->addMonths(6),
            'priority' => 'medium',
        ]);
    }

    public static function createMentorAssignment($reviewId, $mentorId, $assignedTo, $createdBy, $targetDate = null)
    {
        return self::create([
            'review_id' => $reviewId,
            'action_type' => 'assign_mentor',
            'title' => 'Mentor Assignment',
            'mentor_id' => $mentorId,
            'assigned_to' => $assignedTo,
            'created_by' => $createdBy,
            'target_date' => $targetDate ?? now()->addWeeks(2),
            'priority' => 'medium',
        ]);
    }
}
