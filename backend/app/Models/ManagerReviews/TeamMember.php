<?php

namespace App\Models\ManagerReviews;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'employee_user_id',
        'reports_to_user_id',
        'is_primary',
        'effective_from',
        'effective_to',
        'role_in_team',
        'permissions',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'permissions' => 'array',
    ];

    // Relationships
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_user_id');
    }

    public function reportsTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reports_to_user_id');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('effective_to');
    }

    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    public function scopeEffectiveAt(Builder $query, Carbon $date): Builder
    {
        return $query->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>', $date);
            });
    }

    public function scopeForEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_user_id', $employeeId);
    }

    public function scopeReportsTo(Builder $query, int $managerId): Builder
    {
        return $query->where('reports_to_user_id', $managerId);
    }

    // Helper methods
    public function isActive(): bool
    {
        return is_null($this->effective_to);
    }

    public function isEffectiveAt(Carbon $date): bool
    {
        return $this->effective_from <= $date &&
               (is_null($this->effective_to) || $this->effective_to > $date);
    }

    public function getDuration(): ?int
    {
        $startDate = $this->effective_from;
        $endDate = $this->effective_to ?? now();

        return $startDate->diffInDays($endDate);
    }

    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];

        return in_array($permission, $permissions);
    }

    public function addPermission(string $permission): self
    {
        $permissions = $this->permissions ?? [];
        if (! in_array($permission, $permissions)) {
            $permissions[] = $permission;
            $this->update(['permissions' => $permissions]);
        }

        return $this;
    }

    public function removePermission(string $permission): self
    {
        $permissions = $this->permissions ?? [];
        $permissions = array_filter($permissions, fn ($p) => $p !== $permission);
        $this->update(['permissions' => array_values($permissions)]);

        return $this;
    }

    public function getManagerChain(): array
    {
        $chain = [];
        $currentMember = $this;

        while ($currentMember && $currentMember->reports_to_user_id) {
            $manager = User::find($currentMember->reports_to_user_id);
            if ($manager) {
                $chain[] = $manager;

                // Find the next level up
                $managerTeamMember = self::active()
                    ->forEmployee($manager->id)
                    ->primary()
                    ->first();

                $currentMember = $managerTeamMember;
            } else {
                break;
            }
        }

        return $chain;
    }

    public function getDirectReports(): \Illuminate\Database\Eloquent\Collection
    {
        return self::active()
            ->reportsTo($this->employee_user_id)
            ->with('employee')
            ->get();
    }

    public function getAllSubordinates(): \Illuminate\Database\Eloquent\Collection
    {
        $subordinates = collect();
        $directReports = $this->getDirectReports();

        foreach ($directReports as $report) {
            $subordinates->push($report);
            $subordinates = $subordinates->merge($report->getAllSubordinates());
        }

        return $subordinates;
    }

    public function transferTo(int $newTeamId, int $newReportsToId): self
    {
        // End current membership
        $this->update(['effective_to' => now()->toDateString()]);

        // Create new membership
        return self::create([
            'team_id' => $newTeamId,
            'employee_user_id' => $this->employee_user_id,
            'reports_to_user_id' => $newReportsToId,
            'is_primary' => $this->is_primary,
            'effective_from' => now()->toDateString(),
            'role_in_team' => $this->role_in_team,
            'permissions' => $this->permissions,
        ]);
    }

    // Static helper methods
    public static function getTeamMembersForManager(int $managerId): \Illuminate\Database\Eloquent\Collection
    {
        return self::active()
            ->reportsTo($managerId)
            ->with(['employee', 'team'])
            ->get();
    }

    public static function getOrganizationChart(int $rootManagerId): array
    {
        $chart = [];
        $directReports = self::getTeamMembersForManager($rootManagerId);

        foreach ($directReports as $report) {
            $chart[] = [
                'employee' => $report->employee,
                'team' => $report->team,
                'role_in_team' => $report->role_in_team,
                'subordinates' => self::getOrganizationChart($report->employee_user_id),
            ];
        }

        return $chart;
    }

    public static function findByEmployee(int $employeeId, bool $primaryOnly = true): ?self
    {
        $query = self::active()->forEmployee($employeeId);

        if ($primaryOnly) {
            $query->primary();
        }

        return $query->first();
    }
}
