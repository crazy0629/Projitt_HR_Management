<?php

namespace App\Services\LMS;

use App\Models\LMS\Enrollment;
use App\Models\LMS\LessonProgress;
use App\Models\LMS\PathEnrollment;
use Illuminate\Support\Facades\DB;

class ProgressService
{
    /**
     * Get or create lesson progress for an enrollment
     */
    public function getOrCreateLessonProgress(int $enrollmentId, int $lessonId): LessonProgress
    {
        return LessonProgress::firstOrCreate(
            [
                'enrollment_id' => $enrollmentId,
                'lesson_id' => $lessonId,
            ],
            [
                'status' => 'not_started',
                'seconds_consumed' => 0,
                'last_position_sec' => 0,
            ]
        );
    }

    /**
     * Update lesson progress with playback position and time spent
     */
    public function updateLessonProgress(
        int $enrollmentId,
        int $lessonId,
        int $positionSeconds,
        int $consumedSeconds
    ): LessonProgress {
        $progress = $this->getOrCreateLessonProgress($enrollmentId, $lessonId);

        // Start the lesson if not started
        if ($progress->isNotStarted()) {
            $progress->start();
        }

        $progress->updateProgress($positionSeconds, $consumedSeconds);

        // Check if lesson should be auto-completed
        $progress->checkAndAutoComplete();

        return $progress->fresh();
    }

    /**
     * Mark a lesson as completed manually
     */
    public function completeLessonProgress(int $enrollmentId, int $lessonId): LessonProgress
    {
        $progress = $this->getOrCreateLessonProgress($enrollmentId, $lessonId);

        if (! $progress->isCompleted()) {
            $progress->complete();
        }

        return $progress->fresh();
    }

    /**
     * Mark a lesson as viewed (for PDFs, external links)
     */
    public function markLessonViewed(int $enrollmentId, int $lessonId): LessonProgress
    {
        $progress = $this->getOrCreateLessonProgress($enrollmentId, $lessonId);
        $progress->markViewed();

        return $progress->fresh();
    }

    /**
     * Get comprehensive progress data for an enrollment
     */
    public function getEnrollmentProgress(int $enrollmentId): array
    {
        $enrollment = Enrollment::with([
            'course.lessons' => function ($query) {
                $query->where('status', 'active')->orderBy('order_index');
            },
            'lessonProgress',
            'quizAttempts',
        ])->findOrFail($enrollmentId);

        $lessons = $enrollment->course->lessons;
        $progressData = [];

        foreach ($lessons as $lesson) {
            $progress = $enrollment->lessonProgress
                ->where('lesson_id', $lesson->id)
                ->first();

            $quizData = null;
            if ($lesson->hasQuiz()) {
                $bestAttempt = $enrollment->quizAttempts
                    ->where('lesson_id', $lesson->id)
                    ->sortByDesc('score')
                    ->first();

                $quizData = [
                    'has_quiz' => true,
                    'best_score' => $bestAttempt?->score ?? null,
                    'is_passed' => $bestAttempt?->is_passed ?? false,
                    'attempts_count' => $enrollment->quizAttempts
                        ->where('lesson_id', $lesson->id)
                        ->count(),
                    'can_attempt' => $lesson->quiz?->canUserAttempt($enrollmentId) ?? false,
                ];
            }

            $progressData[] = [
                'lesson_id' => $lesson->id,
                'lesson_title' => $lesson->title,
                'lesson_type' => $lesson->type,
                'order_index' => $lesson->order_index,
                'is_required' => $lesson->is_required,
                'duration_est_min' => $lesson->duration_est_min,
                'status' => $progress?->status ?? 'not_started',
                'progress_pct' => $progress?->getProgressPercentage() ?? 0,
                'seconds_consumed' => $progress?->seconds_consumed ?? 0,
                'last_position_sec' => $progress?->last_position_sec ?? 0,
                'started_at' => $progress?->started_at,
                'completed_at' => $progress?->completed_at,
                'quiz' => $quizData,
            ];
        }

        return [
            'enrollment' => [
                'id' => $enrollment->id,
                'course_id' => $enrollment->course_id,
                'course_title' => $enrollment->course->title,
                'status' => $enrollment->status,
                'progress_pct' => $enrollment->progress_pct,
                'started_at' => $enrollment->started_at,
                'completed_at' => $enrollment->completed_at,
                'last_activity_at' => $enrollment->last_activity_at,
            ],
            'lessons' => $progressData,
            'summary' => [
                'total_lessons' => $lessons->count(),
                'completed_lessons' => $enrollment->getCompletedLessonsCount(),
                'required_lessons' => $lessons->where('is_required', true)->count(),
                'completed_required' => $enrollment->lessonProgress()
                    ->whereHas('lesson', function ($q) {
                        $q->where('is_required', true);
                    })
                    ->where('status', 'completed')
                    ->count(),
                'total_time_spent' => $enrollment->getTotalTimeSpent(),
                'next_lesson' => $enrollment->getNextLesson()?->only([
                    'id', 'title', 'type', 'order_index',
                ]),
            ],
        ];
    }

