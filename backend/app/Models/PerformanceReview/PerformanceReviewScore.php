<?php
namespace App\Models\PerformanceReview;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceReviewScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_id',
        'cycle_id',
        'reviewee_id',
        'reviewer_id',
        'reviewer_name',
        'type',
        'scores',
        'average_score',
        'comments',
        'strengths',
        'opportunities',
        'is_anonymous',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'scores' => 'array',
        'average_score' => 'decimal:2',
        'is_anonymous' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($score) {
            if ($score->isDirty('scores') && $score->scores) {
                $score->average_score = collect($score->scores)->avg();
            }
        });
    }
    
    public function reviewee()
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }

    public function cycle()
    {
        return $this->belongsTo(PerformanceReviewCycle::class, 'cycle_id');
    }

    public function scopeByCycle($query, int $cycleId)
    {
        return $query->where('cycle_id', $cycleId);
    }

    public function scopeByReviewee($query, int $revieweeId)
    {
        return $query->where('reviewee_id', $revieweeId);
    }

    public function scopeByReviewer($query, int $reviewerId)
    {
        return $query->where('reviewer_id', $reviewerId);
    }

    // Relationships
    public function review()
    {
        return $this->belongsTo(PerformanceReview::class, 'review_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeAnonymous($query)
    {
        return $query->where('is_anonymous', true);
    }

    // Helper methods
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isInProgress()
    {
        return $this->status === 'in_progress';
    }

    public function getTypeLabel()
    {
        $labels = [
            'self' => 'Self Review',
            'manager' => 'Manager Review',
            'peer' => 'Peer Review',
            'direct_report' => 'Direct Report Review',
        ];

        return $labels[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }

    public function getFormattedScoresAttribute()
    {
        if (! $this->scores) {
            return [];
        }

        return collect($this->scores)->map(function ($score, $competency) {
            return [
                'competency' => $competency,
                'score' => number_format($score, 1),
                'formatted' => $competency.': '.number_format($score, 1).'/5.0',
            ];
        })->values()->toArray();
    }

    public function getFormattedAverageScoreAttribute()
    {
        return $this->average_score ? number_format($this->average_score, 1).'/5.0' : 'N/A';
    }

    public function getScoreForCompetency($competency)
    {
        return $this->scores[$competency] ?? null;
    }

    public function getHighestScoringCompetency()
    {
        if (! $this->scores) {
            return null;
        }

        $highest = collect($this->scores)->sortDesc()->first();
        $competency = collect($this->scores)->search($highest);

        return [
            'competency' => $competency,
            'score' => $highest,
        ];
    }

    public function getLowestScoringCompetency()
    {
        if (! $this->scores) {
            return null;
        }

        $lowest = collect($this->scores)->sort()->first();
        $competency = collect($this->scores)->search($lowest);

        return [
            'competency' => $competency,
            'score' => $lowest,
        ];
    }

    public function getDurationInDays()
    {
        if (! $this->started_at || ! $this->completed_at) {
            return null;
        }

        return $this->started_at->diffInDays($this->completed_at);
    }

    public function markAsStarted()
    {
        $this->status = 'in_progress';
        $this->started_at = now();
        $this->save();
    }

    public function markAsCompleted()
    {
        $this->status = 'completed';
        $this->completed_at = now();
        $this->save();

        // Update the parent review's progress
        $this->review->updateProgress();
    }
}
