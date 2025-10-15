<?php

namespace App\Services\PerformanceReview;

use App\Models\PerformanceReview\PerformanceReview;
use App\Models\PerformanceReview\PerformanceReviewCycle;
use App\Models\User\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReviewAssignmentService
{
    public function __construct()
    {
        //
    }

    /**
     * Generate review assignments for a cycle based on eligibility criteria
     */
    public function generateAssignments(PerformanceReviewCycle $cycle)
    {
        try {
            DB::beginTransaction();

            // Get eligible employees
            $eligibleEmployees = $this->getEligibleEmployees($cycle);

            if ($eligibleEmployees->isEmpty()) {
                throw new \Exception('No eligible employees found for this cycle');
            }

            $assignmentCount = 0;
            $errors = [];

            foreach ($eligibleEmployees as $employee) {
                try {
                    $this->createReviewForEmployee($cycle, $employee);
                    $assignmentCount++;
                } catch (\Exception $e) {
                    $errors[] = "Employee {$employee->id}: ".$e->getMessage();
                    Log::warning('Review assignment failed', [
                        'cycle_id' => $cycle->id,
                        'employee_id' => $employee->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Update cycle statistics
            $cycle->total_employees = $eligibleEmployees->count();
            $cycle->eligible_employees = $eligibleEmployees->count();
            $cycle->employee_count = $assignmentCount;
            $cycle->save();

            // Mark cycle as launched if assignments were created
            if ($assignmentCount > 0) {
                $cycle->markAsLaunched();
            }

            DB::commit();

            return [
                'success' => true,
                'assignments_created' => $assignmentCount,
                'total_eligible' => $eligibleEmployees->count(),
                'errors' => $errors,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Assignment generation failed', [
                'cycle_id' => $cycle->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get eligible employees based on cycle criteria
     */
    protected function getEligibleEmployees(PerformanceReviewCycle $cycle)
    {
        $query = User::query();

        // Apply eligibility criteria if set
        if (! empty($cycle->eligibility_criteria)) {
            $criteria = $cycle->eligibility_criteria;

            // Department filter
            if (! empty($criteria['departments'])) {
                $query->whereIn('department_id', $criteria['departments']);
            }

            // Role filter
            if (! empty($criteria['roles'])) {
                $query->whereIn('role_id', $criteria['roles']);
            }

            // Employment type filter
            if (! empty($criteria['employment_types'])) {
                $query->whereIn('employment_type', $criteria['employment_types']);
            }

            // Tenure filter (minimum months of employment)
            if (! empty($criteria['min_tenure_months'])) {
                $query->whereRaw('DATEDIFF(NOW(), hired_date) >= ?', [$criteria['min_tenure_months'] * 30]);
            }

            // Status filter
            if (! empty($criteria['status'])) {
                $query->whereIn('status', $criteria['status']);
            } else {
                // Default to active employees only
                $query->where('status', 'active');
            }

            // Exclude specific users
            if (! empty($criteria['excluded_users'])) {
                $query->whereNotIn('id', $criteria['excluded_users']);
            }

            // Include specific users (overrides other filters)
            if (! empty($criteria['included_users'])) {
                $query->orWhereIn('id', $criteria['included_users']);
            }
        } else {
            // Default criteria: active employees only
            $query->where('status', 'active');
        }

        return $query->get();
    }

    /**
     * Create a performance review for an employee
     */
    protected function createReviewForEmployee(PerformanceReviewCycle $cycle, User $employee)
    {
        // Check if review already exists
        $existingReview = PerformanceReview::where('cycle_id', $cycle->id)
            ->where('employee_id', $employee->id)
            ->first();

        if ($existingReview) {
            throw new \Exception('Review already exists for this employee');
        }

        // Determine reviewers based on assignment types
        $reviewers = $this->determineReviewers($cycle, $employee);

        // Create the performance review
        $review = PerformanceReview::create([
            'cycle_id' => $cycle->id,
            'employee_id' => $employee->id,
            'manager_id' => $employee->manager_id,
            'reviewers' => $reviewers,
            'status' => 'pending',
            'due_date' => $cycle->period_end,
            'self_review_required' => in_array('self_review', $cycle->assignments ?? []),
            'manager_review_required' => in_array('manager_review', $cycle->assignments ?? []),
            'peer_review_required' => in_array('peer_review', $cycle->assignments ?? []),
            'anonymous_feedback' => $cycle->anonymous_peer_reviews,
        ]);

        return $review;
    }

    /**
     * Determine reviewers for an employee based on assignment types
     */
    protected function determineReviewers(PerformanceReviewCycle $cycle, User $employee)
    {
        $reviewers = [];
        $assignments = $cycle->assignments ?? [];

        // Self review
        if (in_array('self_review', $assignments)) {
            $reviewers['self'] = $employee->id;
        }

        // Manager review
        if (in_array('manager_review', $assignments) && $employee->manager_id) {
            $reviewers['manager'] = $employee->manager_id;
        }

        // Peer reviews
        if (in_array('peer_review', $assignments)) {
            $peers = $this->selectPeerReviewers($employee, 3); // Default 3 peers
            if (! empty($peers)) {
                $reviewers['peers'] = $peers;
            }
        }

        // Direct report reviews
        if (in_array('direct_report', $assignments)) {
            $directReports = $this->getDirectReports($employee);
            if (! empty($directReports)) {
                $reviewers['direct_reports'] = $directReports;
            }
        }

        return $reviewers;
    }

    /**
     * Select peer reviewers for an employee
     */
    protected function selectPeerReviewers(User $employee, $count = 3)
    {
        $peers = User::where('status', 'active')
            ->where('id', '!=', $employee->id)
            ->where('department_id', $employee->department_id)
            ->where('manager_id', $employee->manager_id)
            ->inRandomOrder()
            ->limit($count)
            ->pluck('id')
            ->toArray();

        // If not enough peers in same department, expand search
        if (count($peers) < $count) {
            $additionalPeers = User::where('status', 'active')
                ->where('id', '!=', $employee->id)
                ->whereNotIn('id', $peers)
                ->where('department_id', $employee->department_id)
                ->inRandomOrder()
                ->limit($count - count($peers))
                ->pluck('id')
                ->toArray();

            $peers = array_merge($peers, $additionalPeers);
        }

        return $peers;
    }

    /**
     * Get direct reports for an employee
     */
    protected function getDirectReports(User $employee)
    {
        return User::where('status', 'active')
            ->where('manager_id', $employee->id)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Get assignment statistics for a cycle
     */
    public function getAssignmentStatistics(PerformanceReviewCycle $cycle)
    {
        $reviews = $cycle->reviews()->with(['employee', 'manager'])->get();

        $stats = [
            'total_reviews' => $reviews->count(),
            'pending_reviews' => $reviews->where('status', 'pending')->count(),
            'in_progress_reviews' => $reviews->where('status', 'in_progress')->count(),
            'completed_reviews' => $reviews->where('status', 'completed')->count(),
            'assignment_breakdown' => [],
            'department_breakdown' => [],
        ];

        // Assignment type breakdown
        $assignments = $cycle->assignments ?? [];
        foreach ($assignments as $assignmentType) {
            switch ($assignmentType) {
                case 'self_review':
                    $stats['assignment_breakdown']['self_reviews'] = $reviews->where('self_review_required', true)->count();
                    break;
                case 'manager_review':
                    $stats['assignment_breakdown']['manager_reviews'] = $reviews->where('manager_review_required', true)->count();
                    break;
                case 'peer_review':
                    $stats['assignment_breakdown']['peer_reviews'] = $reviews->where('peer_review_required', true)->count();
                    break;
            }
        }

        // Department breakdown
        $departmentGroups = $reviews->groupBy('employee.department_id');
        foreach ($departmentGroups as $departmentId => $departmentReviews) {
            $stats['department_breakdown'][$departmentId] = [
                'total' => $departmentReviews->count(),
                'completed' => $departmentReviews->where('status', 'completed')->count(),
            ];
        }

        return $stats;
    }

    /**
     * Validate cycle readiness for assignment generation
     */
    public function validateCycleForAssignment(PerformanceReviewCycle $cycle)
    {
        $errors = [];

        // Check setup status
        if (! $cycle->isReadyToLaunch()) {
            $errors[] = 'Cycle setup is not complete. Please finish the setup wizard first.';
        }

        // Check competencies and criteria
        if ($cycle->getCompetenciesCount() === 0) {
            $errors[] = 'No competencies defined for this cycle.';
        }

        if ($cycle->getActiveCriteriaCount() === 0) {
            $errors[] = 'No active criteria defined for this cycle.';
        }

        // Check assignment types
        if (empty($cycle->assignments)) {
            $errors[] = 'No assignment types selected for this cycle.';
        }

        // Check period dates
        if ($cycle->period_start > $cycle->period_end) {
            $errors[] = 'Invalid review period: start date is after end date.';
        }

        if ($cycle->period_end < now()) {
            $errors[] = 'Review period end date is in the past.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
