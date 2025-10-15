<?php

namespace App\Http\Controllers\LearningPath;

use App\Http\Controllers\Controller;
use App\Models\LearningPath\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    /**
     * List all courses with filtering
     * GET /api/courses
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Course::with(['creator']);

            // Apply filters
            if ($request->has('difficulty')) {
                $query->where('difficulty_level', $request->difficulty);
            }

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            // Apply sorting
            $sortField = $request->get('sort', 'created_at');
            $sortDirection = $request->get('direction', 'desc');

            if (in_array($sortField, ['title', 'created_at', 'updated_at', 'rating', 'price'])) {
                $query->orderBy($sortField, $sortDirection);
            }

            // Pagination
            $perPage = min($request->get('per_page', 15), 50);
            $courses = $query->paginate($perPage);

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
     * Create a new course
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
}
