<?php

namespace App\Models\LMS;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CourseLesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'type',
        'title',
        'description',
        'order_index',
        'payload',
        'duration_est_min',
        'is_required',
        'status',
        'completions_count',
        'avg_completion_time_min',
    ];

    protected $casts = [
        'payload' => 'array',
        'is_required' => 'boolean',
        'duration_est_min' => 'integer',
        'order_index' => 'integer',
        'completions_count' => 'integer',
        'avg_completion_time_min' => 'decimal:2',
    ];

    protected $attributes = [
        'type' => 'external_link',
        'order_index' => 0,
        'is_required' => true,
        'status' => 'active',
        'completions_count' => 0,
    ];

    // Relationships
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function quiz(): HasOne
    {
        return $this->hasOne(LessonQuiz::class, 'lesson_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class, 'lesson_id');
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'lesson_id');
    }

    public function lmsEvents(): HasMany
    {
        return $this->hasMany(LMSEvent::class, 'lesson_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeOrderByIndex($query)
    {
        return $query->orderBy('order_index');
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Helper methods
    public function hasQuiz(): bool
    {
        return $this->type === 'quiz' || $this->quiz()->exists();
    }

    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    public function isAudio(): bool
    {
        return $this->type === 'audio';
    }

    public function isPdf(): bool
    {
        return $this->type === 'pdf';
    }

    public function isExternalLink(): bool
    {
        return $this->type === 'external_link';
    }

    public function getUrl(): ?string
    {
        if (isset($this->payload['url'])) {
            return $this->payload['url'];
        }

        return null;
    }

    public function getFilePath(): ?string
    {
        if (isset($this->payload['file_path'])) {
            return $this->payload['file_path'];
        }

        return null;
    }

    public function getDurationMinutes(): int
    {
        return $this->duration_est_min ?? 0;
    }

    public function updateCompletionStats(): void
    {
        $completions = $this->progress()->where('status', 'completed')->count();
        $avgTime = $this->progress()
            ->where('status', 'completed')
            ->where('seconds_consumed', '>', 0)
            ->avg('seconds_consumed');

        $this->update([
            'completions_count' => $completions,
            'avg_completion_time_min' => $avgTime ? round($avgTime / 60, 2) : null,
        ]);
    }

    // Payload structure helpers
    public function setVideoPayload(string $url, ?string $thumbnail = null): void
    {
        $this->payload = [
            'url' => $url,
            'type' => 'video',
            'thumbnail' => $thumbnail,
        ];
    }

    public function setAudioPayload(string $url): void
    {
        $this->payload = [
            'url' => $url,
            'type' => 'audio',
        ];
    }

    public function setPdfPayload(string $filePath, ?string $fileName = null): void
    {
        $this->payload = [
            'file_path' => $filePath,
            'file_name' => $fileName,
            'type' => 'pdf',
        ];
    }

    public function setExternalLinkPayload(string $url, ?string $description = null): void
    {
        $this->payload = [
            'url' => $url,
            'description' => $description,
            'type' => 'external_link',
        ];
    }
}
