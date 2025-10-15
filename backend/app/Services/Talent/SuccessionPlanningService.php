<?php

namespace App\Services\Talent;

use App\Models\Role\Role;
use App\Models\Talent\SuccessionCandidate;
use App\Models\Talent\SuccessionRole;

class SuccessionPlanningService
{
    /**
     * Create succession role for critical position
     */
    public function createSuccessionRole($roleId, $data)
    {
        $role = Role::findOrFail($roleId);

        return SuccessionRole::createForRole($roleId, $data['incumbent_id'] ?? null, $data);
    }

    /**
     * Add candidate to succession pool
     */
    public function addSuccessionCandidate($successionRoleId, $employeeId, $data)
    {
        // Validate employee isn't already a candidate for this role
        $existing = SuccessionCandidate::where('succession_role_id', $successionRoleId)
            ->where('employee_id', $employeeId)
            ->where('is_active', true)
            ->first();

        if ($existing) {
            throw new \Exception('Employee is already a succession candidate for this role');
        }

        return SuccessionCandidate::createForEmployee($employeeId, $successionRoleId, $data);
    }

    /**
     * Update candidate readiness level
     */
    public function updateCandidateReadiness($candidateId, $readiness, $targetDate = null)
    {
        $candidate = SuccessionCandidate::findOrFail($candidateId);

        return $candidate->updateReadiness($readiness, $targetDate);
    }

    /**
     * Get succession dashboard data
     */
    public function getSuccessionDashboard()
    {
        $criticalRoles = SuccessionRole::active()->with(['role', 'incumbent', 'candidates'])->get();

        $stats = [
            'total_critical_roles' => $criticalRoles->count(),
            'high_risk_roles' => $criticalRoles->where('risk_level', 'high')->count(),
            'roles_without_successors' => $criticalRoles->filter(fn ($role) => ! $role->hasAdequateSuccession())->count(),
            'ready_candidates' => SuccessionCandidate::ready()->count(),
            'developing_candidates' => SuccessionCandidate::developing()->count(),
        ];

        $healthDistribution = $criticalRoles->groupBy(fn ($role) => $role->getSuccessionHealth())
            ->map(fn ($group) => $group->count());

        return [
            'stats' => $stats,
            'health_distribution' => $healthDistribution,
            'high_risk_roles' => SuccessionRole::getHighRiskRoles(),
            'ready_candidates_by_role' => $this->getReadyCandidatesByRole(),
        ];
    }

    /**
     * Get succession plan for specific role
     */
    public function getSuccessionPlan($roleId)
    {
        $successionRole = SuccessionRole::where('role_id', $roleId)->with([
            'role',
            'incumbent',
            'candidates' => function ($q) {
                $q->active()->with(['employee', 'learningPath']);
            },
        ])->first();

        if (! $successionRole) {
            return null;
        }

        $candidates = $successionRole->candidates->groupBy('readiness');

        return [
            'succession_role' => $successionRole,
            'candidates_by_readiness' => $candidates,
            'succession_health' => $successionRole->getSuccessionHealth(),
            'recommendations' => $this->getSuccessionRecommendations($successionRole),
        ];
    }

    /**
     * Generate succession recommendations
     */
    public function getSuccessionRecommendations($successionRole)
    {
        $recommendations = [];

        $readyCount = $successionRole->getReadyCandidateCount();
        $developingCount = $successionRole->getDevelopingCandidateCount();

        if ($readyCount === 0) {
            $recommendations[] = [
                'type' => 'urgent',
                'message' => 'No ready successors identified. Consider promoting developing candidates or external hiring.',
                'action' => 'identify_ready_candidates',
            ];
        }

        if ($readyCount < 2 && $successionRole->criticality === 'high') {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'High-criticality role should have at least 2 ready successors.',
                'action' => 'develop_additional_candidates',
            ];
        }

        if ($developingCount === 0) {
            $recommendations[] = [
                'type' => 'info',
                'message' => 'Consider adding developing candidates to build succession pipeline.',
                'action' => 'add_developing_candidates',
            ];
        }

        return $recommendations;
    }

    /**
     * Assign learning path to succession candidate
     */
    public function assignLearningPath($candidateId, $learningPathId)
    {
        $candidate = SuccessionCandidate::findOrFail($candidateId);

        return $candidate->assignLearningPath($learningPathId);
    }

    /**
     * Update development plan for candidate
     */
    public function updateDevelopmentPlan($candidateId, $plan)
    {
        $candidate = SuccessionCandidate::findOrFail($candidateId);

        return $candidate->updateDevelopmentPlan($plan);
    }

    /**
     * Get succession analytics
     */
    public function getSuccessionAnalytics($filters = [])
    {
        $query = SuccessionRole::active();

        if (! empty($filters['department'])) {
            $query->byDepartment($filters['department']);
        }

        $roles = $query->with(['candidates'])->get();

        $analytics = [
            'succession_coverage' => [
                'excellent' => $roles->filter(fn ($r) => $r->getSuccessionHealth() === 'excellent')->count(),
                'good' => $roles->filter(fn ($r) => $r->getSuccessionHealth() === 'good')->count(),
                'fair' => $roles->filter(fn ($r) => $r->getSuccessionHealth() === 'fair')->count(),
                'poor' => $roles->filter(fn ($r) => $r->getSuccessionHealth() === 'poor')->count(),
                'critical' => $roles->filter(fn ($r) => $r->getSuccessionHealth() === 'critical')->count(),
            ],
            'readiness_distribution' => SuccessionCandidate::active()
                ->selectRaw('readiness, COUNT(*) as count')
                ->groupBy('readiness')
                ->pluck('count', 'readiness'),
            'development_progress' => $this->getDevelopmentProgress(),
        ];

        return $analytics;
    }

    // Private helper methods
    private function getReadyCandidatesByRole()
    {
        return SuccessionCandidate::ready()
            ->with(['employee', 'successionRole.role'])
            ->get()
            ->groupBy('succession_role_id')
            ->map(function ($candidates) {
                return [
                    'role_name' => $candidates->first()->successionRole->role->name,
                    'candidates' => $candidates->map(function ($candidate) {
                        return [
                            'id' => $candidate->id,
                            'employee_name' => $candidate->employee->name,
                            'target_ready_date' => $candidate->target_ready_date,
                        ];
                    }),
                ];
            });
    }

    private function getDevelopmentProgress()
    {
        $candidates = SuccessionCandidate::developing()->with(['learningPath'])->get();

        return $candidates->map(function ($candidate) {
            return [
                'candidate_id' => $candidate->id,
                'employee_name' => $candidate->employee->name,
                'readiness_progress' => $candidate->getReadinessProgress(),
                'learning_path_progress' => $candidate->getLearningPathProgress(),
                'days_to_readiness' => $candidate->getDaysToReadiness(),
            ];
        });
    }
}
