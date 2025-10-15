<?php

namespace App\Models\LearningPath;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningPathAssignment extends Model
{
    use HasFactory;

    protected $table = 'learning_path_assignments';

    protected $fillable = [
        'learning_path_id',
        'employee_id',
        'status',
        'progress_percentage',
        'assigned_at',
        'started_at',
        'completed_at',
        'due_date',
        'assigned_by',
        'notes',
        'completion_data',
    ];

    protected $casts = [
        'progress_percentage' => 'decimal:2',
        'completion_data' => 'array',
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'due_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $dates = [
        'assigned_at',
        'started_at',
        'completed_at',
        'due_date',
        'created_at',
        'updated_at',
    ];

    // Relationships
    public function learningPath()
    {
        return $this->belongsTo(LearningPath::class, 'learning_path_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
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
        return $query->where('status', 'overdue')
            ->orWhere(function ($q) {
                $q->whereIn('status', ['assigned', 'in_progress'])
                    ->where('due_date', '<', now());
            });
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    // Helper methods
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isOverdue()
    {
        return $this->due_date && $this->due_date->isPast() && ! $this->isCompleted();
    }

    public function markAsStarted()
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'progress_percentage' => 100.00,
        ]);
    }

    public function updateProgress($percentage)
    {
        $this->update([
            'progress_percentage' => min(100, max(0, $percentage)),
            'status' => $percentage >= 100 ? 'completed' : 'in_progress',
            'completed_at' => $percentage >= 100 ? now() : null,
        ]);
    }
}
