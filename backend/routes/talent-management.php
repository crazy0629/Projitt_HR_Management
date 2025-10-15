<?php

use App\Http\Controllers\Talent\PipController;
use App\Http\Controllers\Talent\PromotionController;
use App\Http\Controllers\Talent\SuccessionPlanningController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Talent Management API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes for the Talent Management module including:
| - Promotions workflow
| - Succession planning
| - Performance Improvement Plans (PIPs)
| - Employee notes and retention tracking
|
*/

// Promotion Management Routes
Route::prefix('promotions')->group(function () {
    Route::get('/', [PromotionController::class, 'index']);
    Route::post('/', [PromotionController::class, 'store']);
    Route::get('/workflows', [PromotionController::class, 'workflows']);
    Route::get('/pending-approvals', [PromotionController::class, 'pendingApprovals']);
    Route::get('/stats', [PromotionController::class, 'stats']);

    Route::prefix('{id}')->group(function () {
        Route::get('/', [PromotionController::class, 'show']);
        Route::put('/', [PromotionController::class, 'update']);
        Route::post('/submit', [PromotionController::class, 'submit']);
        Route::post('/withdraw', [PromotionController::class, 'withdraw']);
        Route::get('/timeline', [PromotionController::class, 'timeline']);
    });

    Route::post('/approvals/{approvalId}', [PromotionController::class, 'processApproval']);
});

// Succession Planning Routes
Route::prefix('succession')->group(function () {
    Route::get('/', [SuccessionPlanningController::class, 'index']);
    Route::get('/metrics', [SuccessionPlanningController::class, 'metrics']);
    Route::get('/critical-gaps', [SuccessionPlanningController::class, 'criticalGaps']);
    Route::get('/readiness-benchmark', [SuccessionPlanningController::class, 'readinessBenchmark']);

    // Succession Roles
    Route::prefix('roles')->group(function () {
        Route::post('/', [SuccessionPlanningController::class, 'createRole']);
        Route::get('/{id}', [SuccessionPlanningController::class, 'showRole']);
    });

    // Succession Candidates
    Route::prefix('candidates')->group(function () {
        Route::post('/', [SuccessionPlanningController::class, 'addCandidate']);
        Route::get('/{id}', [SuccessionPlanningController::class, 'showCandidate']);
        Route::put('/{id}/readiness', [SuccessionPlanningController::class, 'updateCandidateReadiness']);
        Route::post('/{id}/learning-path', [SuccessionPlanningController::class, 'assignLearningPath']);
        Route::post('/{id}/promote', [SuccessionPlanningController::class, 'promoteCandidate']);
    });

    // Employee-specific succession opportunities
    Route::get('/employees/{employeeId}/opportunities', [SuccessionPlanningController::class, 'employeeOpportunities']);
});

// Performance Improvement Plans (PIPs) Routes
Route::prefix('pips')->group(function () {
    Route::get('/', [PipController::class, 'index']);
    Route::post('/', [PipController::class, 'store']);
    Route::get('/due-for-checkin', [PipController::class, 'dueForCheckin']);
    Route::get('/overdue', [PipController::class, 'overdue']);
    Route::get('/metrics', [PipController::class, 'metrics']);

    Route::prefix('{id}')->group(function () {
        Route::get('/', [PipController::class, 'show']);
        Route::put('/status', [PipController::class, 'updateStatus']);
        Route::post('/checkins', [PipController::class, 'addCheckin']);
        Route::get('/report', [PipController::class, 'report']);
    });

    // Employee-specific PIP history
    Route::get('/employees/{employeeId}/history', [PipController::class, 'employeeHistory']);
});

