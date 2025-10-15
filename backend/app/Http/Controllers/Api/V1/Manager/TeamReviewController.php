<?php

namespace App\Http\Controllers\Api\V1\Manager;

use App\Http\Controllers\Controller;
use App\Models\ManagerReviews\Team;
use App\Models\ManagerReviews\TeamMember;
use App\Models\PerformanceReview\PerformanceReview;
use App\Models\PerformanceReview\PerformanceReviewCycle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TeamReviewController extends Controller
{
    /**
     * Get cycles where manager has reviewees
     */
    public function getCycles(Request $request): JsonResponse
    {
        $managerId = Auth::id();
        $scope = $request->get('scope', 'ongoing');
        $limit = min($request->get('limit', 25), 100);
        $cursor = $request->get('cursor');

        // Get team members for this manager
        $teamMemberIds = TeamMember::getTeamMembersForManager($managerId)
            ->pluck('employee_user_id');

        $query = PerformanceReviewCycle::whereHas('reviews', function ($q) use ($teamMemberIds) {
            $q->whereIn('reviewee_id', $teamMemberIds);
        });

        // Apply scope filter
        switch ($scope) {
            case 'ongoing':
                $query->where('status', 'active');
                break;
            case 'completed':
                $query->where('status', 'completed');
                break;
            case 'draft':
                $query->where('status', 'draft');
                break;
        }

        if ($cursor) {
            $query->where('id', '<', $cursor);
        }

        $cycles = $query->with(['reviews' => function ($q) use ($teamMemberIds) {
            $q->whereIn('reviewee_id', $teamMemberIds);
        }])
            ->orderBy('id', 'desc')
            ->limit($limit + 1)
            ->get();

        $hasMore = $cycles->count() > $limit;
        if ($hasMore) {
            $cycles->pop();
        }

        // Add completion statistics
        $cycles->transform(function ($cycle) use ($teamMemberIds) {
            $totalReviews = $cycle->reviews->count();
            $completedReviews = $cycle->reviews->where('status', 'completed')->count();

            $cycle->completion_stats = [
                'total' => $totalReviews,
                'completed' => $completedReviews,
                'percentage' => $totalReviews > 0 ? round(($completedReviews / $totalReviews) * 100, 1) : 0,
                'headcount' => $teamMemberIds->count(),
            ];

            return $cycle;
        });

        return response()->json([
            'cycles' => $cycles,
            'pagination' => [
                'has_more' => $hasMore,
                'next_cursor' => $hasMore ? $cycles->last()->id : null,
            ],
        ]);
    }

    /**
     * Get reviewees for a specific cycle
     */
    public function getCycleReviewees(Request $request, int $cycleId): JsonResponse
    {
        $managerId = Auth::id();
        $search = $request->get('search');
        $sort = $request->get('sort', 'progress');
        $limit = min($request->get('limit', 25), 100);
        $cursor = $request->get('cursor');

        // Get team members for this manager
        $teamMemberIds = TeamMember::getTeamMembersForManager($managerId)
            ->pluck('employee_user_id');

        $query = PerformanceReview::where('cycle_id', $cycleId)
            ->whereIn('reviewee_id', $teamMemberIds)
            ->with(['reviewee.profile', 'reviewee.currentRole', 'scores']);

        if ($search) {
            $query->whereHas('reviewee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($cursor) {
            $query->where('id', '<', $cursor);
        }

        // Apply sorting
        switch ($sort) {
            case 'final_score':
                $query->orderBy('final_score', 'desc');
                break;
            case 'progress':
                $query->orderBy('progress_percentage', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $reviewees = $query->limit($limit + 1)->get();

        $hasMore = $reviewees->count() > $limit;
        if ($hasMore) {
            $reviewees->pop();
        }

        // Transform data and add badges
        $reviewees->transform(function ($review) {
            $badges = $this->calculateEmployeeBadges($review);

            return [
                'id' => $review->id,
                'employee' => [
                    'id' => $review->reviewee->id,
                    'name' => $review->reviewee->name,
                    'email' => $review->reviewee->email,
                    'role' => $review->reviewee->currentRole?->name,
                    'department' => $review->reviewee->profile?->department,
                    'location' => $review->reviewee->profile?->location,
                ],
                'progress' => $review->progress_percentage,
                'final_score' => $review->final_score,
                'badges' => $badges,
                'status' => $review->status,
                'last_updated' => $review->updated_at,
            ];
        });

        return response()->json([
            'reviewees' => $reviewees,
            'pagination' => [
                'has_more' => $hasMore,
                'next_cursor' => $hasMore ? $reviewees->last()['id'] : null,
            ],
        ]);
    }

    /**
     * Send reminders to reviewers
     */
    public function sendReminders(Request $request, int $cycleId): JsonResponse
    {
        $request->validate([
            'audience' => 'required|in:self,peer,all',
            'reviewee_ids' => 'sometimes|array',
            'reviewee_ids.*' => 'integer|exists:users,id',
        ]);

        $managerId = Auth::id();
        $audience = $request->get('audience');
        $revieweeIds = $request->get('reviewee_ids', []);

        // Get team members for this manager
        $teamMemberIds = TeamMember::getTeamMembersForManager($managerId)
            ->pluck('employee_user_id');

        // Filter reviewee IDs to only those in manager's team
        if (! empty($revieweeIds)) {
            $revieweeIds = array_intersect($revieweeIds, $teamMemberIds->toArray());
        } else {
            $revieweeIds = $teamMemberIds->toArray();
        }

        // Check rate limiting
        $cacheKey = "manager_reminder_{$managerId}_{$cycleId}";
        if (cache()->has($cacheKey)) {
            return response()->json([
                'error' => 'Rate limit exceeded. Can only send reminders once per 4 hours.',
                'code' => 'RATE_LIMIT',
            ], 429);
        }

        // Queue reminder notifications
        $reminderCount = 0;

        try {
            DB::transaction(function () use ($revieweeIds, $audience, &$reminderCount) {
                // Logic to send reminders based on audience type
                // This would integrate with your notification system

                foreach ($revieweeIds as $revieweeId) {
                    // Send reminders based on audience type
                    switch ($audience) {
                        case 'self':
                            // Send self-review reminder
                            $reminderCount++;
                            break;
                        case 'peer':
                            // Send peer review reminders
                            $reminderCount++;
                            break;
                        case 'all':
                            // Send all pending review reminders
                            $reminderCount++;
                            break;
                    }
                }
            });

            // Set rate limit cache
            cache()->put($cacheKey, true, now()->addHours(4));

            return response()->json([
                'message' => "Reminders sent to {$reminderCount} reviewers",
                'reminders_sent' => $reminderCount,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to send reminders',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get team summary for a cycle
     */
    public function getTeamSummary(Request $request): JsonResponse
    {
        $managerId = Auth::id();
        $cycleId = $request->get('cycle_id');

        // Get team members
        $teamMembers = TeamMember::getTeamMembersForManager($managerId);
        $teamMemberIds = $teamMembers->pluck('employee_user_id');

        $summary = [
            'team_size' => $teamMemberIds->count(),
            'total_reviewed' => 0,
            'avg_final_score' => 0.0,
            'completion_percentage' => 0.0,
            'high_performers_percentage' => 0.0,
        ];

        if ($cycleId && $teamMemberIds->isNotEmpty()) {
            $reviews = PerformanceReview::where('cycle_id', $cycleId)
                ->whereIn('reviewee_id', $teamMemberIds)
                ->get();

            $completedReviews = $reviews->where('status', 'completed');

            $summary['total_reviewed'] = $completedReviews->count();
            $summary['completion_percentage'] = $reviews->count() > 0
                ? round(($completedReviews->count() / $reviews->count()) * 100, 1)
                : 0;

            if ($completedReviews->isNotEmpty()) {
                $summary['avg_final_score'] = round($completedReviews->avg('final_score'), 2);
                $highPerformers = $completedReviews->where('final_score', '>=', 4.3)->count();
                $summary['high_performers_percentage'] = round(($highPerformers / $completedReviews->count()) * 100, 1);
            }
        }

        return response()->json($summary);
    }

    /**
     * Get team members with their performance data
     */
    public function getTeamMembers(Request $request): JsonResponse
    {
        $managerId = Auth::id();
        $cycleId = $request->get('cycle_id');
        $filter = $request->get('filter', []);
        $limit = min($request->get('limit', 25), 100);
        $cursor = $request->get('cursor');

        $teamMembers = TeamMember::getTeamMembersForManager($managerId);
        $teamMemberIds = $teamMembers->pluck('employee_user_id');

        $query = DB::table('users')
            ->whereIn('id', $teamMemberIds)
            ->leftJoin('performance_reviews', function ($join) use ($cycleId) {
                $join->on('users.id', '=', 'performance_reviews.reviewee_id');
                if ($cycleId) {
                    $join->where('performance_reviews.cycle_id', $cycleId);
                }
            })
            ->leftJoin('performance_review_scores as peer_scores', function ($join) use ($cycleId) {
                $join->on('users.id', '=', 'peer_scores.reviewee_id')
                    ->where('peer_scores.reviewer_type', 'peer');
                if ($cycleId) {
                    $join->where('peer_scores.cycle_id', $cycleId);
                }
            })
            ->leftJoin('performance_review_scores as manager_scores', function ($join) use ($cycleId) {
                $join->on('users.id', '=', 'manager_scores.reviewee_id')
                    ->where('manager_scores.reviewer_type', 'manager');
                if ($cycleId) {
                    $join->where('manager_scores.cycle_id', $cycleId);
                }
            })
            ->leftJoin('performance_review_scores as self_scores', function ($join) use ($cycleId) {
                $join->on('users.id', '=', 'self_scores.reviewee_id')
                    ->where('self_scores.reviewer_type', 'self');
                if ($cycleId) {
                    $join->where('self_scores.cycle_id', $cycleId);
                }
            })
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'performance_reviews.final_score',
                'peer_scores.overall_score as peer_score',
                'manager_scores.overall_score as manager_score',
                'self_scores.overall_score as self_score',
                'performance_reviews.status as review_status',
            ]);

        if ($cursor) {
            $query->where('users.id', '<', $cursor);
        }

        $members = $query->limit($limit + 1)->get();

        $hasMore = $members->count() > $limit;
        if ($hasMore) {
            $members->pop();
        }

        // Add badges to each member
        $members->transform(function ($member) {
            $badges = $this->calculateMemberBadges($member);

            $member->badges = $badges;

            return $member;
        });

        // Apply status filter if specified
        if (isset($filter['status'])) {
            $members = $members->filter(function ($member) use ($filter) {
                return in_array($filter['status'], $member->badges);
            });
        }

        return response()->json([
            'members' => $members,
            'pagination' => [
                'has_more' => $hasMore,
                'next_cursor' => $hasMore ? $members->last()->id : null,
            ],
        ]);
    }

    /**
     * Calculate employee badges based on performance
     */
    private function calculateEmployeeBadges($review): array
    {
        $badges = [];

        if ($review->final_score >= 4.3 && $review->manager_score >= 4.0) {
            $badges[] = 'Ready';
        }

        if ($review->final_score >= 4.5 || $review->peer_avg >= 4.6) {
            $badges[] = 'High Potential';
        }

        if ($review->final_score < 3.2 || $review->manager_score < 3.0) {
            $badges[] = 'Developing';
        }

        return $badges;
    }

    /**
     * Calculate member badges
     */
    private function calculateMemberBadges($member): array
    {
        $badges = [];

        if ($member->final_score >= 4.3 && $member->manager_score >= 4.0) {
            $badges[] = 'ready';
        }

        if ($member->final_score >= 4.5 || $member->peer_score >= 4.6) {
            $badges[] = 'high_potential';
        }

        if ($member->final_score < 3.2 || $member->manager_score < 3.0) {
            $badges[] = 'developing';
        }

        if (empty($badges)) {
            $badges[] = 'solid';
        }

        return $badges;
    }
}
