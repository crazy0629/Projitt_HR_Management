<?php

namespace App\Models\ManagerReviews;

use App\Models\OrgUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'manager_user_id',
        'org_unit_id',
        'description',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'org_unit_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function activeMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class)->where('effective_to', null);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForManager(Builder $query, int $managerId): Builder
    {
        return $query->where('manager_user_id', $managerId);
    }

    // Helper methods
    public function getMemberCount(): int
    {
        return $this->activeMembers()->count();
    }

    public function getDirectReports(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->activeMembers()
            ->where('reports_to_user_id', $this->manager_user_id)
            ->with('employee')
            ->get();
    }

    public function getAllTeamMembers(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->activeMembers()->with('employee')->get();
    }

    public function getTeamHierarchy(): array
    {
        $members = $this->getAllTeamMembers();
        $hierarchy = [];

        foreach ($members as $member) {
            $managerId = $member->reports_to_user_id;
            if (! isset($hierarchy[$managerId])) {
                $hierarchy[$managerId] = [];
            }
            $hierarchy[$managerId][] = $member;
        }

        return $hierarchy;
    }

    public function isManager(int $userId): bool
    {
        return $this->manager_user_id === $userId;
    }

    public function isMember(int $userId): bool
    {
        return $this->activeMembers()
            ->where('employee_user_id', $userId)
            ->exists();
    }

    public function addMember(
        int $employeeId,
        int $reportsToId,
        bool $isPrimary = true,
        ?string $roleInTeam = null,
        ?array $permissions = null
    ): TeamMember {
        return TeamMember::create([
            'team_id' => $this->id,
            'employee_user_id' => $employeeId,
            'reports_to_user_id' => $reportsToId,
            'is_primary' => $isPrimary,
            'effective_from' => now()->toDateString(),
            'role_in_team' => $roleInTeam,
            'permissions' => $permissions,
        ]);
    }

    public function removeMember(int $employeeId): bool
    {
        return $this->activeMembers()
            ->where('employee_user_id', $employeeId)
            ->update(['effective_to' => now()->toDateString()]);
    }

    public function getTeamPerformanceMetrics(?int $cycleId = null): array
    {
        // This would integrate with performance review data
        $members = $this->getAllTeamMembers()->pluck('employee_user_id');

        // Placeholder for performance metrics calculation
        return [
            'total_members' => $members->count(),
            'reviews_completed' => 0, // Would query performance reviews
            'average_score' => 0.0,
            'high_performers' => 0,
            'developing_members' => 0,
        ];
    }

    public function getUpcomingReviewDeadlines(): array
    {
        // This would integrate with review assignments
        return [
            'overdue' => 0,
            'due_this_week' => 0,
            'due_next_week' => 0,
        ];
    }

    // Static helper methods
    public static function getManagerTeams(int $managerId): \Illuminate\Database\Eloquent\Collection
    {
        return self::active()
            ->forManager($managerId)
            ->with(['members.employee', 'orgUnit'])
            ->get();
    }

    public static function getTeamByEmployee(int $employeeId): ?self
    {
        $teamMember = TeamMember::where('employee_user_id', $employeeId)
            ->where('is_primary', true)
            ->whereNull('effective_to')
            ->first();

        return $teamMember ? $teamMember->team : null;
    }
}
