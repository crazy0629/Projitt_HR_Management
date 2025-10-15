<?php

namespace App\Services\Talent;

use App\Models\Role\Role;
use App\Models\Talent\SuccessionCandidate;
use App\Models\Talent\SuccessionRole;
use Illuminate\Support\Facades\DB;

class SuccessionPlanningService
{
    public function createSuccessionRole($roleId, $incumbentId, $data)
    {
        return SuccessionRole::createForRole($roleId, $incumbentId, $data);
    }

    public function addSuccessionCandidate($successionRoleId, $employeeId, $data)
    {
        return SuccessionCandidate::createForEmployee($employeeId, $successionRoleId, $data);
    }

    public function updateCandidateReadiness($candidateId, $readiness, $targetDate = null)
    {
        $candidate = SuccessionCandidate::findOrFail($candidateId);

        return $candidate->updateReadiness($readiness, $targetDate);
    }

    public function assignLearningPath($candidateId, $learningPathId)
    {
        $candidate = SuccessionCandidate::findOrFail($candidateId);

        return $candidate->assignLearningPath($learningPathId);
    }

    public function getSuccessionPlan($roleId = null)
    {
        $query = SuccessionRole::with([
            'role',
            'incumbent',
            'candidates.employee',
            'candidates.learningPath',
        ])->active();

        if ($roleId) {
            $query->where('role_id', $roleId);
        }

        return $query->get()->map(function ($successionRole) {
            return [
                'role' => $successionRole->role,
                'incumbent' => $successionRole->incumbent,
                'criticality' => $successionRole->criticality,
                'risk_level' => $successionRole->risk_level,
                'succession_health' => $successionRole->getSuccessionHealth(),
                'candidates' => $successionRole->candidates->map(function ($candidate) {
                    return [
                        'employee' => $candidate->employee,
                        'readiness' => $candidate->readiness,
                        'readiness_progress' => $candidate->getReadinessProgress(),
                        'learning_path' => $candidate->learningPath,
                        'target_ready_date' => $candidate->target_ready_date,
                        'strengths' => $candidate->getStrengthsList(),
                        'development_areas' => $candidate->getDevelopmentAreasList(),
                    ];
                }),
            ];
        });
    }

    public function getSuccessionMetrics()
    {
        $totalRoles = SuccessionRole::active()->count();
        $rolesWithCandidates = SuccessionRole::active()->has('candidates')->count();
        $highRiskRoles = SuccessionRole::active()->highRisk()->count();
        $readyCandidates = SuccessionCandidate::active()->ready()->count();

        $healthDistribution = SuccessionRole::active()->get()->groupBy(function ($role) {
            return $role->getSuccessionHealth();
        })->map->count();

        return [
            'total_succession_roles' => $totalRoles,
            'roles_with_candidates' => $rolesWithCandidates,
            'coverage_percentage' => $totalRoles > 0 ? round(($rolesWithCandidates / $totalRoles) * 100, 1) : 0,
            'high_risk_roles' => $highRiskRoles,
            'ready_candidates' => $readyCandidates,
            'succession_health' => $healthDistribution,
            'average_candidates_per_role' => $totalRoles > 0 ? round(SuccessionCandidate::active()->count() / $totalRoles, 1) : 0,
        ];
    }

    public function getEmployeeSuccessionOpportunities($employeeId)
    {
        return SuccessionCandidate::byEmployee($employeeId)
            ->active()
            ->with(['successionRole.role', 'learningPath'])
            ->get()
            ->map(function ($candidate) {
                return [
                    'target_role' => $candidate->successionRole->role,
                    'readiness' => $candidate->readiness,
                    'readiness_label' => $candidate->getReadinessLabel(),
                    'progress_percentage' => $candidate->getReadinessProgress(),
                    'learning_path' => $candidate->learningPath,
                    'target_ready_date' => $candidate->target_ready_date,
                    'days_to_readiness' => $candidate->getDaysToReadiness(),
                ];
            });
    }

    public function getCriticalRoleGaps()
    {
        return SuccessionRole::active()
            ->with(['role', 'incumbent', 'candidates'])
            ->get()
            ->filter(function ($role) {
                return ! $role->hasAdequateSuccession();
            })
            ->map(function ($role) {
                return [
                    'role' => $role->role,
                    'incumbent' => $role->incumbent,
                    'criticality' => $role->criticality,
                    'risk_level' => $role->risk_level,
                    'candidate_count' => $role->getCandidateCount(),
                    'ready_candidate_count' => $role->getReadyCandidateCount(),
                    'succession_health' => $role->getSuccessionHealth(),
                    'replacement_timeline' => $role->replacement_timeline,
                ];
            });
    }

    public function promoteCandidate($candidateId, $note = null)
    {
        DB::beginTransaction();

        try {
            $candidate = SuccessionCandidate::findOrFail($candidateId);
            $candidate->promoteToReady($note);

            // If this was their target role and they're now ready,
            // consider creating a promotion request
            if ($candidate->isReady() && $candidate->target_role_id) {
                // This could trigger an automatic promotion workflow
                // For now, just log it
                \Log::info('Succession candidate ready for promotion', [
                    'candidate_id' => $candidateId,
                    'employee_id' => $candidate->employee_id,
                    'target_role_id' => $candidate->target_role_id,
                ]);
            }

            DB::commit();

            return $candidate->fresh();

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function benchmarkSuccessionReadiness()
    {
        $candidates = SuccessionCandidate::active()->with(['employee', 'successionRole.role'])->get();

        return $candidates->groupBy('readiness')->map(function ($group) {
            return [
                'count' => $group->count(),
                'percentage' => round(($group->count() / $candidates->count()) * 100, 1),
                'average_progress' => round($group->avg(function ($candidate) {
                    return $candidate->getReadinessProgress();
                }), 1),
            ];
        });
    }
}
