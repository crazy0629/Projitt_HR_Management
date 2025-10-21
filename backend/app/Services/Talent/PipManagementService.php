<?php

namespace App\Services\Talent;

use App\Models\Talent\Pip;
use Illuminate\Support\Facades\DB;

class PipManagementService
{
    public function createPip($employeeId, $data)
    {
        return Pip::createForEmployee($employeeId, $data);
    }

    public function addCheckin($pipId, $summary, $nextSteps = null, $rating = null)
    {
        $pip = Pip::findOrFail($pipId);

        return $pip->addCheckin($summary, $nextSteps, $rating);
    }

    public function updatePipStatus($pipId, $status, $notes = null)
    {
        $pip = Pip::findOrFail($pipId);

        switch ($status) {
            case 'paused':
                return $pip->pause($notes);
            case 'active':
                return $pip->resume();
            case 'completed':
                return $pip->complete($notes);
            case 'cancelled':
                return $pip->cancel($notes);
            default:
                throw new \InvalidArgumentException("Invalid status: {$status}");
        }
    }

    public function getPipsDueForCheckin()
    {
        return Pip::active()
            ->with(['employee', 'mentor'])
            ->get()
            ->filter(function ($pip) {
                return $pip->isCheckinDue();
            });
    }

    public function getOverduePips()
    {
        return Pip::overdue()
            ->with(['employee', 'mentor', 'learningPath'])
            ->get();
    }

    public function getPipMetrics($startDate = null, $endDate = null)
    {
        $query = Pip::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $total = $query->count();
        $active = $query->clone()->active()->count();
        $completed = $query->clone()->completed()->count();
        $cancelled = $query->clone()->cancelled()->count();

        return [
            'total_pips' => $total,
            'active_pips' => $active,
            'completed_pips' => $completed,
            'cancelled_pips' => $cancelled,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            'success_rate' => $total > 0 ? round(($completed / ($completed + $cancelled)) * 100, 1) : 0,
            'average_duration' => $query->clone()->completed()->avg(DB::raw('DATEDIFF(updated_at, start_date)')),
            'overdue_count' => Pip::overdue()->count(),
            'checkins_due' => $this->getPipsDueForCheckin()->count(),
        ];
    }

    public function getEmployeePipHistory($employeeId)
    {
        return Pip::byEmployee($employeeId)
            ->with(['mentor', 'learningPath', 'checkins.creator'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($pip) {
                return [
                    'id' => $pip->id,
                    'goal_text' => $pip->goal_text,
                    'status' => $pip->status,
                    'start_date' => $pip->start_date,
                    'end_date' => $pip->end_date,
                    'mentor' => $pip->mentor,
                    'learning_path' => $pip->learningPath,
                    'progress_percentage' => $pip->getProgressPercentage(),
                    'checkin_count' => $pip->getCheckinCount(),
                    'average_rating' => $pip->getAverageRating(),
                    'is_overdue' => $pip->isOverdue(),
                    'completion_notes' => $pip->completion_notes,
                ];
            });
    }

    public function generatePipReport($pipId)
    {
        $pip = Pip::with([
            'employee',
            'mentor',
            'learningPath',
            'checkins.creator',
        ])->findOrFail($pipId);

        $checkins = $pip->checkins()->orderBy('checkin_date')->get();

        return [
            'pip' => [
                'id' => $pip->id,
                'employee' => $pip->employee,
                'mentor' => $pip->mentor,
                'goal_text' => $pip->goal_text,
                'status' => $pip->status,
                'start_date' => $pip->start_date,
                'end_date' => $pip->end_date,
                'frequency' => $pip->checkin_frequency,
                'learning_path' => $pip->learningPath,
                'completion_notes' => $pip->completion_notes,
            ],
            'progress' => [
                'total_duration' => $pip->getTotalDuration(),
                'days_elapsed' => $pip->getDaysElapsed(),
                'days_remaining' => $pip->getDaysRemaining(),
                'progress_percentage' => $pip->getProgressPercentage(),
                'is_overdue' => $pip->isOverdue(),
            ],
            'checkins' => $checkins->map(function ($checkin) {
                return [
                    'date' => $checkin->checkin_date,
                    'summary' => $checkin->summary,
                    'next_steps' => $checkin->next_steps,
                    'rating' => $checkin->rating,
                    'rating_label' => $checkin->getRatingLabel(),
                    'creator' => $checkin->creator,
                ];
            }),
            'performance_trend' => $this->calculatePerformanceTrend($checkins),
            'recommendations' => $this->generateRecommendations($pip, $checkins),
        ];
    }

    private function calculatePerformanceTrend($checkins)
    {
        $ratingsWithDates = $checkins->whereNotNull('rating')->map(function ($checkin) {
            return [
                'date' => $checkin->checkin_date->format('Y-m-d'),
                'rating' => $checkin->rating,
            ];
        })->values();

        if ($ratingsWithDates->count() < 2) {
            return [
                'trend' => 'insufficient_data',
                'direction' => null,
                'improvement' => null,
            ];
        }

        $firstRating = $ratingsWithDates->first()['rating'];
        $lastRating = $ratingsWithDates->last()['rating'];
        $improvement = $lastRating - $firstRating;

        return [
            'trend' => $improvement > 0.5 ? 'improving' : ($improvement < -0.5 ? 'declining' : 'stable'),
            'direction' => $improvement > 0 ? 'up' : ($improvement < 0 ? 'down' : 'stable'),
            'improvement' => $improvement,
            'ratings_data' => $ratingsWithDates,
        ];
    }

    private function generateRecommendations($pip, $checkins)
    {
        $recommendations = [];

        if ($pip->isOverdue()) {
            $recommendations[] = [
                'type' => 'urgent',
                'message' => 'PIP is overdue. Consider immediate review and action.',
            ];
        }

        $recentCheckins = $checkins->take(3);
        $avgRecentRating = $recentCheckins->whereNotNull('rating')->avg('rating');

        if ($avgRecentRating && $avgRecentRating < 2.5) {
            $recommendations[] = [
                'type' => 'performance',
                'message' => 'Recent ratings indicate continued performance concerns. Consider additional support or intervention.',
            ];
        } elseif ($avgRecentRating && $avgRecentRating >= 4) {
            $recommendations[] = [
                'type' => 'success',
                'message' => 'Performance shows significant improvement. Consider early completion or transition planning.',
            ];
        }

        if ($checkins->count() === 0 && $pip->getDaysElapsed() > 7) {
            $recommendations[] = [
                'type' => 'process',
                'message' => 'No check-ins recorded. Schedule regular check-ins to track progress.',
            ];
        }

        return $recommendations;
    }
}
