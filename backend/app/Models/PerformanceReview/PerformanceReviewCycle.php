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
        'anonymous_peer_reviews',
        'allow_optional_text_feedback',
        'eligibility_criteria',
        'user_guide_path',
        'user_guide_name',
        'setup_status',
        'launched_at',
        'total_employees',
        'eligible_employees',
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
        'anonymous_peer_reviews' => 'boolean',
        'allow_optional_text_feedback' => 'boolean',
        'eligibility_criteria' => 'array',
        'launched_at' => 'datetime',
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

    public function reviewCompetencies()
    {
        return $this->hasMany(ReviewCompetency::class, 'cycle_id')->ordered();
    }

    public function activeCompetencies()
    {
        return $this->hasMany(ReviewCompetency::class, 'cycle_id')->active()->ordered();
    }

    public function questionImports()
    {
        return $this->hasMany(ReviewQuestionImport::class, 'cycle_id')->orderBy('created_at', 'desc');
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

    // Setup wizard helper methods
    public function isSetupIncomplete()
    {
        return $this->setup_status === 'incomplete';
    }

    public function hasCompetenciesAdded()
    {
        return in_array($this->setup_status, ['competencies_added', 'criteria_added', 'ready_to_launch']);
    }

    public function hasCriteriaAdded()
    {
        return in_array($this->setup_status, ['criteria_added', 'ready_to_launch']);
    }

    public function isReadyToLaunch()
    {
        return $this->setup_status === 'ready_to_launch';
    }

    public function isLaunched()
    {
        return $this->launched_at !== null;
    }

    public function updateSetupStatus($status)
    {
        $validStatuses = ['incomplete', 'competencies_added', 'criteria_added', 'ready_to_launch'];

        if (in_array($status, $validStatuses)) {
            $this->setup_status = $status;
            $this->save();
        }

        return $this;
    }

    public function markAsLaunched()
    {
        $this->launched_at = now();
        $this->status = 'active';
        $this->save();

        return $this;
    }

    public function getSetupProgress()
    {
        $steps = [
            'incomplete' => 0,
            'competencies_added' => 25,
            'criteria_added' => 75,
            'ready_to_launch' => 100,
        ];

        return $steps[$this->setup_status] ?? 0;
    }

    public function getCompetenciesCount()
    {
        return $this->reviewCompetencies()->count();
    }

    public function getActiveCriteriaCount()
    {
        return $this->activeCompetencies()
            ->with(['activeCriteria'])
            ->get()
            ->sum(function ($competency) {
                return $competency->activeCriteria->count();
            });
    }

    public function hasUserGuide()
    {
        return ! empty($this->user_guide_path);
    }

    public function getUserGuideUrl()
    {
        if ($this->hasUserGuide()) {
            return asset('storage/'.$this->user_guide_path);
        }

        return null;
    }

    public function deleteUserGuide()
    {
        if ($this->hasUserGuide()) {
            \Storage::delete($this->user_guide_path);
            $this->user_guide_path = null;
            $this->user_guide_name = null;
            $this->save();
        }

        return $this;
    }

    public function uploadUserGuide($file)
    {
        if ($this->hasUserGuide()) {
            $this->deleteUserGuide();
        }

        $fileName = $file->getClientOriginalName();
        $filePath = $file->store('user_guides');

        $this->user_guide_path = $filePath;
        $this->user_guide_name = $fileName;
        $this->save();

        return $this;
    }

    public function createDefaultCompetencies()
    {
        if ($this->reviewCompetencies()->count() === 0) {
            $competencies = ReviewCompetency::createDefault($this->id);

            foreach ($competencies as $competency) {
                ReviewCriteria::createDefaultForCompetency($competency->id, $competency->name);
            }

            $this->updateSetupStatus('criteria_added');

            return $competencies;
        }

        return [];
    }

    public function calculateEligibleEmployees($criteria = null)
    {
        $eligibilityCriteria = $criteria ?? $this->eligibility_criteria;

        if (empty($eligibilityCriteria)) {
            $this->eligible_employees = $this->total_employees;
            $this->save();

            return $this->total_employees;
        }

        // This would need to be implemented based on your user model structure
        // For now, return total employees as placeholder
        $this->eligible_employees = $this->total_employees;
        $this->save();

        return $this->eligible_employees;
    }

    public function generateReviewAssignments()
    {
        // This will be implemented in the service layer
        return [];
    }
}
