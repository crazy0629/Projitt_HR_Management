<?php

namespace App\Models\PerformanceReview;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'cycle_id',
        'employee_id',
        'employee_name',
        'employee_email',
        'department_name',
        'final_score',
        'status',
        'progress',
        'total_reviewers',
        'completed_reviewers',
        'ai_summary',
        'potential_status',
        'due_date',
        'completed_at',
    ];

    protected $casts = [
        'final_score' => 'decimal:2',
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $dates = [
        'due_date',
        'completed_at',
        'created_at',
        'updated_at',
    ];

    // Relationships
    public function cycle()
    {
        return $this->belongsTo(PerformanceReviewCycle::class, 'cycle_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function scores()
    {
        return $this->hasMany(PerformanceReviewScore::class, 'review_id');
    }

    public function feedback()
    {
        return $this->hasOne(PerformanceReviewFeedback::class, 'review_id');
    }

    public function actions()
    {
        return $this->hasMany(PerformanceAction::class, 'review_id');
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

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
            ->orWhere(function ($q) {
                $q->where('due_date', '<', now())
                    ->whereIn('status', ['pending', 'in_progress']);
            });
    }

    public function scopeHighPerformers($query)
    {
        return $query->where('final_score', '>=', 4.0)
            ->where('status', 'completed');
    }

    public function scopeByPotentialStatus($query, $status)
    {
        return $query->where('potential_status', $status);
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->where('department_name', $department);
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

    public function isOverdue()
    {
        return $this->status === 'overdue' ||
               ($this->due_date && $this->due_date < now() && ! $this->isCompleted());
    }

    public function calculateFinalScore()
    {
        $scores = $this->scores()->where('status', 'completed')->get();

        if ($scores->isEmpty()) {
            return null;
        }

        // Weight different reviewer types
        $weights = [
            'manager' => 0.5,
            'self' => 0.2,
            'peer' => 0.2,
            'direct_report' => 0.1,
        ];

        $weightedSum = 0;
        $totalWeight = 0;

        foreach ($scores as $score) {
            $weight = $weights[$score->type] ?? 0.25;
            $weightedSum += $score->average_score * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? round($weightedSum / $totalWeight, 2) : null;
    }

    public function updateProgress()
    {
        $this->completed_reviewers = $this->scores()->where('status', 'completed')->count();

        if ($this->total_reviewers > 0) {
            $this->progress = round(($this->completed_reviewers / $this->total_reviewers) * 100);
        } else {
            $this->progress = 0;
        }

        // Update status based on progress
        if ($this->progress === 100) {
            $this->status = 'completed';
            $this->completed_at = now();
            $this->final_score = $this->calculateFinalScore();
        } elseif ($this->progress > 0) {
            $this->status = 'in_progress';
        }

        $this->save();
    }

    public function getFormattedFinalScoreAttribute()
    {
        return $this->final_score ? number_format($this->final_score, 1).'/5.0' : 'N/A';
    }

    public function getProgressPercentageAttribute()
    {
        return $this->progress.'%';
    }

    public function getPotentialStatusLabelAttribute()
    {
        $labels = [
            'developing' => 'Developing',
            'solid' => 'Solid Performer',
            'ready' => 'Ready for Growth',
            'high_potential' => 'High Potential',
        ];

        return $labels[$this->potential_status] ?? 'Not Assessed';
    }

    public function getDaysOverdueAttribute()
    {
        if (! $this->due_date || $this->isCompleted()) {
            return 0;
        }

        return max(0, now()->diffInDays($this->due_date, false));
    }

    public function getSelfReviewScore()
    {
        return $this->scores()->where('type', 'self')->first();
    }

    public function getManagerReviewScore()
    {
        return $this->scores()->where('type', 'manager')->first();
    }

    public function getPeerReviewScores()
    {
        return $this->scores()->where('type', 'peer')->get();
    }

    public function getAveragePeerScore()
    {
        $peerScores = $this->getPeerReviewScores();

        if ($peerScores->isEmpty()) {
            return null;
        }

        return round($peerScores->avg('average_score'), 2);
    }
}
