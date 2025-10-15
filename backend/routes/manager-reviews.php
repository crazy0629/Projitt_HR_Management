<?php

use App\Http\Controllers\Api\V1\Manager\ActionsController;
use App\Http\Controllers\Api\V1\Manager\TeamReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Manager Performance Review API Routes
|--------------------------------------------------------------------------
|
| These routes handle manager-specific performance review functionality
| including team review management, promotion recommendations, succession
| planning, and career path assignments.
|
*/

Route::prefix('api/v1/manager')->middleware(['auth:sanctum'])->group(function () {

    // Team Review Management
    Route::prefix('team-reviews')->group(function () {
        // Get cycles where manager has reviewees
        Route::get('cycles', [TeamReviewController::class, 'getCycles']);

        // Get reviewees for a specific cycle
        Route::get('cycles/{cycle_id}/reviewees', [TeamReviewController::class, 'getCycleReviewees']);

        // Send reminders to reviewers
        Route::post('cycles/{cycle_id}/reminders', [TeamReviewController::class, 'sendReminders']);

        // Get team summary for a cycle
        Route::get('team-summary', [TeamReviewController::class, 'getTeamSummary']);

        // Get team members with performance data
        Route::get('team-members', [TeamReviewController::class, 'getTeamMembers']);
    });

    // Promotion Recommendations
    Route::prefix('promotions')->group(function () {
        // Create promotion recommendation
        Route::post('/', [ActionsController::class, 'createPromotion']);

        // Get manager's promotion recommendations
        Route::get('/', [ActionsController::class, 'getPromotions']);

        // Withdraw promotion recommendation
        Route::patch('{promotion_id}/withdraw', [ActionsController::class, 'withdrawPromotion']);
    });

    // Succession Planning
    Route::prefix('succession')->group(function () {
        // Add employee to succession pool
        Route::post('/', [ActionsController::class, 'createSuccession']);

        // Get succession planning data would be handled by additional controller methods
    });

    // Career Path Assignments
    Route::prefix('career-paths')->group(function () {
        // Assign career path to employee
        Route::post('assign', [ActionsController::class, 'assignCareerPath']);

        // Career path management would be handled by additional controller methods
    });

    // Reference Data
    Route::prefix('reference')->group(function () {
        // Get available roles for promotion/succession
        Route::get('roles', [ActionsController::class, 'getAvailableRoles']);

        // Get available learning paths
        Route::get('learning-paths', [ActionsController::class, 'getLearningPaths']);
    });
});

/*
|--------------------------------------------------------------------------
| Additional Manager API Endpoints (Future Implementation)
|--------------------------------------------------------------------------
|
| These endpoints would be implemented with additional controllers for
| comprehensive manager review functionality:
|
| Team Management:
| - GET /api/v1/manager/team-structure - Get organizational hierarchy
| - GET /api/v1/manager/direct-reports - Get direct reports with performance data
| - POST /api/v1/manager/team-members - Add/modify team structure
|
| Reviewee Snapshots:
| - GET /api/v1/manager/reviewees/{id}/snapshot - Get detailed reviewee performance
| - GET /api/v1/manager/reviewees/{id}/history - Get historical performance data
| - GET /api/v1/manager/reviewees/{id}/development - Get development recommendations
|
| AI-Powered Insights:
| - GET /api/v1/manager/ai-suggestions - Get AI-generated management suggestions
| - POST /api/v1/manager/ai-analysis - Request AI analysis of team performance
| - GET /api/v1/manager/badges/calculate - Calculate performance badges for team
|
| Reporting and Analytics:
| - GET /api/v1/manager/reports/team-performance - Team performance analytics
| - GET /api/v1/manager/reports/succession-readiness - Succession planning report
| - GET /api/v1/manager/reports/promotion-pipeline - Promotion pipeline analysis
| - POST /api/v1/manager/reports/export - Export team performance data
|
| Notification Management:
| - GET /api/v1/manager/notifications - Get manager-specific notifications
| - POST /api/v1/manager/notifications/mark-read - Mark notifications as read
| - GET /api/v1/manager/reminders - Get upcoming reminders and deadlines
|
| Succession Pool Management:
| - GET /api/v1/manager/succession/pools - Get succession pools
| - POST /api/v1/manager/succession/pools - Create succession pool
| - PATCH /api/v1/manager/succession/candidates/{id} - Update succession candidate
| - DELETE /api/v1/manager/succession/candidates/{id} - Remove succession candidate
|
| Career Development:
| - GET /api/v1/manager/career-paths - Get all career path assignments
| - PATCH /api/v1/manager/career-paths/{id}/progress - Update career path progress
| - POST /api/v1/manager/career-paths/{id}/milestones - Add career path milestone
| - GET /api/v1/manager/development-plans - Get team development plans
|
| Performance Calibration:
| - GET /api/v1/manager/calibration/sessions - Get calibration sessions
| - POST /api/v1/manager/calibration/ratings - Submit calibrated ratings
| - GET /api/v1/manager/calibration/guidelines - Get calibration guidelines
|
*/
