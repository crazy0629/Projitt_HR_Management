<?php

namespace App\Models\LMS;

use App\Models\Course;
use App\Models\LearningPath\LearningPath;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LMSEvent extends Model
{
    use HasFactory;

    protected $table = 'lms_events';

    protected $fillable = [
        'employee_id',
        'event_type',
        'course_id',
        'lesson_id',
        'path_id',
        'event_data',
        'event_timestamp',
    ];

    protected $casts = [
        'event_data' => 'array',
        'event_timestamp' => 'datetime',
    ];

    protected $attributes = [
        'event_timestamp' => null,
    ];

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(CourseLesson::class, 'lesson_id');
    }

    public function path(): BelongsTo
    {
        return $this->belongsTo(LearningPath::class, 'path_id');
    }

    // Scopes
    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    public function scopeByLesson($query, $lessonId)
    {
        return $query->where('lesson_id', $lessonId);
    }

    public function scopeByPath($query, $pathId)
    {
        return $query->where('path_id', $pathId);
    }

    public function scopeByEventType($query, $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('event_timestamp', '>=', now()->subDays($days));
    }

    public function scopeOrderByTimestamp($query, $direction = 'desc')
    {
        return $query->orderBy('event_timestamp', $direction);
    }

    // Course-related events
    public function scopeCourseEvents($query)
    {
        return $query->whereIn('event_type', [
            'course_enrolled', 'course_started', 'course_completed', 'course_abandoned',
        ]);
    }

    // Lesson-related events
    public function scopeLessonEvents($query)
    {
        return $query->whereIn('event_type', [
            'lesson_started', 'lesson_completed', 'lesson_viewed',
        ]);
    }

    // Quiz-related events
    public function scopeQuizEvents($query)
    {
        return $query->whereIn('event_type', [
            'quiz_started', 'quiz_completed', 'quiz_failed', 'quiz_retaken',
        ]);
    }

    // Path-related events
    public function scopePathEvents($query)
    {
        return $query->whereIn('event_type', [
            'path_enrolled', 'path_started', 'path_completed', 'path_abandoned',
        ]);
    }

    // Achievement events
    public function scopeAchievementEvents($query)
    {
        return $query->whereIn('event_type', [
            'certificate_earned', 'progress_checkpoint',
        ]);
    }

    // Helper methods
    public function isCourseEvent(): bool
    {
        return in_array($this->event_type, [
            'course_enrolled', 'course_started', 'course_completed', 'course_abandoned',
        ]);
    }

    public function isLessonEvent(): bool
    {
        return in_array($this->event_type, [
            'lesson_started', 'lesson_completed', 'lesson_viewed',
        ]);
    }

    public function isQuizEvent(): bool
    {
        return in_array($this->event_type, [
            'quiz_started', 'quiz_completed', 'quiz_failed', 'quiz_retaken',
        ]);
    }

    public function isPathEvent(): bool
    {
        return in_array($this->event_type, [
            'path_enrolled', 'path_started', 'path_completed', 'path_abandoned',
        ]);
    }

    public function isAchievementEvent(): bool
    {
        return in_array($this->event_type, [
            'certificate_earned', 'progress_checkpoint',
        ]);
    }

    public function getEventDescription(): string
    {
        $descriptions = [
            'course_enrolled' => 'Enrolled in course',
            'course_started' => 'Started course',
            'course_completed' => 'Completed course',
            'course_abandoned' => 'Abandoned course',
            'lesson_started' => 'Started lesson',
            'lesson_completed' => 'Completed lesson',
            'lesson_viewed' => 'Viewed lesson',
            'quiz_started' => 'Started quiz',
            'quiz_completed' => 'Completed quiz',
            'quiz_failed' => 'Failed quiz',
            'quiz_retaken' => 'Retook quiz',
            'path_enrolled' => 'Enrolled in learning path',
            'path_started' => 'Started learning path',
            'path_completed' => 'Completed learning path',
            'path_abandoned' => 'Abandoned learning path',
            'certificate_earned' => 'Earned certificate',
            'progress_checkpoint' => 'Reached progress checkpoint',
        ];

        return $descriptions[$this->event_type] ?? 'Unknown event';
    }

    protected static function booted()
    {
        static::creating(function ($event) {
            if (! $event->event_timestamp) {
                $event->event_timestamp = now();
            }
        });
    }

    public static function logEvent(
        int $employeeId,
        string $eventType,
        ?int $courseId = null,
        ?int $lessonId = null,
        ?int $pathId = null,
        ?array $eventData = null
    ): self {
        return static::create([
            'employee_id' => $employeeId,
            'event_type' => $eventType,
            'course_id' => $courseId,
            'lesson_id' => $lessonId,
            'path_id' => $pathId,
            'event_data' => $eventData,
            'event_timestamp' => now(),
        ]);
    }

    // Analytics helper methods
    public static function getEmployeeActivitySummary(int $employeeId, ?int $days = 30): array
    {
        $events = static::byEmployee($employeeId)
            ->recent($days)
            ->get()
            ->groupBy('event_type');

        return [
            'total_events' => $events->flatten()->count(),
            'courses_started' => $events->get('course_started', collect())->count(),
            'courses_completed' => $events->get('course_completed', collect())->count(),
            'lessons_completed' => $events->get('lesson_completed', collect())->count(),
            'quizzes_taken' => $events->get('quiz_started', collect())->count(),
            'quizzes_passed' => $events->get('quiz_completed', collect())->count(),
            'certificates_earned' => $events->get('certificate_earned', collect())->count(),
            'paths_started' => $events->get('path_started', collect())->count(),
            'paths_completed' => $events->get('path_completed', collect())->count(),
            'last_activity' => static::byEmployee($employeeId)
                ->orderByTimestamp()
                ->first()?->event_timestamp,
        ];
    }

    public static function getCourseActivitySummary(int $courseId, ?int $days = 30): array
    {
        $events = static::byCourse($courseId)
            ->recent($days)
            ->get()
            ->groupBy('event_type');

        return [
            'total_events' => $events->flatten()->count(),
            'enrollments' => $events->get('course_enrolled', collect())->count(),
            'starts' => $events->get('course_started', collect())->count(),
            'completions' => $events->get('course_completed', collect())->count(),
            'abandonments' => $events->get('course_abandoned', collect())->count(),
            'unique_users' => $events->flatten()->pluck('employee_id')->unique()->count(),
            'completion_rate' => $events->get('course_started', collect())->count() > 0
                ? round(($events->get('course_completed', collect())->count() / $events->get('course_started', collect())->count()) * 100, 2)
                : 0,
        ];
    }

    public static function getSystemActivitySummary(?int $days = 30): array
    {
        $events = static::recent($days)->get()->groupBy('event_type');

        return [
            'total_events' => $events->flatten()->count(),
            'active_users' => $events->flatten()->pluck('employee_id')->unique()->count(),
            'course_completions' => $events->get('course_completed', collect())->count(),
            'lesson_completions' => $events->get('lesson_completed', collect())->count(),
            'quiz_attempts' => $events->get('quiz_started', collect())->count(),
            'certificates_issued' => $events->get('certificate_earned', collect())->count(),
            'path_completions' => $events->get('path_completed', collect())->count(),
            'most_active_day' => $events->flatten()
                ->groupBy(fn ($event) => $event->event_timestamp->format('Y-m-d'))
                ->map->count()
                ->sortDesc()
                ->keys()
                ->first(),
        ];
    }
}