    /**
     * Get learning path progress for a user
     */
    public function getPathProgress(int $employeeId, int $pathId): array
    {
        $pathEnrollment = PathEnrollment::with('path.courses')
            ->where('employee_id', $employeeId)
            ->where('path_id', $pathId)
            ->firstOrFail();

        $courseStatuses = $pathEnrollment->getCourseEnrollmentStatus();

        return [
            'path_enrollment' => [
                'id' => $pathEnrollment->id,
                'path_id' => $pathEnrollment->path_id,
                'path_title' => $pathEnrollment->path->title,
                'status' => $pathEnrollment->status,
                'progress_pct' => $pathEnrollment->progress_pct,
                'completed_courses' => $pathEnrollment->completed_courses,
                'total_courses' => $pathEnrollment->total_courses,
                'assigned_at' => $pathEnrollment->assigned_at,
                'started_at' => $pathEnrollment->started_at,
                'completed_at' => $pathEnrollment->completed_at,
                'due_date' => $pathEnrollment->due_date,
                'is_overdue' => $pathEnrollment->isOverdue(),
                'days_until_due' => $pathEnrollment->getDaysUntilDue(),
            ],
            'courses' => $courseStatuses,
            'summary' => [
                'total_time_spent' => $pathEnrollment->getTotalTimeSpent(),
                'completion_time_days' => $pathEnrollment->getCompletionTime(),
                'next_course' => $pathEnrollment->getNextCourse()?->only([
                    'id', 'title', 'type', 'difficulty_level',
                ]),
            ],
        ];
    }

    /**
     * Bulk update progress for multiple lessons
     */
    public function bulkUpdateProgress(array $progressUpdates): array
    {
        $results = [];

        DB::transaction(function () use ($progressUpdates, &$results) {
            foreach ($progressUpdates as $update) {
                $progress = $this->updateLessonProgress(
                    $update['enrollment_id'],
                    $update['lesson_id'],
                    $update['position_seconds'] ?? 0,
                    $update['consumed_seconds'] ?? 0
                );

                $results[] = [
                    'lesson_id' => $update['lesson_id'],
                    'status' => $progress->status,
                    'progress_pct' => $progress->getProgressPercentage(),
                ];
            }
        });

        return $results;
    }

    /**
     * Get user's overall learning statistics
     */
    public function getUserLearningStats(int $employeeId, ?int $days = 30): array
    {
        $enrollments = Enrollment::where('employee_id', $employeeId)
            ->with(['course', 'lessonProgress'])
            ->get();

        $pathEnrollments = PathEnrollment::where('employee_id', $employeeId)
            ->with('path')
            ->get();

        // Recent activity
        $recentActivity = $enrollments->filter(function ($enrollment) use ($days) {
            return $enrollment->last_activity_at &&
                   $enrollment->last_activity_at->diffInDays(now()) <= $days;
        });

        return [
            'courses' => [
                'total_enrolled' => $enrollments->count(),
                'completed' => $enrollments->where('status', 'completed')->count(),
                'in_progress' => $enrollments->where('status', 'in_progress')->count(),
                'not_started' => $enrollments->where('status', 'not_started')->count(),
                'completion_rate' => $enrollments->count() > 0
                    ? round(($enrollments->where('status', 'completed')->count() / $enrollments->count()) * 100, 2)
                    : 0,
            ],
            'learning_paths' => [
                'total_enrolled' => $pathEnrollments->count(),
                'completed' => $pathEnrollments->where('status', 'completed')->count(),
                'in_progress' => $pathEnrollments->where('status', 'in_progress')->count(),
                'assigned' => $pathEnrollments->where('status', 'assigned')->count(),
                'completion_rate' => $pathEnrollments->count() > 0
                    ? round(($pathEnrollments->where('status', 'completed')->count() / $pathEnrollments->count()) * 100, 2)
                    : 0,
            ],
            'activity' => [
                'total_time_spent_minutes' => $enrollments->sum(function ($enrollment) {
                    return round($enrollment->getTotalTimeSpent() / 60);
                }),
                'active_courses_last_30_days' => $recentActivity->count(),
                'lessons_completed_total' => $enrollments->sum(function ($enrollment) {
                    return $enrollment->getCompletedLessonsCount();
                }),
                'avg_session_time_minutes' => $enrollments->where('status', 'completed')->count() > 0
                    ? round($enrollments->sum(function ($enrollment) {
                        return $enrollment->getTotalTimeSpent() / 60;
                    }) / $enrollments->where('status', 'completed')->count())
                    : 0,
            ],
            'recent_completions' => $enrollments
                ->where('status', 'completed')
                ->sortByDesc('completed_at')
                ->take(5)
                ->map(function ($enrollment) {
                    return [
                        'course_id' => $enrollment->course_id,
                        'course_title' => $enrollment->course->title,
                        'completed_at' => $enrollment->completed_at,
                        'completion_time_minutes' => $enrollment->getCompletionTime(),
                    ];
                })
                ->values(),
        ];
    }
}
