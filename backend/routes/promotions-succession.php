<?php

use App\Http\Controllers\Talent\PromotionController;
use App\Http\Controllers\Talent\SuccessionPlanningController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Promotions & Succession Planning API Routes
|--------------------------------------------------------------------------
*/

// Promotion Management Routes
Route::prefix('promotions')->group(function () {
    Route::get('/', [PromotionController::class, 'index']);
    Route::post('/', [PromotionController::class, 'store']);
    Route::get('/pending-approvals', [PromotionController::class, 'pendingApprovals']);
    Route::get('/stats', [PromotionController::class, 'stats']);
    Route::get('/workflows', [PromotionController::class, 'workflows']);

    Route::prefix('{id}')->group(function () {
        Route::get('/', [PromotionController::class, 'show']);
        Route::put('/', [PromotionController::class, 'update']);
        Route::post('/submit', [PromotionController::class, 'submit']);
        Route::post('/withdraw', [PromotionController::class, 'withdraw']);
        Route::get('/timeline', [PromotionController::class, 'timeline']);
    });

    Route::post('/approvals/{approvalId}/process', [PromotionController::class, 'processApproval']);
});

// Succession Planning Routes
Route::prefix('succession')->group(function () {
    Route::get('/dashboard', [SuccessionPlanningController::class, 'dashboard']);
    Route::get('/analytics', [SuccessionPlanningController::class, 'analytics']);

    // Critical Roles Management
    Route::prefix('roles')->group(function () {
        Route::get('/', [SuccessionPlanningController::class, 'getRoles']);
        Route::post('/', [SuccessionPlanningController::class, 'createRole']);
        Route::get('/{id}', [SuccessionPlanningController::class, 'getRole']);
        Route::put('/{id}', [SuccessionPlanningController::class, 'updateRole']);
        Route::get('/{id}/plan', [SuccessionPlanningController::class, 'getSuccessionPlan']);
    });

    // Succession Candidates Management
    Route::prefix('candidates')->group(function () {
        Route::get('/', [SuccessionPlanningController::class, 'getCandidates']);
        Route::post('/', [SuccessionPlanningController::class, 'addCandidate']);
        Route::get('/{id}', [SuccessionPlanningController::class, 'getCandidate']);
        Route::put('/{id}', [SuccessionPlanningController::class, 'updateCandidate']);
        Route::put('/{id}/readiness', [SuccessionPlanningController::class, 'updateReadiness']);
        Route::post('/{id}/learning-path', [SuccessionPlanningController::class, 'assignLearningPath']);
        Route::put('/{id}/development-plan', [SuccessionPlanningController::class, 'updateDevelopmentPlan']);
    });
});
