<?php

namespace App\Models\ManagerReviews;

use App\Models\Role;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuccessionPool extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_id',
        'title',
        'description',
        'priority_level',
        'status',
        'target_fill_date',
        'business_impact',
        'replacement_risk',
        'created_by_user_id',
        'meta',
    ];

    protected $casts = [
        'target_fill_date' => 'datetime',
        'meta' => 'array',
    ];

    // Relationships
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(ManagerSuccessionCandidate::class);
    }

    public function activeCandidates(): HasMany
    {
        return $this->hasMany(ManagerSuccessionCandidate::class)
            ->where('status', 'active');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority_level', $priority);
    }

    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->where('priority_level', 'high');
    }

    public function scopeForRole(Builder $query, int $roleId): Builder
    {
        return $query->where('role_id', $roleId);
    }

    public function scopeByRisk(Builder $query, string $risk): Builder
    {
        return $query->where('replacement_risk', $risk);
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isHighPriority(): bool
    {
        return $this->priority_level === 'high';
    }

    public function isCritical(): bool
    {
        return $this->replacement_risk === 'critical';
    }

    public function getCandidateCount(): int
    {
        return $this->activeCandidates()->count();
    }

    public function getReadyCandidateCount(): int
    {
        return $this->activeCandidates()
            ->where('readiness', 'ready_now')
            ->count();
    }

    public function getCandidatesByReadiness(): array
    {
        $candidates = $this->activeCandidates()
            ->with('employee')
            ->get()
            ->groupBy('readiness');

        return [
            'ready_now' => $candidates->get('ready_now', collect())->count(),
            '3_6m' => $candidates->get('3_6m', collect())->count(),
            '6_12m' => $candidates->get('6_12m', collect())->count(),
            '12_24m' => $candidates->get('12_24m', collect())->count(),
        ];
    }

    public function getPriorityColor(): string
    {
        return match ($this->priority_level) {
            'high' => '#EF4444', // Red
            'medium' => '#F59E0B', // Amber
            'low' => '#10B981', // Green
            default => '#6B7280' // Gray
        };
    }

    public function getRiskColor(): string
    {
        return match ($this->replacement_risk) {
            'critical' => '#EF4444', // Red
            'high' => '#F59E0B', // Amber
            'medium' => '#3B82F6', // Blue
            'low' => '#10B981', // Green
            default => '#6B7280' // Gray
        };
    }

    public function getBusinessImpactDescription(): string
    {
        return match ($this->business_impact) {
            'critical' => 'Critical business functions would be severely impacted',
            'high' => 'Significant impact on team/department performance',
            'medium' => 'Moderate impact requiring backfill planning',
            'low' => 'Minimal immediate impact',
            default => 'Impact assessment needed'
        };
    }

    public function calculateSuccessionReadiness(): array
    {
        $readiness = $this->getCandidatesByReadiness();
        $totalCandidates = array_sum($readiness);

        if ($totalCandidates === 0) {
            return [
                'status' => 'at_risk',
                'description' => 'No succession candidates identified',
                'color' => '#EF4444',
                'score' => 0,
            ];
        }

        $score = 0;
        $score += $readiness['ready_now'] * 4;
        $score += $readiness['3_6m'] * 3;
        $score += $readiness['6_12m'] * 2;
        $score += $readiness['12_24m'] * 1;

        $normalizedScore = min(100, ($score / max(1, $totalCandidates)) * 25);

        return match (true) {
            $normalizedScore >= 80 => [
                'status' => 'well_prepared',
                'description' => 'Strong succession pipeline with ready candidates',
                'color' => '#10B981',
                'score' => $normalizedScore,
            ],
            $normalizedScore >= 60 => [
                'status' => 'adequate',
                'description' => 'Adequate succession planning with development needed',
                'color' => '#3B82F6',
                'score' => $normalizedScore,
            ],
            $normalizedScore >= 40 => [
                'status' => 'needs_development',
                'description' => 'Succession planning requires focus and development',
                'color' => '#F59E0B',
                'score' => $normalizedScore,
            ],
            default => [
                'status' => 'at_risk',
                'description' => 'High succession risk - immediate attention required',
                'color' => '#EF4444',
                'score' => $normalizedScore,
            ]
        };
    }

    // Static helper methods
    public static function getPriorityLevels(): array
    {
        return [
            'high' => 'High Priority',
            'medium' => 'Medium Priority',
            'low' => 'Low Priority',
        ];
    }

    public static function getRiskLevels(): array
    {
        return [
            'critical' => 'Critical Risk',
            'high' => 'High Risk',
            'medium' => 'Medium Risk',
            'low' => 'Low Risk',
        ];
    }

    public static function getBusinessImpactLevels(): array
    {
        return [
            'critical' => 'Critical Impact',
            'high' => 'High Impact',
            'medium' => 'Medium Impact',
            'low' => 'Low Impact',
        ];
    }

    public static function createPool(
        int $roleId,
        int $createdById,
        string $priority = 'medium',
        ?string $title = null,
        ?string $description = null,
        ?array $metadata = null
    ): self {
        $role = Role::find($roleId);

        return self::create([
            'role_id' => $roleId,
            'title' => $title ?? "Succession Pool for {$role->name}",
            'description' => $description,
            'priority_level' => $priority,
            'status' => 'active',
            'created_by_user_id' => $createdById,
            'meta' => $metadata,
        ]);
    }

    public static function getPoolsWithStats(): \Illuminate\Database\Eloquent\Collection
    {
        return self::active()
            ->with(['role', 'activeCandidates.employee'])
            ->get()
            ->map(function ($pool) {
                $pool->candidate_count = $pool->getCandidateCount();
                $pool->ready_candidates = $pool->getReadyCandidateCount();
                $pool->readiness_breakdown = $pool->getCandidatesByReadiness();
                $pool->succession_readiness = $pool->calculateSuccessionReadiness();

                return $pool;
            });
    }

    public static function getCriticalPools(): \Illuminate\Database\Eloquent\Collection
    {
        return self::active()
            ->where(function ($query) {
                $query->where('priority_level', 'high')
                    ->orWhere('replacement_risk', 'critical');
            })
            ->with(['role', 'activeCandidates'])
            ->get();
    }

    public static function getPoolsByDepartment(): array
    {
        return self::active()
            ->with(['role', 'activeCandidates'])
            ->get()
            ->groupBy('role.department')
            ->map(function ($pools) {
                return [
                    'total_pools' => $pools->count(),
                    'total_candidates' => $pools->sum(function ($pool) {
                        return $pool->activeCandidates->count();
                    }),
                    'high_priority_pools' => $pools->where('priority_level', 'high')->count(),
                    'pools' => $pools,
                ];
            })
            ->toArray();
    }
}
