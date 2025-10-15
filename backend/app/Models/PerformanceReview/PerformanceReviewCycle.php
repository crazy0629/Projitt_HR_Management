<?php

namespace App\Models\PerformanceReview;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PerformanceReviewCycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'period_start',
        'period_end',
        'frequency',
        'competencies',
        'assignments',
        'status',
        'employee_count',
        'completed_count',
        'completion_rate',
        'created_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'competencies' => 'array',
        'assignments' => 'array',
        'completion_rate' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $dates = [
        'period_start',
        'period_end',
        'created_at',
        'updated_at',
    ];

    // Auto-generate slug on creation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($cycle) {
            if (empty($cycle->slug)) {
                $cycle->slug = Str::slug($cycle->name);
            }
        });

        static::updating(function ($cycle) {
            if ($cycle->isDirty('name') && empty($cycle->slug)) {
                $cycle->slug = Str::slug($cycle->name);
            }
        });
    }

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviews()
    {
        return $this->hasMany(PerformanceReview::class, 'cycle_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCurrent($query)
    {
        $now = now();

        return $query->where('period_start', '<=', $now)
            ->where('period_end', '>=', $now)
            ->where('status', 'active');
    }

    // Helper methods
    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isCurrentPeriod()
    {
        $now = now();

        return $this->period_start <= $now && $this->period_end >= $now;
    }

    public function updateCompletionStats()
    {
        $this->employee_count = $this->reviews()->count();
        $this->completed_count = $this->reviews()->where('status', 'completed')->count();

        if ($this->employee_count > 0) {
            $this->completion_rate = ($this->completed_count / $this->employee_count) * 100;
        } else {
            $this->completion_rate = 0;
        }

        $this->save();
    }

    public function getFormattedPeriodAttribute()
    {
        return $this->period_start->format('M j, Y').' - '.$this->period_end->format('M j, Y');
    }

    public function getDurationInDaysAttribute()
    {
        return $this->period_start->diffInDays($this->period_end) + 1;
    }

    public function getCompetenciesListAttribute()
    {
        return implode(', ', $this->competencies ?? []);
    }

    public function getAssignmentTypesListAttribute()
    {
        $types = [
            'self_review' => 'Self Review',
            'manager_review' => 'Manager Review',
            'peer_review' => 'Peer Review',
            'direct_report' => 'Direct Report Review',
        ];

        return collect($this->assignments ?? [])
            ->map(fn ($assignment) => $types[$assignment] ?? ucfirst(str_replace('_', ' ', $assignment)))
            ->implode(', ');
    }
}
