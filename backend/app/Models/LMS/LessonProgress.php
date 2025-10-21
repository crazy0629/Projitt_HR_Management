<?php

namespace App\Models\LMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonProgress extends Model
{
    use HasFactory;

    protected $table = 'lesson_progress';

    protected $fillable = [
        'enrollment_id',
        'lesson_id',
        'status',
        'seconds_consumed',
        'last_position_sec',
        'started_at',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'seconds_consumed' => 'integer',
        'last_position_sec' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'status' => 'not_started',
        'seconds_consumed' => 0,
        'last_position_sec' => 0,
    ];

    // Relationships
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(CourseLesson::class, 'lesson_id');
    }

    // Scopes
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

    public function start(): void
    {
        if ($this->isNotStarted()) {
            $this->update([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);

            // Start enrollment if not started
            if ($this->enrollment->isNotStarted()) {
                $this->enrollment->start();
            }

            // Log event
            LMSEvent::logEvent(
                $this->enrollment->employee_id,
                'lesson_started',
                $this->enrollment->course_id,
                $this->lesson_id,
                null,
                ['enrollment_id' => $this->enrollment_id]
            );
        }
    }

    public function updateProgress(int $positionSeconds, int $consumedSeconds): void
    {
        $this->update([
            'last_position_sec' => $positionSeconds,
            'seconds_consumed' => $consumedSeconds,
            'status' => 'in_progress',
        ]);

        // Start if not started
        if ($this->isNotStarted()) {
            $this->start();
        }
    }

    public function complete(): void
    {
        $wasCompleted = $this->isCompleted();

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Only update lesson statistics and enrollment progress if this is a new completion
        if (! $wasCompleted) {
            // Update lesson completion statistics
            $this->lesson->updateCompletionStats();

            // Update enrollment progress
            $this->enrollment->updateProgress();

            // Log event
            LMSEvent::logEvent(
                $this->enrollment->employee_id,
                'lesson_completed',
                $this->enrollment->course_id,
                $this->lesson_id,
                null,
                [
                    'enrollment_id' => $this->enrollment_id,
                    'time_spent' => $this->seconds_consumed,
                    'lesson_type' => $this->lesson->type,
                ]
            );
        }
    }

    public function markViewed(): void
    {
        // For content that doesn't track progress (like PDFs, external links)
        if ($this->isNotStarted()) {
            $this->start();
        }

        LMSEvent::logEvent(
            $this->enrollment->employee_id,
            'lesson_viewed',
            $this->enrollment->course_id,
            $this->lesson_id,
            null,
            ['enrollment_id' => $this->enrollment_id]
        );
    }

    public function getCompletionTime(): ?int
    {
        if (! $this->started_at || ! $this->completed_at) {
            return null;
        }

        return $this->started_at->diffInMinutes($this->completed_at);
    }

    public function getProgressPercentage(): int
    {
        if ($this->isCompleted()) {
            return 100;
        }

        // For video/audio content, calculate based on position
        if (in_array($this->lesson->type, ['video', 'audio']) && $this->lesson->duration_est_min) {
            $estimatedDurationSeconds = $this->lesson->duration_est_min * 60;

            return min(100, round(($this->last_position_sec / $estimatedDurationSeconds) * 100));
        }

        // For other content types
        return $this->isInProgress() ? 50 : 0;
    }

    public function shouldAutoComplete(): bool
    {
        // Auto-complete logic for different lesson types
        switch ($this->lesson->type) {
            case 'video':
            case 'audio':
                // Complete if user has watched/listened to 90% or more
                $estimatedDurationSeconds = $this->lesson->duration_est_min * 60;

                return $estimatedDurationSeconds > 0 &&
                       ($this->last_position_sec / $estimatedDurationSeconds) >= 0.9;

            case 'pdf':
            case 'external_link':
                // These require manual completion or time-based completion
                return false;

            case 'quiz':
                // Quiz completion is handled by quiz attempts
                return false;

            default:
                return false;
        }
    }

    public function checkAndAutoComplete(): void
    {
        if (! $this->isCompleted() && $this->shouldAutoComplete()) {
            $this->complete();
        }
    }
}
