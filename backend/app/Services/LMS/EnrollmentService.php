<?php

namespace App\Services\LMS;

use App\Models\LearningPath\Course;
use App\Models\LMS\Enrollment;
use App\Models\LMS\PathEnrollment;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EnrollmentService
{
    /**
     * Enroll a user in a course
     */
    public function enrollUserInCourse(
        int $employeeId,
        int $courseId,
        string $source = 'self_enroll',
        ?array $metadata = null,
        ?Carbon $expiresAt = null
    ): Enrollment {
        return DB::transaction(function () use ($employeeId, $courseId, $source, $metadata, $expiresAt) {
            // Check if already enrolled
            $existingEnrollment = Enrollment::where('employee_id', $employeeId)
                ->where('course_id', $courseId)
                ->first();

            if ($existingEnrollment) {
                return $existingEnrollment;
            }

            // Validate course is available
            $course = Course::where('id', $courseId)
                ->where('status', 'active')
                ->firstOrFail();

            // Create enrollment
            $enrollment = Enrollment::create([
                'employee_id' => $employeeId,
                'course_id' => $courseId,
                'source' => $source,
                'status' => 'not_started',
                'expires_at' => $expiresAt,
                'metadata' => $metadata,
            ]);

            return $enrollment;
        });
    }

    /**
     * Enroll a user in a learning path
     */
    public function enrollUserInPath(
        int $employeeId,
        int $pathId,
        ?Carbon $dueDate = null,
        ?array $metadata = null
    ): PathEnrollment {
        return DB::transaction(function () use ($employeeId, $pathId, $dueDate, $metadata) {
            // Check if already enrolled
            $existingEnrollment = PathEnrollment::where('employee_id', $employeeId)
                ->where('path_id', $pathId)
                ->first();

            if ($existingEnrollment) {
                return $existingEnrollment;
            }

            // Create path enrollment (this will auto-enroll in courses)
            $pathEnrollment = PathEnrollment::createForUser($employeeId, $pathId, $dueDate);

            if ($metadata) {
                $pathEnrollment->update(['metadata' => $metadata]);
            }

            return $pathEnrollment;
        });
    }

    /**
     * Get user's course enrollments with filters
     */
    public function getUserCourseEnrollments(
        int $employeeId,
        ?string $status = null,
        ?string $source = null,
        int $page = 1,
        int $perPage = 20
    ): array {
        $query = Enrollment::where('employee_id', $employeeId)
            ->with(['course.category', 'course.tags']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($source) {
            $query->where('source', $source);
        }

        $enrollments = $query->orderBy('last_activity_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'enrollments' => $enrollments->items(),
            'pagination' => [
                'current_page' => $enrollments->currentPage(),
                'per_page' => $enrollments->perPage(),
                'total' => $enrollments->total(),
                'last_page' => $enrollments->lastPage(),
                'has_more' => $enrollments->hasMorePages(),
            ],
        ];
    }

    /**
     * Get user's learning path enrollments
     */
    public function getUserPathEnrollments(
        int $employeeId,
        ?string $status = null,
        int $page = 1,
        int $perPage = 20
    ): array {
        $query = PathEnrollment::where('employee_id', $employeeId)
            ->with('path');

        if ($status) {
            $query->where('status', $status);
        }

        $enrollments = $query->orderBy('last_activity_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'path_enrollments' => $enrollments->items(),
            'pagination' => [
                'current_page' => $enrollments->currentPage(),
                'per_page' => $enrollments->perPage(),
                'total' => $enrollments->total(),
                'last_page' => $enrollments->lastPage(),
                'has_more' => $enrollments->hasMorePages(),
            ],
        ];
    }

    /**
     * Get available courses for enrollment
     */
    public function getAvailableCourses(
        int $employeeId,
        ?string $search = null,
        ?int $categoryId = null,
        ?string $difficultyLevel = null,
        ?array $tags = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $page = 1,
        int $perPage = 20
    ): array {
        $query = Course::where('status', 'active');

        // Exclude already enrolled courses
        $enrolledCourseIds = Enrollment::where('employee_id', $employeeId)
            ->pluck('course_id')
            ->toArray();

        if (! empty($enrolledCourseIds)) {
            $query->whereNotIn('id', $enrolledCourseIds);
        }

        // Apply filters
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($difficultyLevel) {
            $query->where('difficulty_level', $difficultyLevel);
        }

        if ($tags && ! empty($tags)) {
            $query->whereHas('tags', function ($q) use ($tags) {
                $q->whereIn('name', $tags);
            });
        }

        // Apply sorting
        $allowedSorts = ['created_at', 'title', 'rating', 'enrollments_count', 'duration_minutes'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $courses = $query->with(['category', 'tags'])
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'courses' => $courses->items(),
            'pagination' => [
                'current_page' => $courses->currentPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
                'last_page' => $courses->lastPage(),
                'has_more' => $courses->hasMorePages(),
            ],
        ];
    }

    /**
     * Check if user can enroll in a course
     */
    public function canUserEnrollInCourse(int $employeeId, int $courseId): array
    {
        $course = Course::findOrFail($courseId);
        $existingEnrollment = Enrollment::where('employee_id', $employeeId)
            ->where('course_id', $courseId)
            ->first();

        $canEnroll = true;
        $reason = null;

        if ($existingEnrollment) {
            $canEnroll = false;
            $reason = 'Already enrolled in this course';
        } elseif ($course->status !== 'active') {
            $canEnroll = false;
            $reason = 'Course is not currently available';
        }

        return [
            'can_enroll' => $canEnroll,
            'reason' => $reason,
            'existing_enrollment' => $existingEnrollment ? [
                'id' => $existingEnrollment->id,
                'status' => $existingEnrollment->status,
                'progress_pct' => $existingEnrollment->progress_pct,
            ] : null,
        ];
    }

    /**
     * Bulk enroll users in courses
     */
    public function bulkEnrollUsers(
        array $employeeIds,
        array $courseIds,
        string $source = 'manager_assign',
        ?array $metadata = null,
        ?Carbon $expiresAt = null
    ): array {
        $results = [];

        DB::transaction(function () use ($employeeIds, $courseIds, $source, $metadata, $expiresAt, &$results) {
            foreach ($employeeIds as $employeeId) {
                foreach ($courseIds as $courseId) {
                    try {
                        $enrollment = $this->enrollUserInCourse(
                            $employeeId,
                            $courseId,
                            $source,
                            $metadata,
                            $expiresAt
                        );

                        $results[] = [
                            'employee_id' => $employeeId,
                            'course_id' => $courseId,
                            'enrollment_id' => $enrollment->id,
                            'status' => 'success',
                            'was_already_enrolled' => $enrollment->wasRecentlyCreated === false,
                        ];
                    } catch (\Exception $e) {
                        $results[] = [
                            'employee_id' => $employeeId,
                            'course_id' => $courseId,
                            'enrollment_id' => null,
                            'status' => 'error',
                            'error' => $e->getMessage(),
                        ];
                    }
                }
            }
        });

        return $results;
    }

    /**
     * Update enrollment expiry
     */
    public function updateEnrollmentExpiry(int $enrollmentId, ?Carbon $expiresAt): Enrollment
    {
        $enrollment = Enrollment::findOrFail($enrollmentId);

        $enrollment->update([
            'expires_at' => $expiresAt,
        ]);

        return $enrollment->fresh();
    }

    /**
     * Get enrollment dashboard data for user
     */
    public function getUserDashboard(int $employeeId): array
    {
        $enrollments = Enrollment::where('employee_id', $employeeId)
            ->with('course')
            ->get();

        $pathEnrollments = PathEnrollment::where('employee_id', $employeeId)
            ->with('path')
            ->get();

        $recentActivity = $enrollments->where('last_activity_at', '>', now()->subDays(7))
            ->sortByDesc('last_activity_at')
            ->take(5);

        $upcomingDeadlines = collect()
            ->merge($enrollments->whereNotNull('expires_at')->where('expires_at', '>', now()))
            ->merge($pathEnrollments->whereNotNull('due_date')->where('due_date', '>', now()))
            ->sortBy(function ($item) {
                return $item->expires_at ?? $item->due_date;
            })
            ->take(5);

        return [
            'summary' => [
                'total_courses' => $enrollments->count(),
                'completed_courses' => $enrollments->where('status', 'completed')->count(),
                'in_progress_courses' => $enrollments->where('status', 'in_progress')->count(),
                'total_paths' => $pathEnrollments->count(),
                'completed_paths' => $pathEnrollments->where('status', 'completed')->count(),
                'overdue_items' => $pathEnrollments->where('due_date', '<', now())->where('status', '!=', 'completed')->count(),
            ],
            'recent_activity' => $recentActivity->map(function ($enrollment) {
                return [
                    'type' => 'course',
                    'id' => $enrollment->id,
                    'title' => $enrollment->course->title,
                    'progress_pct' => $enrollment->progress_pct,
                    'last_activity_at' => $enrollment->last_activity_at,
                ];
            })->values(),
            'upcoming_deadlines' => $upcomingDeadlines->map(function ($item) {
                return [
                    'type' => isset($item->path_id) ? 'path' : 'course',
                    'id' => $item->id,
                    'title' => isset($item->path) ? $item->path->title : $item->course->title,
                    'deadline' => $item->due_date ?? $item->expires_at,
                    'status' => $item->status,
                ];
            })->values(),
        ];
    }
}
