<?php

namespace App\Models\LMS;

use App\Models\LearningPath\LearningPath;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PathEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'path_id',
        'status',
        'progress_pct',
        'completed_courses',
        'total_courses',
        'assigned_at',
        'started_at',
        'completed_at',
        'last_activity_at',
        'due_date',
        'metadata',
    ];

    protected $casts = [
        'progress_pct' => 'integer',
        'completed_courses' => 'integer',
        'total_courses' => 'integer',
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'due_date' => 'datetime',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'status' => 'assigned',
        'progress_pct' => 0,
        'completed_courses' => 0,
        'total_courses' => 0,
    ];

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function path(): BelongsTo
    {
        return $this->belongsTo(LearningPath::class, 'path_id');
    }

    public function courseEnrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'employee_id', 'employee_id')
            ->whereHas('course.learningPathCourses', function ($query) {
                $query->where('learning_path_id', $this->path_id);
            });
    }

    // Scopes
    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeAbandoned($query)
    {
        return $query->where('status', 'abandoned');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', ['completed', 'abandoned']);
    }

    public function scopeWithPath($query)
    {
        return $query->with('path');
    }

    // Helper methods
    public function isAssigned(): bool
    {
        return $this->status === 'assigned';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isAbandoned(): bool
    {
        return $this->status === 'abandoned';
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && ! $this->isCompleted();
    }

    public function getDaysUntilDue(): ?int
    {
        if (! $this->due_date) {
            return null;
        }

        return Carbon::now()->diffInDays($this->due_date, false);
    }

    public function start(): void
    {
        if ($this->isAssigned()) {
            $this->update([
                'status' => 'in_progress',
                'started_at' => now(),
                'last_activity_at' => now(),
            ]);

            // Log event
            LMSEvent::logEvent(
                $this->employee_id,
                'path_started',
                null,
                null,
                $this->path_id,
                ['path_enrollment_id' => $this->id]
            );
        }
    }

    public function updateProgress(): void
    {
        // Get all courses in this learning path
        $pathCourses = $this->path->courses()->get();
        $totalCourses = $pathCourses->count();

        if ($totalCourses === 0) {
            return;
        }

        // Count completed course enrollments for this user
        $completedCourses = 0;
        foreach ($pathCourses as $course) {
            $enrollment = Enrollment::where('employee_id', $this->employee_id)
                ->where('course_id', $course->id)
                ->where('status', 'completed')
                ->first();

            if ($enrollment) {
                $completedCourses++;
            }
        }

        $progressPct = round(($completedCourses / $totalCourses) * 100);

        $this->update([
            'progress_pct' => $progressPct,
            'completed_courses' => $completedCourses,
            'total_courses' => $totalCourses,
            'last_activity_at' => now(),
        ]);

        // Start if not started
        if ($this->isAssigned() && $completedCourses > 0) {
            $this->start();
        }

        // Check if path is complete
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
            'path_completed',
            null,
            null,
            $this->path_id,
            [
                'path_enrollment_id' => $this->id,
                'completion_time' => $this->getCompletionTime(),
                'total_courses' => $this->total_courses,
            ]
        );

        // Generate certificate if applicable
        $this->generateCertificateIfEligible();
    }

    public function abandon(): void
    {
        $this->update([
            'status' => 'abandoned',
            'last_activity_at' => now(),
        ]);

        // Log event
        LMSEvent::logEvent(
            $this->employee_id,
            'path_abandoned',
            null,
            null,
            $this->path_id,
            ['path_enrollment_id' => $this->id]
        );
    }

    public function getCompletionTime(): ?int
    {
        if (! $this->started_at || ! $this->completed_at) {
            return null;
        }

        return $this->started_at->diffInDays($this->completed_at);
    }

    public function getTotalTimeSpent(): int
    {
        $totalSeconds = 0;
        $pathCourses = $this->path->courses()->pluck('id');

        $enrollments = Enrollment::where('employee_id', $this->employee_id)
            ->whereIn('course_id', $pathCourses)
            ->get();

        foreach ($enrollments as $enrollment) {
            $totalSeconds += $enrollment->getTotalTimeSpent();
        }

        return $totalSeconds;
    }

    public function getNextCourse(): ?object
    {
        $pathCourses = $this->path->courses()->orderBy('learning_path_courses.order_index')->get();

        foreach ($pathCourses as $course) {
            $enrollment = Enrollment::where('employee_id', $this->employee_id)
                ->where('course_id', $course->id)
                ->first();

            if (! $enrollment || ! $enrollment->isCompleted()) {
                return $course;
            }
        }

        return null;
    }

    public function getCourseEnrollmentStatus(): array
    {
        $pathCourses = $this->path->courses()->orderBy('learning_path_courses.order_index')->get();
        $statuses = [];

        foreach ($pathCourses as $course) {
            $enrollment = Enrollment::where('employee_id', $this->employee_id)
                ->where('course_id', $course->id)
                ->first();

            $statuses[] = [
                'course_id' => $course->id,
                'course_title' => $course->title,
                'enrollment_id' => $enrollment?->id,
                'status' => $enrollment?->status ?? 'not_enrolled',
                'progress_pct' => $enrollment?->progress_pct ?? 0,
                'completed_at' => $enrollment?->completed_at,
                'is_required' => $course->pivot->is_required ?? true,
            ];
        }

        return $statuses;
    }

    public function enrollInNextCourses(): array
    {
        $enrolled = [];
        $pathCourses = $this->path->courses()->orderBy('learning_path_courses.order_index')->get();

        foreach ($pathCourses as $course) {
            $existingEnrollment = Enrollment::where('employee_id', $this->employee_id)
                ->where('course_id', $course->id)
                ->first();

            if (! $existingEnrollment) {
                $enrollment = Enrollment::create([
                    'employee_id' => $this->employee_id,
                    'course_id' => $course->id,
                    'source' => 'path',
                    'metadata' => [
                        'path_id' => $this->path_id,
                        'path_enrollment_id' => $this->id,
                    ],
                ]);

                $enrolled[] = $enrollment;
            }
        }

        return $enrolled;
    }

    private function generateCertificateIfEligible(): void
    {
        // Check if learning path has certificate enabled
        $pathMetadata = $this->path->metadata ?? [];
        if (isset($pathMetadata['certificate_enabled']) && $pathMetadata['certificate_enabled']) {
            Certificate::generateForLearningPath($this->employee_id, $this->path_id);
        }
    }

    public static function createForUser(int $employeeId, int $pathId, ?Carbon $dueDate = null): self
    {
        $enrollment = static::create([
            'employee_id' => $employeeId,
            'path_id' => $pathId,
            'assigned_at' => now(),
            'due_date' => $dueDate,
        ]);

        // Auto-enroll in courses
        $enrollment->enrollInNextCourses();

        // Update initial progress
        $enrollment->updateProgress();

        // Log event
        LMSEvent::logEvent(
            $employeeId,
            'path_enrolled',
            null,
            null,
            $pathId,
            ['path_enrollment_id' => $enrollment->id]
        );

        return $enrollment;
    }
}
