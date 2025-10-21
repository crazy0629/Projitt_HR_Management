<?php

namespace App\Models\Talent;

use App\Models\LearningPath\LearningPath;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pip extends Model
{
    use HasFactory;

protected $fillable = [
        'employee_id',
        'manager_id',
        'title',
        'description',
        'goals',
        'success_criteria',
        'learning_path_id',
        'mentor_id',
        'start_date',
        'end_date',
        'checkin_frequency',
        'status',
        'completion_notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'goals' => 'array',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function learningPath()
    {
        return $this->belongsTo(LearningPath::class, 'learning_path_id');
    }

    public function mentor()
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function checkins()
    {
        return $this->hasMany(PipCheckin::class, 'pip_id')->orderBy('checkin_date', 'desc');
    }

    public function recentCheckins()
    {
        return $this->hasMany(PipCheckin::class, 'pip_id')
            ->orderBy('checkin_date', 'desc')
            ->limit(5);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByMentor($query, $mentorId)
    {
        return $query->where('mentor_id', $mentorId);
    }

    public function scopeEndingSoon($query, $days = 30)
    {
        return $query->where('status', 'active')
            ->where('end_date', '<=', now()->addDays($days));
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'active')
            ->where('end_date', '<', now());
    }

    // State Methods
    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isPaused()
    {
        return $this->status === 'paused';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function canEdit()
    {
        return in_array($this->status, ['active', 'paused']);
    }

    public function canPause()
    {
        return $this->status === 'active';
    }

    public function canResume()
    {
        return $this->status === 'paused';
    }

    public function canComplete()
    {
        return in_array($this->status, ['active', 'paused']);
    }

    public function canCancel()
    {
        return in_array($this->status, ['active', 'paused']);
    }

    // Helper Methods
    public function getDaysRemaining()
    {
        if (! $this->isActive()) {
            return 0;
        }

        return max(0, now()->diffInDays($this->end_date, false));
    }

    public function getDaysElapsed()
    {
        return $this->start_date->diffInDays(now());
    }

    public function getTotalDuration()
    {
        return $this->start_date->diffInDays($this->end_date);
    }

    public function getProgressPercentage()
    {
        if ($this->isCompleted()) {
            return 100;
        }

        if (! $this->isActive()) {
            return 0;
        }

        $totalDays = $this->getTotalDuration();
        $elapsedDays = $this->getDaysElapsed();

        if ($totalDays <= 0) {
            return 0;
        }

        return min(100, round(($elapsedDays / $totalDays) * 100));
    }

    public function isOverdue()
    {
        return $this->isActive() && now() > $this->end_date;
    }

    public function isEndingSoon($days = 14)
    {
        return $this->isActive() && $this->getDaysRemaining() <= $days;
    }

    public function getStatusLabel()
    {
        return match ($this->status) {
            'active' => 'Active',
            'paused' => 'Paused',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => 'Unknown',
        };
    }

    public function getStatusColor()
    {
        if ($this->isOverdue()) {
            return 'danger';
        }

        return match ($this->status) {
            'active' => $this->isEndingSoon() ? 'warning' : 'primary',
            'paused' => 'secondary',
            'completed' => 'success',
            'cancelled' => 'dark',
            default => 'secondary',
        };
    }

    public function getFrequencyLabel()
    {
        return match ($this->checkin_frequency) {
            'weekly' => 'Weekly',
            'biweekly' => 'Bi-weekly',
            'monthly' => 'Monthly',
            default => 'Unknown',
        };
    }

    public function getNextCheckinDate()
    {
        $lastCheckin = $this->checkins()->first();

        if (! $lastCheckin) {
            return $this->start_date;
        }

        $lastDate = $lastCheckin->checkin_date;

        return match ($this->checkin_frequency) {
            'weekly' => $lastDate->addWeek(),
            'biweekly' => $lastDate->addWeeks(2),
            'monthly' => $lastDate->addMonth(),
            default => $lastDate,
        };
    }

    public function isCheckinDue()
    {
        if (! $this->isActive()) {
            return false;
        }

        return now() >= $this->getNextCheckinDate();
    }

    public function getCheckinCount()
    {
        return $this->checkins()->count();
    }

    public function getAverageRating()
    {
        $ratings = $this->checkins()->whereNotNull('rating')->pluck('rating');

        return $ratings->count() > 0 ? round($ratings->average(), 1) : null;
    }

    // Business Logic
    public function pause($reason = null)
    {
        if (! $this->canPause()) {
            throw new \Exception('PIP cannot be paused in current status: '.$this->status);
        }

        $this->status = 'paused';
        $this->save();

        $this->logActivity('paused', ['reason' => $reason]);

        return $this;
    }

    public function resume()
    {
        if (! $this->canResume()) {
            throw new \Exception('PIP cannot be resumed in current status: '.$this->status);
        }

        $this->status = 'active';
        $this->save();

        $this->logActivity('resumed');

        return $this;
    }

    public function complete($notes = null)
    {
        if (! $this->canComplete()) {
            throw new \Exception('PIP cannot be completed in current status: '.$this->status);
        }

        $this->status = 'completed';
        $this->completion_notes = $notes;
        $this->save();

        $this->logActivity('completed', ['notes' => $notes]);

        return $this;
    }

    public function cancel($reason = null)
    {
        if (! $this->canCancel()) {
            throw new \Exception('PIP cannot be cancelled in current status: '.$this->status);
        }

        $this->status = 'cancelled';
        $this->completion_notes = $reason;
        $this->save();

        $this->logActivity('cancelled', ['reason' => $reason]);

        return $this;
    }

public function addCheckin($data)
{
    return $this->checkins()->create([
        'checkin_date' => $data['checkin_date'] ?? now(),
        'summary' => $data['summary'],
        'status' => $data['status'] ?? 'on_track',
        'rating' => $data['rating'] ?? null,
        'goals_progress' => $data['goals_progress'] ?? [],
        'manager_notes' => $data['manager_notes'] ?? null,
        'next_steps' => $data['next_steps'] ?? null,
        'next_checkin_date' => $data['next_checkin_date'] ?? null,
        'created_by' => \Illuminate\Support\Facades\Auth::guard('sanctum')->id() ?? auth()->id(),
        'updated_by' => \Illuminate\Support\Facades\Auth::guard('sanctum')->id() ?? auth()->id(),
    ]);
}

    private function logActivity($action, $payload = [])
    {
        AuditLog::create([
            'actor_id' => \Illuminate\Support\Facades\Auth::guard('sanctum')->id() ?? auth()->id(),
            'entity_type' => 'Pip',
            'entity_id' => $this->id,
            'action' => $action,
            'payload_json' => array_merge($payload, [
                'status' => $this->status,
                'employee_id' => $this->employee_id,
            ]),
            'created_at' => now(),
        ]);
    }

    // Static Methods
    public static function createForEmployee($employeeId, $data)
    {
        return self::create([
            'employee_id' => $employeeId,
            'manager_id' => $data['manager_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'goals' => $data['goals'] ?? [],
            'success_criteria' => $data['success_criteria'] ?? null,
            'learning_path_id' => $data['learning_path_id'] ?? null,
            'mentor_id' => $data['mentor_id'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'checkin_frequency' => $data['checkin_frequency'] ?? 'weekly',
            'status' => 'active',
            'created_by' => \Illuminate\Support\Facades\Auth::guard('sanctum')->id() ?? auth()->id(),
        ]);
    }

    public static function getActivePips()
    {
        return self::active()
            ->with(['employee', 'mentor', 'learningPath'])
            ->get();
    }

    public static function getOverduePips()
    {
        return self::overdue()
            ->with(['employee', 'mentor'])
            ->get();
    }
}
