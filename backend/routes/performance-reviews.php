<?php

use App\Http\Controllers\PerformanceReview\PerformanceCycleController;
use App\Http\Controllers\PerformanceReview\PerformanceReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Performance Review Routes
|--------------------------------------------------------------------------
|
| Here are the routes for the Performance Reviews module.
| This includes cycle management, review submissions, AI feedback,
| succession planning, and comprehensive analytics.
|
*/

Route::middleware(['auth:sanctum'])->prefix('performance-reviews')->group(function () {

    // Performance Review Cycles Management
    Route::prefix('cycles')->group(function () {
        Route::get('/', [PerformanceCycleController::class, 'index']); // List all cycles
        Route::post('/', [PerformanceCycleController::class, 'store']); // Create new cycle
        Route::get('/{id}', [PerformanceCycleController::class, 'show']); // Get cycle details
        Route::put('/{id}', [PerformanceCycleController::class, 'update']); // Update cycle (draft only)
        Route::delete('/{id}', [PerformanceCycleController::class, 'destroy']); // Delete cycle

        // Cycle State Management
        Route::post('/{id}/activate', [PerformanceCycleController::class, 'activate']); // Activate cycle
        Route::post('/{id}/complete', [PerformanceCycleController::class, 'complete']); // Complete cycle

        // Employee Management
        Route::post('/{id}/employees', [PerformanceCycleController::class, 'addEmployees']); // Add employees to cycle

        // Analytics
        Route::get('/{id}/analytics', [PerformanceCycleController::class, 'analytics']); // Cycle analytics
    });

    // Performance Reviews Management
    Route::prefix('reviews')->group(function () {
        Route::get('/', [PerformanceReviewController::class, 'index']); // List all reviews
        Route::get('/{id}', [PerformanceReviewController::class, 'show']); // Get review details

        // Review Scoring
        Route::post('/{reviewId}/scores', [PerformanceReviewController::class, 'submitScore']); // Submit review scores

        // AI and Manager Feedback
        Route::post('/{reviewId}/ai-feedback', [PerformanceReviewController::class, 'generateAIFeedback']); // Generate AI feedback
        Route::post('/{reviewId}/manager-summary', [PerformanceReviewController::class, 'addManagerSummary']); // Add manager summary

        // Performance Actions & Succession Planning
        Route::post('/{reviewId}/actions', [PerformanceReviewController::class, 'createActions']); // Create performance actions

        // Reviewer Dashboard
        Route::get('/assigned/me', [PerformanceReviewController::class, 'myAssignedReviews']); // Reviews assigned to current user
    });

    // Analytics and Reporting
    Route::prefix('analytics')->group(function () {
        Route::get('/', [PerformanceReviewController::class, 'analytics']); // Performance analytics
        Route::get('/succession-candidates', [PerformanceReviewController::class, 'successionCandidates']); // Succession planning candidates
    });
});

/*
|--------------------------------------------------------------------------
| API Endpoint Summary
|--------------------------------------------------------------------------
|
| Performance Review Cycles:
| GET    /performance-reviews/cycles                     - List cycles
| POST   /performance-reviews/cycles                     - Create cycle
| GET    /performance-reviews/cycles/{id}                - Get cycle
| PUT    /performance-reviews/cycles/{id}                - Update cycle
| DELETE /performance-reviews/cycles/{id}                - Delete cycle
| POST   /performance-reviews/cycles/{id}/activate       - Activate cycle
| POST   /performance-reviews/cycles/{id}/complete       - Complete cycle
| POST   /performance-reviews/cycles/{id}/employees      - Add employees
| GET    /performance-reviews/cycles/{id}/analytics      - Cycle analytics
|
| Performance Reviews:
| GET    /performance-reviews/reviews                    - List reviews
| GET    /performance-reviews/reviews/{id}               - Get review
| POST   /performance-reviews/reviews/{id}/scores        - Submit scores
| POST   /performance-reviews/reviews/{id}/ai-feedback   - Generate AI feedback
| POST   /performance-reviews/reviews/{id}/manager-summary - Add manager summary
| POST   /performance-reviews/reviews/{id}/actions       - Create actions
| GET    /performance-reviews/reviews/assigned/me        - My assigned reviews
|
| Analytics & Reporting:
| GET    /performance-reviews/analytics                  - Performance analytics
| GET    /performance-reviews/analytics/succession-candidates - Succession candidates
|
*/
