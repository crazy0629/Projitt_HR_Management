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
     * Get learning path details
     * GET /api/learning-paths/{id}
     */
    public function show($id): JsonResponse
    {
        try {
            $learningPath = LearningPath::with([
                'roles',
                'tags',
                'courses.creator',
                'criteria',
                'assignments.employee',
                'creator',
                'updater',
                'publisher',
            ])->findOrFail($id);

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
     * List learning paths with filtering
     * GET /api/learning-paths
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = LearningPath::with(['roles', 'tags', 'creator']);

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

            if (in_array($sortField, ['name', 'created_at', 'updated_at', 'status'])) {
                $query->orderBy($sortField, $sortDirection);
            }

            // Pagination
            $perPage = min($request->get('per_page', 15), 50);
            $learningPaths = $query->paginate($perPage);

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
