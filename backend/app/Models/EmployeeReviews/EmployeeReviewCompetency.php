<?php

namespace App\Models\EmployeeReviews;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeReviewCompetency extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category',
        'weight',
        'evaluation_criteria',
        'measurement_scale',
        'is_required',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'evaluation_criteria' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'weight' => 'integer',
        'sort_order' => 'integer',
    ];

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeRequired(Builder $query): Builder
    {
        return $query->where('is_required', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Helper methods
    public function getScaleOptions(): array
    {
        return match ($this->measurement_scale) {
            '1-5' => [
                1 => 'Below Expectations',
                2 => 'Partially Meets Expectations',
                3 => 'Meets Expectations',
                4 => 'Exceeds Expectations',
                5 => 'Outstanding',
            ],
            '1-10' => array_combine(
                range(1, 10),
                array_map(fn ($i) => "Level $i", range(1, 10))
            ),
            'percentage' => [], // 0-100 scale
            default => []
        };
    }

    public function getMaxScore(): int
    {
        return match ($this->measurement_scale) {
            '1-5' => 5,
            '1-10' => 10,
            'percentage' => 100,
            default => 5
        };
    }

    public function calculateWeightedScore(float $rawScore): float
    {
        $maxScore = $this->getMaxScore();
        $normalizedScore = $rawScore / $maxScore; // 0-1 scale

        return $normalizedScore * $this->weight;
    }

    public function getRatingLabel(float $score): string
    {
        $maxScore = $this->getMaxScore();
        $percentage = ($score / $maxScore) * 100;

        return match (true) {
            $percentage >= 90 => 'Outstanding',
            $percentage >= 80 => 'Exceeds Expectations',
            $percentage >= 70 => 'Meets Expectations',
            $percentage >= 60 => 'Partially Meets Expectations',
            default => 'Below Expectations'
        };
    }

    public function getCategoryColor(): string
    {
        return match ($this->category) {
            'technical' => '#3B82F6', // Blue
            'behavioral' => '#10B981', // Green
            'leadership' => '#8B5CF6', // Purple
            'communication' => '#F59E0B', // Amber
            'teamwork' => '#EF4444', // Red
            default => '#6B7280' // Gray
        };
    }

    public function getCategoryIcon(): string
    {
        return match ($this->category) {
            'technical' => 'code',
            'behavioral' => 'user-check',
            'leadership' => 'users',
            'communication' => 'message-square',
            'teamwork' => 'users-2',
            default => 'star'
        };
    }

    // Static helper methods
    public static function getCategories(): array
    {
        return [
            'technical' => 'Technical Skills',
            'behavioral' => 'Behavioral Competencies',
            'leadership' => 'Leadership Skills',
            'communication' => 'Communication',
            'teamwork' => 'Teamwork & Collaboration',
        ];
    }

    public static function getMeasurementScales(): array
    {
        return [
            '1-5' => '5-Point Scale (1-5)',
            '1-10' => '10-Point Scale (1-10)',
            'percentage' => 'Percentage (0-100%)',
        ];
    }

    public static function getDefaultCompetencies(): array
    {
        return [
            [
                'name' => 'Technical Expertise',
                'description' => 'Demonstrates proficiency in job-specific technical skills and tools',
                'category' => 'technical',
                'weight' => 3,
                'evaluation_criteria' => [
                    'Applies technical knowledge effectively',
                    'Stays current with industry trends and technologies',
                    'Solves complex technical problems',
                    'Shares technical knowledge with team members',
                ],
                'measurement_scale' => '1-5',
                'is_required' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Problem Solving',
                'description' => 'Identifies issues and develops effective solutions',
                'category' => 'behavioral',
                'weight' => 2,
                'evaluation_criteria' => [
                    'Identifies root causes of problems',
                    'Develops creative and practical solutions',
                    'Implements solutions effectively',
                    'Learns from problem-solving experiences',
                ],
                'measurement_scale' => '1-5',
                'is_required' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Communication',
                'description' => 'Communicates clearly and effectively with all stakeholders',
                'category' => 'communication',
                'weight' => 2,
                'evaluation_criteria' => [
                    'Communicates ideas clearly and concisely',
                    'Actively listens to others',
                    'Adapts communication style to audience',
                    'Provides constructive feedback',
                ],
                'measurement_scale' => '1-5',
                'is_required' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Teamwork & Collaboration',
                'description' => 'Works effectively with team members and cross-functional partners',
                'category' => 'teamwork',
                'weight' => 2,
                'evaluation_criteria' => [
                    'Collaborates effectively with team members',
                    'Supports team goals and objectives',
                    'Shares knowledge and resources',
                    'Builds positive working relationships',
                ],
                'measurement_scale' => '1-5',
                'is_required' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Leadership & Initiative',
                'description' => 'Demonstrates leadership qualities and takes initiative',
                'category' => 'leadership',
                'weight' => 2,
                'evaluation_criteria' => [
                    'Takes initiative on projects and tasks',
                    'Influences and motivates others positively',
                    'Makes sound decisions under pressure',
                    'Mentors and develops team members',
                ],
                'measurement_scale' => '1-5',
                'is_required' => false,
                'sort_order' => 5,
            ],
        ];
    }
}
