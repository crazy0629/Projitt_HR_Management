<?php

namespace App\Http\Controllers\LearningPath;

use App\Http\Controllers\Controller;
use App\Models\LearningPath\Course;
use App\Models\LearningPath\LearningPath;
use App\Models\LearningPath\LearningPathLog;
use App\Models\LearningPath\Tag;
use App\Models\User\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LearningPathController extends Controller
{
    /**
     * Create a new learning path (Step 1: Basic Info)
     * POST /api/learning-paths
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:250|unique:learning_paths,name',
            'description' => 'nullable|string',
            'begin_month' => 'nullable|string|date_format:Y-m',
            'end_month' => 'nullable|string|date_format:Y-m|after_or_equal:begin_month',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:100',
            'estimated_duration_hours' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return validationResponse('Validation failed', $validator, 422);
        }

        DB::beginTransaction();
        try {
            // Create learning path
            $learningPath = LearningPath::create([
                'name' => $request->name,
                'description' => $request->description,
                'begin_month' => $request->begin_month,
                'end_month' => $request->end_month,
                'estimated_duration_hours' => $request->estimated_duration_hours,
                'status' => 'draft',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Attach roles if provided
            if ($request->has('roles') && is_array($request->roles)) {
                $learningPath->roles()->attach($request->roles);
            }

            // Create or attach tags if provided
            if ($request->has('tags') && is_array($request->tags)) {
                $tagIds = [];
                foreach ($request->tags as $tagName) {
                    $tag = Tag::firstOrCreate(
                        ['name' => $tagName],
                        ['slug' => Str::slug($tagName), 'created_by' => Auth::id()]
                    );
                    $tagIds[] = $tag->id;
                }
                $learningPath->tags()->attach($tagIds);
            }

            // Log the action
            LearningPathLog::logAction(
                $learningPath->id,
                Auth::id(),
                'created',
                $learningPath->toArray()
            );

            DB::commit();

            return successResponse(
                'Learning path created successfully',
                $learningPath->load(['roles', 'tags', 'creator']),
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return errorResponse('Failed to create learning path', $e, 500);
        }
    }

    /**
     * Get learning path details with optional view-specific data
     * GET /api/learning-paths/{id}?view=courses|employees
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $view = $request->get('view', 'overview');

            // Base query with essential relationships
            $query = LearningPath::with([
                'roles',
                'tags',
                'creator',
                'updater',
                'publisher',
            ]);

            // Add view-specific relationships
            switch ($view) {
                case 'courses':
                    $query->with([
                        'courses' => function ($query) {
                            $query->select(['courses.*'])
                                ->with(['creator:id,first_name,last_name'])
                                ->orderBy('learning_path_courses.order_index');
                        },
                    ]);
                    break;

                case 'employees':
                    $query->with([
                        'assignments' => function ($query) {
                            $query->with([
                                'employee:id,first_name,last_name,email',
                                'employee.role:id,name',
                            ])->latest();
                        },
                    ]);
                    break;

                default: // overview
                    $query->with([
                        'courses:id,title,type,duration_hours',
                        'assignments:id,learning_path_id,employee_id,status,progress_percentage',
                        'criteria',
                    ]);
                    break;
            }

            $learningPath = $query->findOrFail($id);

            // Add computed statistics
            $learningPath->statistics = [
                'total_courses' => $learningPath->courses->count(),
                'total_employees' => $learningPath->assignments->count(),
                'completed_assignments' => $learningPath->assignments->where('status', 'completed')->count(),
                'in_progress_assignments' => $learningPath->assignments->where('status', 'in_progress')->count(),
                'completion_rate' => $learningPath->assignments->count() > 0
                    ? round(($learningPath->assignments->where('status', 'completed')->count() / $learningPath->assignments->count()) * 100, 1)
                    : 0,
                'average_progress' => $learningPath->assignments->count() > 0
                    ? round($learningPath->assignments->avg('progress_percentage'), 1)
                    : 0,
            ];

            // Format view-specific data
            if ($view === 'courses') {
                $learningPath->courses->transform(function ($course) {
                    return [
                        'id' => $course->id,
                        'title' => $course->title,
                        'description' => $course->description,
                        'type' => $course->type,
                        'difficulty_level' => $course->difficulty_level,
                        'duration_hours' => $course->duration_hours,
                        'duration' => $course->duration_hours.'h',
                        'category' => $course->type, // You might want to add a category field
                        'provider' => $course->provider,
                        'creator' => $course->creator,
                        'is_required' => $course->pivot->is_required,
                        'order_index' => $course->pivot->order_index,
                        'completion_criteria' => $course->pivot->completion_criteria,
                    ];
                });
            }

            if ($view === 'employees') {
                $learningPath->employees_data = $learningPath->assignments->map(function ($assignment) {
                    return [
                        'id' => $assignment->employee->id,
                        'name' => $assignment->employee->first_name.' '.$assignment->employee->last_name,
                        'email' => $assignment->employee->email,
                        'role' => $assignment->employee->role->name ?? 'N/A',
                        'department' => $assignment->employee->role->name ?? 'N/A', // Adjust based on your schema
                        'status' => $assignment->status,
                        'progress_percentage' => $assignment->progress_percentage,
                        'assigned_at' => $assignment->assigned_at,
                        'started_at' => $assignment->started_at,
                        'completed_at' => $assignment->completed_at,
                        'due_date' => $assignment->due_date,
                    ];
                });
                // Remove the assignments relationship to avoid duplication
                unset($learningPath->assignments);
            }

            return successResponse(
                'Learning path retrieved successfully',
                $learningPath,
                200
            );

        } catch (\Exception $e) {
            return errorResponse('Learning path not found', $e, 404);
        }
    }

    /**
     * Update learning path basic info
     * PUT /api/learning-paths/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:250',
                Rule::unique('learning_paths', 'name')->ignore($id),
            ],
            'description' => 'nullable|string',
            'begin_month' => 'nullable|string|date_format:Y-m',
            'end_month' => 'nullable|string|date_format:Y-m|after_or_equal:begin_month',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:100',
            'estimated_duration_hours' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return validationResponse('Validation failed', $validator, 422);
        }

        DB::beginTransaction();
        try {
            $learningPath = LearningPath::findOrFail($id);

            // Check if user can edit
            if ($learningPath->status === 'published' && Auth::id() !== $learningPath->created_by) {
                return invalidCredentials('Cannot edit published learning path', null, 403);
            }

            $previousData = $learningPath->toArray();

            // Update learning path
            $learningPath->update([
                'name' => $request->name,
                'description' => $request->description,
                'begin_month' => $request->begin_month,
                'end_month' => $request->end_month,
                'estimated_duration_hours' => $request->estimated_duration_hours,
                'updated_by' => Auth::id(),
            ]);

            // Update roles
            if ($request->has('roles')) {
                $learningPath->roles()->sync($request->roles ?? []);
            }

            // Update tags
            if ($request->has('tags')) {
                $tagIds = [];
                foreach ($request->tags ?? [] as $tagName) {
                    $tag = Tag::firstOrCreate(
                        ['name' => $tagName],
                        ['slug' => Str::slug($tagName), 'created_by' => Auth::id()]
                    );
                    $tagIds[] = $tag->id;
                }
                $learningPath->tags()->sync($tagIds);
            }

            // Log the action
            LearningPathLog::logAction(
                $learningPath->id,
                Auth::id(),
                'updated',
                $learningPath->fresh()->toArray(),
                $previousData
            );

            DB::commit();

            return successResponse(
                'Learning path updated successfully',
                $learningPath->load(['roles', 'tags', 'creator']),
                200
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return errorResponse('Failed to update learning path', $e, 500);
        }
    }

    /**
     * Add courses to learning path (Step 2: Course Selection)
     * POST /api/learning-paths/{id}/courses
     */
    public function addCourses(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'courses' => 'required|array|min:1',
            'courses.*.course_id' => 'required|exists:courses,id',
            'courses.*.order_index' => 'required|integer|min:0',
            'courses.*.is_required' => 'nullable|boolean',
            'courses.*.completion_criteria' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return validationResponse('Validation failed', $validator, 422);
        }

        DB::beginTransaction();
        try {
            $learningPath = LearningPath::findOrFail($id);

            if ($learningPath->status === 'published') {
                return invalidCredentials('Cannot modify courses of published learning path', null, 403);
            }

            // Clear existing courses
            $learningPath->courses()->detach();

            // Add new courses
            foreach ($request->courses as $courseData) {
                $learningPath->courses()->attach($courseData['course_id'], [
                    'order_index' => $courseData['order_index'],
                    'is_required' => $courseData['is_required'] ?? true,
                    'completion_criteria' => $courseData['completion_criteria'] ?? null,
                ]);
            }

            // Update estimated duration
            $totalDuration = $learningPath->courses()->sum('duration_hours');
            $learningPath->update([
                'estimated_duration_hours' => $totalDuration,
                'updated_by' => Auth::id(),
            ]);

            // Log the action
            LearningPathLog::logAction(
                $learningPath->id,
                Auth::id(),
                'courses_updated',
                ['courses' => $request->courses]
            );

            DB::commit();

            return successResponse(
                'Courses added to learning path successfully',
                $learningPath->load(['courses']),
                200
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return errorResponse('Failed to add courses', $e, 500);
        }
    }

    /**
     * Set eligibility criteria (Step 3: Eligibility)
     * POST /api/learning-paths/{id}/eligibility
     */
    public function setEligibility(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'criteria' => 'required|array|min:1',
            'criteria.*.field' => 'required|string|max:100',
            'criteria.*.operator' => 'required|in:=,!=,IN,NOT IN,>,<,>=,<=,LIKE,NOT LIKE',
            'criteria.*.value' => 'required',
            'criteria.*.connector' => 'nullable|in:AND,OR',
            'criteria.*.group_index' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return validationResponse('Validation failed', $validator, 422);
        }

        DB::beginTransaction();
        try {
            $learningPath = LearningPath::findOrFail($id);

            if ($learningPath->status === 'published') {
                return invalidCredentials('Cannot modify eligibility of published learning path', null, 403);
            }

            // Clear existing criteria
            $learningPath->criteria()->delete();

            // Add new criteria
            foreach ($request->criteria as $criteriaData) {
                $learningPath->criteria()->create([
                    'field' => $criteriaData['field'],
                    'operator' => $criteriaData['operator'],
                    'value' => is_array($criteriaData['value'])
                        ? json_encode($criteriaData['value'])
                        : $criteriaData['value'],
                    'connector' => $criteriaData['connector'] ?? 'AND',
                    'group_index' => $criteriaData['group_index'] ?? 0,
                ]);
            }

            // Pre-compute eligible employees based on criteria
            $this->computeEligibleEmployees($learningPath);

            // Log the action
            LearningPathLog::logAction(
                $learningPath->id,
                Auth::id(),
                'eligibility_updated',
                ['criteria' => $request->criteria]
            );

            DB::commit();

            return successResponse(
                'Eligibility criteria set successfully',
                $learningPath->load(['criteria', 'assignments.employee']),
                200
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return errorResponse('Failed to set eligibility criteria', $e, 500);
        }
    }

    /**
     * Publish learning path (Step 4: Review & Publish)
     * PATCH /api/learning-paths/{id}/publish
     */
    public function publish(Request $request, $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $learningPath = LearningPath::findOrFail($id);

            if (! $learningPath->canBePublished()) {
                return invalidCredentials('Learning path cannot be published. Ensure it has courses.', null, 400);
            }

            if ($learningPath->status === 'published') {
                return invalidCredentials('Learning path is already published', null, 400);
            }

            $previousData = $learningPath->toArray();

            // Update status to published
            $learningPath->update([
                'status' => 'published',
                'published_by' => Auth::id(),
                'published_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            // Log the action
            LearningPathLog::logAction(
                $learningPath->id,
                Auth::id(),
                'published',
                $learningPath->fresh()->toArray(),
                $previousData
            );

            DB::commit();

            // TODO: Send notifications to eligible employees
            // This could be implemented as a job or event listener

            return successResponse(
                'Learning path published successfully',
                $learningPath->load(['assignments.employee']),
                200
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return errorResponse('Failed to publish learning path', $e, 500);
        }
    }

    /**
     * List learning paths with filtering and management dashboard data
     * GET /api/learning-paths
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = LearningPath::with(['roles', 'tags', 'creator'])
                ->withCount([
                    'courses',
                    'assignments',
                    'assignments as completed_assignments' => function ($query) {
                        $query->where('status', 'completed');
                    },
                ]);

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('role')) {
                $query->whereHas('roles', function ($q) use ($request) {
                    $q->whereIn('name', (array) $request->role);
                });
            }

            if ($request->has('tag')) {
                $query->whereHas('tags', function ($q) use ($request) {
                    $q->whereIn('name', (array) $request->tag);
                });
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            // Apply sorting
            $sortField = $request->get('sort', 'created_at');
            $sortDirection = $request->get('direction', 'desc');

            $allowedSortFields = ['name', 'created_at', 'updated_at', 'status', 'completion_rate'];
            if (in_array($sortField, $allowedSortFields)) {
                if ($sortField === 'completion_rate') {
                    // Sort by completion rate (calculated)
                    $query->orderByRaw('CASE 
                        WHEN assignments_count > 0 
                        THEN (completed_assignments_count * 100.0 / assignments_count) 
                        ELSE 0 
                    END '.$sortDirection);
                } else {
                    $query->orderBy($sortField, $sortDirection);
                }
            }

            // Pagination
            $perPage = min($request->get('per_page', 15), 50);
            $learningPaths = $query->paginate($perPage);

            // Add computed fields for management dashboard
            $learningPaths->getCollection()->transform(function ($learningPath) {
                // Calculate completion rate
                $completionRate = $learningPath->assignments_count > 0
                    ? round(($learningPath->completed_assignments_count / $learningPath->assignments_count) * 100, 1)
                    : 0;

                // Add management dashboard fields
                $learningPath->employee_count = $learningPath->assignments_count;
                $learningPath->course_count = $learningPath->courses_count;
                $learningPath->completion_rate = $completionRate;

                // Format roles and tags for display
                $learningPath->role_names = $learningPath->roles->pluck('name')->toArray();
                $learningPath->tag_names = $learningPath->tags->pluck('name')->toArray();

                return $learningPath;
            });

            return successResponse(
                'Learning paths retrieved successfully',
                $learningPaths,
                200
            );

        } catch (\Exception $e) {
            return errorResponse('Failed to retrieve learning paths', $e, 500);
        }
    }

    /**
     * Update learning path status (Move to Draft/Active/Archived)
     * PATCH /api/learning-paths/{id}/status
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:draft,published,archived',
        ]);

        if ($validator->fails()) {
            return validationResponse('Validation failed', $validator, 422);
        }

        DB::beginTransaction();
        try {
            $learningPath = LearningPath::findOrFail($id);
            $previousStatus = $learningPath->status;
            $newStatus = $request->status;

            // Validate status transitions
            if ($previousStatus === $newStatus) {
                return invalidCredentials("Learning path is already {$newStatus}", null, 400);
            }

            // Special validation for status transitions
            if ($newStatus === 'published' && ! $learningPath->canBePublished()) {
                return invalidCredentials('Learning path cannot be published. Ensure it has courses.', null, 400);
            }

            $previousData = $learningPath->toArray();

            // Update status and related fields
            $updateData = [
                'status' => $newStatus,
                'updated_by' => Auth::id(),
            ];

            // Handle specific status transitions
            switch ($newStatus) {
                case 'published':
                    $updateData['published_by'] = Auth::id();
                    $updateData['published_at'] = now();
                    break;

                case 'draft':
                    // When moving to draft, optionally pause active assignments
                    $learningPath->assignments()
                        ->where('status', 'assigned')
                        ->update(['status' => 'paused']);
                    break;

                case 'archived':
                    // When archiving, mark assignments as inactive
                    $learningPath->assignments()
                        ->whereNotIn('status', ['completed'])
                        ->update(['status' => 'inactive']);
                    break;
            }

            $learningPath->update($updateData);

            // Log the action
            LearningPathLog::logAction(
                $learningPath->id,
                Auth::id(),
                'status_changed',
                [
                    'from_status' => $previousStatus,
                    'to_status' => $newStatus,
                    'learning_path' => $learningPath->fresh()->toArray(),
                ],
                $previousData
            );

            DB::commit();

            return successResponse(
                "Learning path status updated to {$newStatus} successfully",
                $learningPath->load(['roles', 'tags', 'creator']),
                200
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return errorResponse('Failed to update learning path status', $e, 500);
        }
    }

    /**
     * Get learning path assignments with detailed progress
     * GET /api/learning-paths/{id}/assignments
     */
    public function getAssignments(Request $request, $id): JsonResponse
    {
        try {
            $learningPath = LearningPath::findOrFail($id);

            $assignments = $learningPath->assignments()
                ->with([
                    'employee:id,first_name,last_name,email',
                    'employee.role:id,name',
                    'assignedBy:id,first_name,last_name',
                ])
                ->latest()
                ->get()
                ->map(function ($assignment) {
                    return [
                        'id' => $assignment->id,
                        'employee' => [
                            'id' => $assignment->employee->id,
                            'name' => $assignment->employee->first_name.' '.$assignment->employee->last_name,
                            'email' => $assignment->employee->email,
                            'role' => $assignment->employee->role->name ?? 'N/A',
                        ],
                        'status' => $assignment->status,
                        'progress_percentage' => $assignment->progress_percentage,
                        'assigned_at' => $assignment->assigned_at,
                        'started_at' => $assignment->started_at,
                        'completed_at' => $assignment->completed_at,
                        'due_date' => $assignment->due_date,
                        'assigned_by' => $assignment->assignedBy
                            ? $assignment->assignedBy->first_name.' '.$assignment->assignedBy->last_name
                            : 'System',
                        'notes' => $assignment->notes,
                        'is_overdue' => $assignment->isOverdue(),
                    ];
                });

            return successResponse(
                'Learning path assignments retrieved successfully',
                [
                    'learning_path' => [
                        'id' => $learningPath->id,
                        'name' => $learningPath->name,
                        'status' => $learningPath->status,
                    ],
                    'assignments' => $assignments,
                    'statistics' => [
                        'total' => $assignments->count(),
                        'assigned' => $assignments->where('status', 'assigned')->count(),
                        'in_progress' => $assignments->where('status', 'in_progress')->count(),
                        'completed' => $assignments->where('status', 'completed')->count(),
                        'overdue' => $assignments->where('is_overdue', true)->count(),
                    ],
                ],
                200
            );

        } catch (\Exception $e) {
            return errorResponse('Failed to retrieve assignments', $e, 500);
        }
    }

    /**
     * Manually assign employees to learning path
     * POST /api/learning-paths/{id}/assign
     */
    public function assignToEmployees(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:users,id',
            'due_date' => 'nullable|date|after:today',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return validationResponse('Validation failed', $validator, 422);
        }

        DB::beginTransaction();
        try {
            $learningPath = LearningPath::findOrFail($id);

            if ($learningPath->status !== 'published') {
                return invalidCredentials('Can only assign employees to published learning paths', null, 403);
            }

            $assignedCount = 0;
            $alreadyAssigned = [];

            foreach ($request->employee_ids as $employeeId) {
                $assignment = $learningPath->assignments()
                    ->where('employee_id', $employeeId)
                    ->first();

                if ($assignment) {
                    $alreadyAssigned[] = $employeeId;

                    continue;
                }

                $learningPath->assignments()->create([
                    'employee_id' => $employeeId,
                    'status' => 'assigned',
                    'assigned_at' => now(),
                    'assigned_by' => Auth::id(),
                    'due_date' => $request->due_date,
                    'notes' => $request->notes,
                    'progress_percentage' => 0,
                ]);

                $assignedCount++;
            }

            // Log the action
            LearningPathLog::logAction(
                $learningPath->id,
                Auth::id(),
                'employees_assigned',
                [
                    'assigned_employee_ids' => $request->employee_ids,
                    'assigned_count' => $assignedCount,
                    'already_assigned' => $alreadyAssigned,
                    'due_date' => $request->due_date,
                    'notes' => $request->notes,
                ]
            );

            DB::commit();

            return successResponse(
                "Successfully assigned {$assignedCount} employees to learning path",
                [
                    'assigned_count' => $assignedCount,
                    'already_assigned_count' => count($alreadyAssigned),
                    'learning_path' => $learningPath->load(['assignments.employee']),
                ],
                200
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return errorResponse('Failed to assign employees', $e, 500);
        }
    }

    /**
     * Delete learning path
     * DELETE /api/learning-paths/{id}
     */
    public function destroy($id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $learningPath = LearningPath::findOrFail($id);

            if ($learningPath->status === 'published') {
                return invalidCredentials('Cannot delete published learning path', null, 403);
            }

            $previousData = $learningPath->toArray();

            // Log the action before deletion
            LearningPathLog::logAction(
                $learningPath->id,
                Auth::id(),
                'deleted',
                null,
                $previousData
            );

            $learningPath->delete();

            DB::commit();

            return successResponse('Learning path deleted successfully', null, 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return errorResponse('Failed to delete learning path', $e, 500);
        }
    }

    /**
     * Compute eligible employees based on criteria
     */
    private function computeEligibleEmployees(LearningPath $learningPath)
    {
        $criteria = $learningPath->criteria;

        if ($criteria->isEmpty()) {
            return;
        }

        // Build query based on criteria
        $query = User::query();

        foreach ($criteria as $criterion) {
            $field = $criterion->field;
            $operator = $criterion->operator;
            $value = $criterion->getDecodedValue();

            // This is a simplified example - you'd need to implement
            // proper field mapping based on your user schema
            switch ($field) {
                case 'role':
                    $query->whereHas('role', function ($q) use ($operator, $value) {
                        if ($operator === 'IN') {
                            $q->whereIn('name', $value);
                        } else {
                            $q->where('name', $operator, $value);
                        }
                    });
                    break;

                    // Add more field mappings as needed
            }
        }

        $eligibleEmployees = $query->get();

        // Create assignments for eligible employees
        foreach ($eligibleEmployees as $employee) {
            $learningPath->assignments()->updateOrCreate(
                ['employee_id' => $employee->id],
                [
                    'status' => 'assigned',
                    'assigned_at' => now(),
                    'assigned_by' => Auth::id(),
                ]
            );
        }
    }
}
