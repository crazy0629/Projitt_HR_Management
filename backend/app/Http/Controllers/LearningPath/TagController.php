<?php

namespace App\Http\Controllers\LearningPath;

use App\Http\Controllers\Controller;
use App\Models\LearningPath\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TagController extends Controller
{
    /**
     * List all tags with usage statistics
     * GET /api/tags
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Tag::with(['creator'])
                ->withCount('learningPaths');

            // Apply search filter
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('slug', 'LIKE', "%{$search}%");
                });
            }

            // Apply sorting
            $sortField = $request->get('sort', 'usage_count');
            $sortDirection = $request->get('direction', 'desc');

            if (in_array($sortField, ['name', 'created_at', 'updated_at', 'usage_count'])) {
                if ($sortField === 'usage_count') {
                    $query->orderBy('learning_paths_count', $sortDirection);
                } else {
                    $query->orderBy($sortField, $sortDirection);
                }
            }

            // Pagination
            $perPage = min($request->get('per_page', 20), 50);
            $tags = $query->paginate($perPage);

            return successResponse(
                'Tags retrieved successfully',
                $tags,
                200
            );

        } catch (\Exception $e) {
            return errorResponse('Failed to retrieve tags', $e, 500);
        }
    }

    /**
     * Create a new tag
     * POST /api/tags
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:tags,name',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return validationResponse('Validation failed', $validator, 422);
        }

        try {
            $tag = Tag::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'color' => $request->color ?? '#6366f1',
                'description' => $request->description,
                'created_by' => Auth::id(),
            ]);

            return successResponse(
                'Tag created successfully',
                $tag->load('creator'),
                201
            );

        } catch (\Exception $e) {
            return errorResponse('Failed to create tag', $e, 500);
        }
    }

    /**
     * Get tag details with learning paths
     * GET /api/tags/{id}
     */
    public function show($id): JsonResponse
    {
        try {
            $tag = Tag::with([
                'creator',
                'learningPaths.creator',
                'learningPaths.roles',
            ])->findOrFail($id);

            return successResponse(
                'Tag retrieved successfully',
                $tag,
                200
            );

        } catch (\Exception $e) {
            return errorResponse('Tag not found', $e, 404);
        }
    }

    /**
     * Update tag
     * PUT /api/tags/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:tags,name,'.$id,
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return validationResponse('Validation failed', $validator, 422);
        }

        try {
            $tag = Tag::findOrFail($id);

            $tag->update([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'color' => $request->color ?? $tag->color,
                'description' => $request->description,
            ]);

            return successResponse(
                'Tag updated successfully',
                $tag->load('creator'),
                200
            );

        } catch (\Exception $e) {
            return errorResponse('Failed to update tag', $e, 500);
        }
    }

    /**
     * Delete tag
     * DELETE /api/tags/{id}
     */
    public function destroy($id): JsonResponse
    {
        try {
            $tag = Tag::findOrFail($id);

            // Check if tag is used in any learning paths
            if ($tag->learningPaths()->exists()) {
                return invalidCredentials('Cannot delete tag that is used in learning paths', null, 403);
            }

            $tag->delete();

            return successResponse('Tag deleted successfully', null, 200);

        } catch (\Exception $e) {
            return errorResponse('Failed to delete tag', $e, 500);
        }
    }

    /**
     * Get popular tags
     * GET /api/tags/popular
     */
    public function popular(Request $request): JsonResponse
    {
        try {
            $limit = min($request->get('limit', 10), 20);

            $tags = Tag::withCount('learningPaths')
                ->having('learning_paths_count', '>', 0)
                ->orderBy('learning_paths_count', 'desc')
                ->limit($limit)
                ->get();

            return successResponse(
                'Popular tags retrieved successfully',
                $tags,
                200
            );

        } catch (\Exception $e) {
            return errorResponse('Failed to retrieve popular tags', $e, 500);
        }
    }
}