// Employee Notes Routes (simple CRUD for now)
Route::prefix('notes')->group(function () {
    Route::get('/', function (Illuminate\Http\Request $request) {
        $query = \App\Models\Talent\Note::with(['employee', 'author']);

        if ($request->filled('employee_id')) {
            $query->forEmployee($request->employee_id);
        }

        if ($request->filled('visibility')) {
            $query->byVisibility($request->visibility);
        }

        if ($request->filled('sensitive')) {
            $query->where('is_sensitive', $request->boolean('sensitive'));
        }

        $notes = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $notes,
        ]);
    });

    Route::post('/', function (Illuminate\Http\Request $request) {
        $validator = Illuminate\Support\Facades\Validator::make($request->all(), [
            'employee_id' => 'required|exists:users,id',
            'body' => 'required|string|min:5',
            'visibility' => 'required|in:hr_only,manager_chain,employee_visible',
            'is_sensitive' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $note = \App\Models\Talent\Note::create([
            'employee_id' => $request->employee_id,
            'author_id' => auth()->id(),
            'body' => $request->body,
            'visibility' => $request->visibility,
            'is_sensitive' => $request->boolean('is_sensitive', false),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Note created successfully',
            'data' => $note->load(['employee', 'author']),
        ], 201);
    });

    Route::get('/{id}', function ($id) {
        $note = \App\Models\Talent\Note::with(['employee', 'author'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $note,
        ]);
    });

    Route::delete('/{id}', function ($id) {
        $note = \App\Models\Talent\Note::findOrFail($id);
        $note->delete();

        return response()->json([
            'success' => true,
            'message' => 'Note deleted successfully',
        ]);
    });
});

// Retention Risk Tracking Routes
Route::prefix('retention-risk')->group(function () {
    Route::get('/', function (Illuminate\Http\Request $request) {
        $query = \App\Models\Talent\RetentionRiskSnapshot::with('employee');

        if ($request->filled('employee_id')) {
            $query->forEmployee($request->employee_id);
        }

        if ($request->filled('period')) {
            $query->forPeriod($request->period);
        }

        if ($request->filled('risk')) {
            $query->where('risk', $request->risk);
        }

        $snapshots = $query->orderBy('period', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $snapshots,
        ]);
    });

    Route::post('/', function (Illuminate\Http\Request $request) {
        $validator = Illuminate\Support\Facades\Validator::make($request->all(), [
            'employee_id' => 'required|exists:users,id',
            'risk' => 'required|in:low,medium,high',
            'factors' => 'nullable|array',
            'score' => 'nullable|numeric|min:0|max:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $snapshot = \App\Models\Talent\RetentionRiskSnapshot::createForEmployee(
            $request->employee_id,
            $request->risk,
            $request->factors ?? [],
            $request->score
        );

        return response()->json([
            'success' => true,
            'message' => 'Retention risk snapshot created successfully',
            'data' => $snapshot->load('employee'),
        ], 201);
    });

    Route::get('/employees/{employeeId}/current', function ($employeeId) {
        $snapshot = \App\Models\Talent\RetentionRiskSnapshot::getCurrentRisk($employeeId);

        return response()->json([
            'success' => true,
            'data' => $snapshot,
        ]);
    });

    Route::get('/high-risk', function () {
        $highRisk = \App\Models\Talent\RetentionRiskSnapshot::with('employee')
            ->highRisk()
            ->where('period', now()->format('Y-m'))
            ->get();

        return response()->json([
            'success' => true,
            'data' => $highRisk,
        ]);
    });
});

// Audit Logs Routes (read-only)
Route::prefix('audit-logs')->group(function () {
    Route::get('/', function (Illuminate\Http\Request $request) {
        $query = \App\Models\Talent\AuditLog::with('actor');

        if ($request->filled('entity_type') && $request->filled('entity_id')) {
            $query->forEntity($request->entity_type, $request->entity_id);
        }

        if ($request->filled('actor_id')) {
            $query->byActor($request->actor_id);
        }

        if ($request->filled('action')) {
            $query->byAction($request->action);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    });

    Route::get('/{entityType}/{entityId}', function ($entityType, $entityId) {
        $logs = \App\Models\Talent\AuditLog::with('actor')
            ->forEntity($entityType, $entityId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    });
});
