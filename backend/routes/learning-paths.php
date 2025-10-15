<?php

use App\Http\Controllers\LearningPath\CourseController;
use App\Http\Controllers\LearningPath\LearningPathController;
use App\Http\Controllers\LearningPath\TagController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Learning Path API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes for the Learning Path module including:
| - Learning Path CRUD operations
| - Course management
| - Tag management
| - Assignment management
|
*/

Route::middleware(['auth:sanctum'])->group(function () {

    // Learning Path Routes
    Route::prefix('learning-paths')->group(function () {
        // Basic CRUD operations
        Route::get('/', [LearningPathController::class, 'index']);
        Route::post('/', [LearningPathController::class, 'store']);
        Route::get('/{id}', [LearningPathController::class, 'show']);
        Route::put('/{id}', [LearningPathController::class, 'update']);
        Route::delete('/{id}', [LearningPathController::class, 'destroy']);

        // Workflow operations (4-step process)
        Route::post('/{id}/courses', [LearningPathController::class, 'addCourses']);
        Route::post('/{id}/eligibility', [LearningPathController::class, 'setEligibility']);
        Route::patch('/{id}/publish', [LearningPathController::class, 'publish']);

        // Management operations
        Route::patch('/{id}/status', [LearningPathController::class, 'updateStatus']);
        Route::get('/{id}/assignments', [LearningPathController::class, 'getAssignments']);
        Route::post('/{id}/assign', [LearningPathController::class, 'assignToEmployees']);
        Route::patch('/{id}/assignments/{assignmentId}/progress', [LearningPathController::class, 'updateProgress']);
    });

    // Course Routes (Course Library)
    Route::prefix('courses')->group(function () {
        // Course Library specific endpoints
        Route::get('/categories', [CourseController::class, 'getCategories']);
        Route::post('/external', [CourseController::class, 'storeExternal']);
        Route::post('/upload', [CourseController::class, 'storeUpload']);
        Route::patch('/{id}/status', [CourseController::class, 'updateStatus']);
        
        // Basic CRUD operations
        Route::get('/', [CourseController::class, 'index']);
        Route::post('/', [CourseController::class, 'store']); // Legacy
        Route::get('/{id}', [CourseController::class, 'show']);
        Route::put('/{id}', [CourseController::class, 'update']);
        Route::delete('/{id}', [CourseController::class, 'destroy']);
    });

    // Tag Routes
    Route::prefix('tags')->group(function () {
        Route::get('/popular', [TagController::class, 'popular']);
        Route::get('/', [TagController::class, 'index']);
        Route::post('/', [TagController::class, 'store']);
        Route::get('/{id}', [TagController::class, 'show']);
        Route::put('/{id}', [TagController::class, 'update']);
        Route::delete('/{id}', [TagController::class, 'destroy']);
    });

    // Employee-facing routes for assignments
    Route::prefix('my-learning')->group(function () {
        Route::get('/paths', [LearningPathController::class, 'myLearningPaths']);
        Route::get('/assignments', [LearningPathController::class, 'myAssignments']);
        Route::patch('/assignments/{id}/start', [LearningPathController::class, 'startAssignment']);
        Route::patch('/assignments/{id}/complete', [LearningPathController::class, 'completeAssignment']);
        Route::patch('/assignments/{id}/progress', [LearningPathController::class, 'updateMyProgress']);
    });

    // Analytics and reporting routes
    Route::prefix('learning-analytics')->group(function () {
        Route::get('/dashboard', [LearningPathController::class, 'getDashboardData']);
        Route::get('/completion-stats', [LearningPathController::class, 'getCompletionStats']);
        Route::get('/popular-paths', [LearningPathController::class, 'getPopularPaths']);
        Route::get('/employee-progress/{employeeId}', [LearningPathController::class, 'getEmployeeProgress']);
    });
});
