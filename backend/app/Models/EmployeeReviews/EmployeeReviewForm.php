<?php

namespace App\Models\EmployeeReviews;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeReviewForm extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'sections',
        'competency_weights',
        'scoring_rules',
        'estimated_duration_minutes',
        'requires_manager_approval',
        'allows_self_review',
        'allows_peer_review',
        'allows_subordinate_review',
        'is_template',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'sections' => 'array',
        'competency_weights' => 'array',
        'scoring_rules' => 'array',
        'estimated_duration_minutes' => 'integer',
        'requires_manager_approval' => 'boolean',
        'allows_self_review' => 'boolean',
        'allows_peer_review' => 'boolean',
        'allows_subordinate_review' => 'boolean',
        'is_template' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeReviewAssignment::class, 'form_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(EmployeeReviewFormSubmission::class, 'form_id');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeTemplates(Builder $query): Builder
    {
        return $query->where('is_template', true);
    }

    public function scopeAllowsReviewType(Builder $query, string $reviewType): Builder
    {
        $field = match ($reviewType) {
            'self' => 'allows_self_review',
            'peer' => 'allows_peer_review',
            'subordinate' => 'allows_subordinate_review',
            default => 'allows_self_review'
        };

        return $query->where($field, true);
    }

    // Helper methods
    public function getEstimatedDurationFormatted(): string
    {
        $minutes = $this->estimated_duration_minutes;

        if ($minutes < 60) {
            return "{$minutes} minutes";
        }

        $hours = intval($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes === 0) {
            return "{$hours} hour".($hours > 1 ? 's' : '');
        }

        return "{$hours}h {$remainingMinutes}m";
    }

    public function getTotalSections(): int
    {
        return count($this->sections ?? []);
    }

    public function getTotalQuestions(): int
    {
        $total = 0;
        foreach ($this->sections ?? [] as $section) {
            $total += count($section['questions'] ?? []);
        }

        return $total;
    }

    public function getCompetencyWeightTotal(): float
    {
        return array_sum($this->competency_weights ?? []);
    }

    public function calculateOverallScore(array $competencyScores): float
    {
        if (empty($this->competency_weights) || empty($competencyScores)) {
            return 0.0;
        }

        $weightedSum = 0.0;
        $totalWeight = 0.0;

        foreach ($this->competency_weights as $competencyId => $weight) {
            if (isset($competencyScores[$competencyId])) {
                $weightedSum += $competencyScores[$competencyId] * $weight;
                $totalWeight += $weight;
            }
        }

        return $totalWeight > 0 ? $weightedSum / $totalWeight : 0.0;
    }

    public function getPerformanceLevel(float $score): string
    {
        $rules = $this->scoring_rules['performance_levels'] ?? [];

        // Default performance levels if not configured
        if (empty($rules)) {
            $rules = [
                'outstanding' => ['min' => 4.5, 'max' => 5.0],
                'exceeds' => ['min' => 4.0, 'max' => 4.49],
                'meets' => ['min' => 3.0, 'max' => 3.99],
                'below' => ['min' => 0.0, 'max' => 2.99],
            ];
        }

        foreach ($rules as $level => $range) {
            if ($score >= $range['min'] && $score <= $range['max']) {
                return $level;
            }
        }

        return 'unrated';
    }

    public function allowsReviewType(string $reviewType): bool
    {
        return match ($reviewType) {
            'self' => $this->allows_self_review,
            'peer' => $this->allows_peer_review,
            'subordinate' => $this->allows_subordinate_review,
            'manager' => true, // Manager reviews are always allowed
            default => false
        };
    }

    public function createFromTemplate(): self
    {
        if (! $this->is_template) {
            throw new \InvalidArgumentException('Cannot create from non-template form');
        }

        return self::create([
            'name' => $this->name.' (Copy)',
            'description' => $this->description,
            'type' => $this->type,
            'sections' => $this->sections,
            'competency_weights' => $this->competency_weights,
            'scoring_rules' => $this->scoring_rules,
            'estimated_duration_minutes' => $this->estimated_duration_minutes,
            'requires_manager_approval' => $this->requires_manager_approval,
            'allows_self_review' => $this->allows_self_review,
            'allows_peer_review' => $this->allows_peer_review,
            'allows_subordinate_review' => $this->allows_subordinate_review,
            'is_template' => false,
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);
    }

    // Static helper methods
    public static function getFormTypes(): array
    {
        return [
            'annual' => 'Annual Review',
            'quarterly' => 'Quarterly Review',
            'project' => 'Project Review',
            '360' => '360-Degree Review',
            'probation' => 'Probation Review',
            'exit' => 'Exit Interview',
        ];
    }

    public static function getDefaultSections(): array
    {
        return [
            [
                'title' => 'Performance & Goals',
                'description' => 'Evaluate performance against established goals and objectives',
                'questions' => [
                    [
                        'id' => 'goals_achievement',
                        'text' => 'How well did the employee achieve their goals this period?',
                        'type' => 'rating',
                        'scale' => '1-5',
                        'required' => true,
                    ],
                    [
                        'id' => 'goals_narrative',
                        'text' => 'Provide specific examples of goal achievement or areas where goals were not met',
                        'type' => 'long_text',
                        'required' => true,
                    ],
                ],
            ],
            [
                'title' => 'Core Competencies',
                'description' => 'Rate performance across key competency areas',
                'questions' => [
                    [
                        'id' => 'competency_ratings',
                        'text' => 'Rate performance on each competency',
                        'type' => 'competency_matrix',
                        'required' => true,
                    ],
                ],
            ],
            [
                'title' => 'Development & Growth',
                'description' => 'Assess development progress and future growth opportunities',
                'questions' => [
                    [
                        'id' => 'strengths',
                        'text' => 'What are the employee\'s key strengths?',
                        'type' => 'long_text',
                        'required' => true,
                    ],
                    [
                        'id' => 'development_areas',
                        'text' => 'What areas should the employee focus on for development?',
                        'type' => 'long_text',
                        'required' => true,
                    ],
                    [
                        'id' => 'career_aspirations',
                        'text' => 'What are the employee\'s career goals and aspirations?',
                        'type' => 'long_text',
                        'required' => false,
                    ],
                ],
            ],
        ];
    }

    public static function getDefaultScoringRules(): array
    {
        return [
            'performance_levels' => [
                'outstanding' => ['min' => 4.5, 'max' => 5.0, 'label' => 'Outstanding'],
                'exceeds' => ['min' => 4.0, 'max' => 4.49, 'label' => 'Exceeds Expectations'],
                'meets' => ['min' => 3.0, 'max' => 3.99, 'label' => 'Meets Expectations'],
                'below' => ['min' => 2.0, 'max' => 2.99, 'label' => 'Below Expectations'],
                'unsatisfactory' => ['min' => 0.0, 'max' => 1.99, 'label' => 'Unsatisfactory'],
            ],
            'weights' => [
                'goals' => 0.4,
                'competencies' => 0.5,
                'development' => 0.1,
            ],
            'calculation_method' => 'weighted_average',
        ];
    }
}
