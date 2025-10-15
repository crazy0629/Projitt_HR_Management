<?php

namespace App\Models\LMS;

use App\Models\Course;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'course_id',
        'source',
        'status',
        'progress_pct',
        'started_at',
        'completed_at',
        'last_activity_at',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'progress_pct' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'status' => 'not_started',
        'progress_pct' => 0,
    ];

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class, 'enrollment_id');
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'enrollment_id');
    }

    public function lmsEvents(): HasMany
    {
        return $this->hasMany(LMSEvent::class, 'employee_id', 'employee_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['not_started', 'in_progress']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeNotStarted($query)
    {
        return $query->where('status', 'not_started');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    public function scopeWithCourse($query)
    {
        return $query->with('course');
    }

    public function scopeWithProgress($query)
    {
        return $query->with('lessonProgress');
    }

    // Helper methods
    public function isNotStarted(): bool
    {
        return $this->status === 'not_started';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    public function hasExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getDaysUntilExpiry(): ?int
    {
        if (! $this->expires_at) {
            return null;
        }

        return Carbon::now()->diffInDays($this->expires_at, false);
    }

    public function start(): void
    {
        if ($this->isNotStarted()) {
            $this->update([
                'status' => 'in_progress',
                'started_at' => now(),
                'last_activity_at' => now(),
            ]);

            // Log event
            LMSEvent::logEvent(
                $this->employee_id,
                'course_started',
                $this->course_id,
                null,
                null,
                ['enrollment_id' => $this->id]
            );
        }
    }

    public function updateProgress(): void
    {
        $lessons = $this->course->lessons()->where('status', 'active')->get();
        $totalLessons = $lessons->count();

        if ($totalLessons === 0) {
            return;
        }

        $completedLessons = $this->lessonProgress()
            ->where('status', 'completed')
            ->count();

        $progressPct = round(($completedLessons / $totalLessons) * 100);

        $this->update([
            'progress_pct' => $progressPct,
            'last_activity_at' => now(),
        ]);

        // Check if course is complete
        if ($progressPct >= 100 && ! $this->isCompleted()) {
            $this->complete();
        }
    }

    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'progress_pct' => 100,
            'last_activity_at' => now(),
        ]);

        // Log event
        LMSEvent::logEvent(
            $this->employee_id,
            'course_completed',
            $this->course_id,
            null,
            null,
            ['enrollment_id' => $this->id, 'completion_time' => $this->getCompletionTime()]
        );

        // Generate certificate if applicable
        $this->generateCertificateIfEligible();
    }

    public function abandon(): void
    {
        $this->update([
            'status' => 'expired', // Using expired status for abandoned
            'last_activity_at' => now(),
        ]);

        // Log event
        LMSEvent::logEvent(
            $this->employee_id,
            'course_abandoned',
            $this->course_id,
            null,
            null,
            ['enrollment_id' => $this->id]
        );
    }

    public function getCompletionTime(): ?int
    {
        if (! $this->started_at || ! $this->completed_at) {
            return null;
        }

        return $this->started_at->diffInMinutes($this->completed_at);
    }

    public function getTotalTimeSpent(): int
    {
        return $this->lessonProgress()->sum('seconds_consumed');
    }

    public function getCompletedLessonsCount(): int
    {
        return $this->lessonProgress()->where('status', 'completed')->count();
    }

    public function getTotalLessonsCount(): int
    {
        return $this->course->lessons()->where('status', 'active')->count();
    }

    public function getNextLesson(): ?CourseLesson
    {
        $completedLessonIds = $this->lessonProgress()
            ->where('status', 'completed')
            ->pluck('lesson_id');

        return $this->course->lessons()
            ->where('status', 'active')
            ->whereNotIn('id', $completedLessonIds)
            ->orderBy('order_index')
            ->first();
    }

    public function getLessonProgress(int $lessonId): ?LessonProgress
    {
        return $this->lessonProgress()
            ->where('lesson_id', $lessonId)
            ->first();
    }

    public function hasPassedAllQuizzes(): bool
    {
        $quizLessons = $this->course->lessons()
            ->where('type', 'quiz')
            ->where('status', 'active')
            ->get();

        foreach ($quizLessons as $lesson) {
            $bestAttempt = $this->quizAttempts()
                ->where('lesson_id', $lesson->id)
                ->orderBy('score', 'desc')
                ->first();

            if (! $bestAttempt || ! $bestAttempt->is_passed) {
                return false;
            }
        }

        return true;
    }

    private function generateCertificateIfEligible(): void
    {
        // Check if course has certificate enabled and user is eligible
        $courseMetadata = $this->course->metadata ?? [];
        if (isset($courseMetadata['certificate_enabled']) && $courseMetadata['certificate_enabled']) {
            Certificate::generateForCourse($this->employee_id, $this->course_id);
        }
    }
}
