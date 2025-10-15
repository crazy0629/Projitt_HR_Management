<?php

namespace App\Http\Controllers\LearningPath;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\LearningPath\Course;
use App\Models\LearningPath\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    /**
     * List all courses with Course Library filtering
     * GET /api/courses
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Course::with(['creator', 'category', 'tags']);

            // Filter by status (active/archived)
            if ($request->has('status')) {
                $query->where('status', $request->status);
            } else {
                $query->active(); // Default to active courses only
            }

            // Filter by category
            if ($request->has('category')) {
                $query->byCategory($request->category);
            }

            // Filter by type (video, text, external_link, file_upload)
            if ($request->has('type')) {
                $query->byType($request->type);
            }

            // Filter by tag
            if ($request->has('tag')) {
                $query->withTag($request->tag);
            }

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%")
                        ->orWhere('instructor', 'LIKE', "%{$search}%");
                });
            }

            // Apply sorting
            $sortField = $request->get('sort', 'created_at');
            $sortDirection = $request->get('direction', 'desc');

            $allowedSorts = ['title', 'created_at', 'updated_at', 'duration_minutes', 'learning_paths_count', 'assigned_users_count'];
            if (in_array($sortField, $allowedSorts)) {
                $query->orderBy($sortField, $sortDirection);
            }

            // Pagination
            $perPage = min($request->get('per_page', 25), 50);
            $courses = $query->paginate($perPage);

            // Transform data for Course Library UI
            $courses->getCollection()->transform(function ($course) {
                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'category' => $course->category ? $course->category->name : null,
                    'type' => $course->getTypeLabel(),
                    'duration' => $course->getFormattedDurationAttribute(),
                    'assigned_paths' => $course->learning_paths_count,
                    'assigned_users' => $course->assigned_users_count,
                    'tags' => $course->tags->pluck('name')->toArray(),
                    'status' => $course->status,
                    'created_at' => $course->created_at,
                    'url' => $course->getDisplayUrl(),
                ];
            });

            return successResponse(
                'Courses retrieved successfully',
                $courses,
                200
            );

        } catch (\Exception $e) {
            return errorResponse('Failed to retrieve courses', $e, 500);
        }
    }

    /**
     * Create a new course via external link
     * POST /api/courses/external
     */
    public function storeExternal(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255|unique:courses,title',
            'description' => 'nullable|string',
            'url' => 'required|url',
            'duration' => 'nullable|integer|min:1|max:600', // Duration in minutes
            'category_id' => 'nullable|exists:categories,id',
            'difficulty_level' => 'nullable|in:beginner,intermediate,advanced',
            'instructor' => 'nullable|string|max:150',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        if ($validator->fails()) {
            return validationResponse('Validation failed', $validator, 422);
        }

        try {
            $course = Course::create([
                'title' => $request->title,
                'description' => $request->description,
                'type' => 'external_link',
                'url' => $request->url,
                'duration_minutes' => $request->duration,
                'category_id' => $request->category_id,
                'difficulty_level' => $request->difficulty_level ?? 'beginner',
                'instructor' => $request->instructor,
                'status' => 'active',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Sync tags if provided
            if ($request->has('tags') && is_array($request->tags)) {
                $this->syncTags($course, $request->tags);
            }

            return successResponse(
                'External course created successfully',
                $course->load(['creator', 'category', 'tags']),
                201
            );

        } catch (\Exception $e) {
            return errorResponse('Failed to create external course', $e, 500);
        }
    }

    /**
     * Create a new course via file upload
     * POST /api/courses/upload
     */
    public function storeUpload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255|unique:courses,title',
            'description' => 'nullable|string',
            'file' => 'required|file|mimes:pdf,mp4,mov,avi,wmv,doc,docx,ppt,pptx|max:102400', // Max 100MB
            'duration' => 'nullable|integer|min:1|max:600', // Duration in minutes
            'category_id' => 'nullable|exists:categories,id',
            'difficulty_level' => 'nullable|in:beginner,intermediate,advanced',
            'instructor' => 'nullable|string|max:150',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        if ($validator->fails()) {
            return validationResponse('Validation failed', $validator, 422);
        }

        try {
            $uploadedFile = $request->file('file');

            // Generate unique filename
            $filename = time().'_'.Str::random(10).'.'.$uploadedFile->getClientOriginalExtension();
            $filePath = $uploadedFile->storeAs('courses', $filename, 'public');

            $course = Course::create([
                'title' => $request->title,
                'description' => $request->description,
                'type' => 'file_upload',
                'file_path' => $filePath,
                'file_type' => $uploadedFile->getMimeType(),
                'file_size' => $uploadedFile->getSize(),
                'duration_minutes' => $request->duration,
                'category_id' => $request->category_id,
                'difficulty_level' => $request->difficulty_level ?? 'beginner',
                'instructor' => $request->instructor,
                'status' => 'active',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Sync tags if provided
            if ($request->has('tags') && is_array($request->tags)) {
                $this->syncTags($course, $request->tags);
            }

            return successResponse(
                'Course file uploaded successfully',
                $course->load(['creator', 'category', 'tags']),
                201
            );

        } catch (\Exception $e) {
            return errorResponse('Failed to upload course file', $e, 500);
        }
    }

    /**
     * Create a new course (legacy method)
     * POST /api/courses
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:online,offline,hybrid',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced',
            'duration_hours' => 'required|integer|min:1',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'provider' => 'nullable|string|max:255',
            'external_url' => 'nullable|url',
            'materials' => 'nullable|array',
            'materials.*' => 'string',
            'prerequisites' => 'nullable|array',
            'prerequisites.*' => 'string',
        ]);

        if ($validator->fails()) {
            return validationResponse('Validation failed', $validator, 422);
        }

        try {
            $course = Course::create([
                'title' => $request->title,
                'description' => $request->description,
                'type' => $request->type,
                'difficulty_level' => $request->difficulty_level,
                'duration_hours' => $request->duration_hours,
                'price' => $request->price,
                'currency' => $request->currency ?? 'USD',
                'provider' => $request->provider,
                'external_url' => $request->external_url,
                'materials' => $request->materials,
                'prerequisites' => $request->prerequisites,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            return successResponse(
                'Course created successfully',
                $course->load('creator'),
                201
            );

        } catch (\Exception $e) {
            return errorResponse('Failed to create course', $e, 500);
        }
    }

    /**
     * Get course details
     * GET /api/courses/{id}
     */
    public function show($id): JsonResponse
    {
        try {
            $course = Course::with(['creator', 'updater'])->findOrFail($id);

            return successResponse(
                'Course retrieved successfully',
                $course,
                200
            );

        } catch (\Exception $e) {
            return errorResponse('Course not found', $e, 404);
        }
    }

    /**
     * Update course
     * PUT /api/courses/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:online,offline,hybrid',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced',
            'duration_hours' => 'required|integer|min:1',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'provider' => 'nullable|string|max:255',
            'external_url' => 'nullable|url',
            'materials' => 'nullable|array',
            'materials.*' => 'string',
            'prerequisites' => 'nullable|array',
            'prerequisites.*' => 'string',
        ]);

        if ($validator->fails()) {
            return validationResponse('Validation failed', $validator, 422);
        }

        try {
            $course = Course::findOrFail($id);

            $course->update([
                'title' => $request->title,
                'description' => $request->description,
                'type' => $request->type,
                'difficulty_level' => $request->difficulty_level,
                'duration_hours' => $request->duration_hours,
                'price' => $request->price,
                'currency' => $request->currency ?? 'USD',
                'provider' => $request->provider,
                'external_url' => $request->external_url,
                'materials' => $request->materials,
                'prerequisites' => $request->prerequisites,
                'updated_by' => Auth::id(),
            ]);

            return successResponse(
                'Course updated successfully',
                $course->load(['creator', 'updater']),
                200
            );

        } catch (\Exception $e) {
            return errorResponse('Failed to update course', $e, 500);
        }
    }

    /**
     * Delete course
     * DELETE /api/courses/{id}
     */
    public function destroy($id): JsonResponse
    {
        try {
            $course = Course::findOrFail($id);

            // Check if course is used in any learning paths
            if ($course->learningPaths()->exists()) {
                return invalidCredentials('Cannot delete course that is used in learning paths', null, 403);
            }

            $course->delete();

            return successResponse('Course deleted successfully', null, 200);

        } catch (\Exception $e) {
            return errorResponse('Failed to delete course', $e, 500);
        }
    }

    /**
     * Update course status (active/archived)
     * PATCH /api/courses/{id}/status
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,archived',
        ]);

        if ($validator->fails()) {
            return validationResponse('Validation failed', $validator, 422);
        }

        try {
            $course = Course::findOrFail($id);

            $course->update([
                'status' => $request->status,
                'updated_by' => Auth::id(),
            ]);

            return successResponse(
                'Course status updated successfully',
                $course->load(['creator', 'category', 'tags']),
                200
            );

        } catch (\Exception $e) {
            return errorResponse('Failed to update course status', $e, 500);
        }
    }

    /**
     * Get categories for dropdown
     * GET /api/courses/categories
     */
    public function getCategories(): JsonResponse
    {
        try {
            $categories = Category::active()->ordered()->get(['id', 'name', 'color']);

            return successResponse(
                'Categories retrieved successfully',
                $categories,
                200
            );

        } catch (\Exception $e) {
            return errorResponse('Failed to retrieve categories', $e, 500);
        }
    }

    /**
     * Sync tags for a course
     */
    private function syncTags(Course $course, array $tagNames): void
    {
        $tagIds = [];

        foreach ($tagNames as $tagName) {
            $tag = Tag::firstOrCreate(
                ['name' => trim($tagName)],
                ['created_by' => Auth::id()]
            );
            $tagIds[] = $tag->id;
        }

        $course->tags()->sync($tagIds);
    }
}
