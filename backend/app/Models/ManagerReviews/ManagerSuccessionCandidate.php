<?php

namespace App\Models\ManagerReviews;

use App\Models\LearningPath\LearningPath;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManagerSuccessionCandidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'succession_pool_id',
        'employee_user_id',
        'readiness',
        'readiness_score',
        'potential_rating',
        'performance_rating',
        'learning_path_id',
        'mentor_user_id',
        'status',
        'notes',
        'development_plan',
        'target_ready_date',
        'assessment_date',
        'source',
        'nominated_by_user_id',
        'meta',
    ];

    protected $casts = [
        'readiness_score' => 'decimal:2',
        'potential_rating' => 'decimal:2',
        'performance_rating' => 'decimal:2',
        'development_plan' => 'array',
        'target_ready_date' => 'datetime',
        'assessment_date' => 'datetime',
        'meta' => 'array',
    ];

    // Relationships
    public function successionPool(): BelongsTo
    {
        return $this->belongsTo(SuccessionPool::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_user_id');
    }

    public function learningPath(): BelongsTo
    {
        return $this->belongsTo(LearningPath::class);
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_user_id');
    }

    public function nominatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nominated_by_user_id');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeByReadiness(Builder $query, string $readiness): Builder
    {
        return $query->where('readiness', $readiness);
    }

    public function scopeReadyNow(Builder $query): Builder
    {
        return $query->where('readiness', 'ready_now');
    }

    public function scopeForPool(Builder $query, int $poolId): Builder
    {
        return $query->where('succession_pool_id', $poolId);
    }

    public function scopeForEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_user_id', $employeeId);
    }

    public function scopeBySource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }

    public function scopeHighPotential(Builder $query): Builder
    {
        return $query->where('potential_rating', '>=', 4.0);
    }

    public function scopeHighPerformer(Builder $query): Builder
    {
        return $query->where('performance_rating', '>=', 4.0);
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isReadyNow(): bool
    {
        return $this->readiness === 'ready_now';
    }

    public function isHighPotential(): bool
    {
        return $this->potential_rating >= 4.0;
    }

    public function isHighPerformer(): bool
    {
        return $this->performance_rating >= 4.0;
    }

    public function getReadinessTimeframe(): string
    {
        return match ($this->readiness) {
            'ready_now' => 'Ready Now',
            '3_6m' => '3-6 Months',
            '6_12m' => '6-12 Months',
            '12_24m' => '12-24 Months',
            default => 'Unknown'
        };
    }

    public function getReadinessColor(): string
    {
        return match ($this->readiness) {
            'ready_now' => '#10B981', // Green
            '3_6m' => '#3B82F6', // Blue
            '6_12m' => '#F59E0B', // Amber
            '12_24m' => '#6B7280', // Gray
            default => '#EF4444' // Red
        };
    }

    public function getOverallScore(): float
    {
        $weights = [
            'readiness_score' => 0.4,
            'potential_rating' => 0.3,
            'performance_rating' => 0.3,
        ];

        $score = 0;
        $totalWeight = 0;

        if ($this->readiness_score) {
            $score += $this->readiness_score * $weights['readiness_score'];
            $totalWeight += $weights['readiness_score'];
        }

        if ($this->potential_rating) {
            $score += $this->potential_rating * $weights['potential_rating'];
            $totalWeight += $weights['potential_rating'];
        }

        if ($this->performance_rating) {
            $score += $this->performance_rating * $weights['performance_rating'];
            $totalWeight += $weights['performance_rating'];
        }

        return $totalWeight > 0 ? round($score / $totalWeight, 2) : 0.0;
    }

    public function getReadinessScore(): int
    {
        return match ($this->readiness) {
            'ready_now' => 100,
            '3_6m' => 75,
            '6_12m' => 50,
            '12_24m' => 25,
            default => 0
        };
    }

    // Static helper methods
    public static function getReadinessOptions(): array
    {
        return [
            'ready_now' => 'Ready Now',
            '3_6m' => '3-6 Months',
            '6_12m' => '6-12 Months',
            '12_24m' => '12-24 Months',
        ];
    }

    public static function createCandidate(
        int $poolId,
        int $employeeId,
        string $readiness,
        int $nominatedById,
        string $source = 'manager',
        ?array $options = null
    ): self {
        return self::create([
            'succession_pool_id' => $poolId,
            'employee_user_id' => $employeeId,
            'readiness' => $readiness,
            'source' => $source,
            'nominated_by_user_id' => $nominatedById,
            'status' => 'active',
            'assessment_date' => now(),
            'learning_path_id' => $options['learning_path_id'] ?? null,
            'mentor_user_id' => $options['mentor_user_id'] ?? null,
            'notes' => $options['notes'] ?? null,
            'potential_rating' => $options['potential_rating'] ?? null,
            'performance_rating' => $options['performance_rating'] ?? null,
            'development_plan' => $options['development_plan'] ?? null,
            'meta' => $options['meta'] ?? null,
        ]);
    }

    public static function getCandidatesForManager(int $managerId): \Illuminate\Database\Eloquent\Collection
    {
        return self::active()
            ->whereHas('employee.teamMemberships', function ($query) use ($managerId) {
                $query->where('manager_user_id', $managerId)
                    ->where('status', 'active');
            })
            ->with(['successionPool.role', 'employee', 'learningPath', 'mentor'])
            ->orderBy('readiness')
            ->orderBy('potential_rating', 'desc')
            ->get();
    }

    public static function getTopCandidates(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return self::active()
            ->with(['successionPool.role', 'employee'])
            ->get()
            ->sortByDesc(function ($candidate) {
                return $candidate->getOverallScore();
            })
            ->take($limit);
    }
}
