<?php

namespace App\Http\Controllers\Talent;

use App\Http\Controllers\Controller;
use App\Models\Talent\Pip;
use App\Services\Talent\PipManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PipController extends Controller
{
    protected $pipService;

    public function __construct(PipManagementService $pipService)
    {
        $this->pipService = $pipService;
    }

    /**
     * Get all PIPs with filtering
     */
    public function index(Request $request)
    {
        $query = Pip::with(['employee', 'mentor', 'learningPath']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('employee_id')) {
            $query->byEmployee($request->employee_id);
        }

        if ($request->filled('mentor_id')) {
            $query->byMentor($request->mentor_id);
        }

        if ($request->filled('ending_soon')) {
            $query->endingSoon($request->ending_soon);
        }

        if ($request->filled('overdue')) {
            $query->overdue();
        }

        $pips = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $pips,
        ]);
    }

    /**
     * Create a new PIP
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:users,id',
            'manager_id' => 'nullable|exists:users,id',
            'title' => 'required|string|min:5',
            'description' => 'nullable|string',
            'goals' => 'nullable|array',
            'goals.*.title' => 'required_with:goals|string',
            'goals.*.description' => 'nullable|string',
            'goals.*.target_metric' => 'nullable|string',
            'goals.*.due_date' => 'nullable|date',
            'success_criteria' => 'nullable|string',
            'learning_path_id' => 'nullable|exists:learning_paths,id',
            'mentor_id' => 'nullable|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'checkin_frequency' => 'nullable|in:weekly,biweekly,monthly',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $pip = $this->pipService->createPip(
                $request->employee_id,
                $request->only([
                    'manager_id',
                    'title',
                    'description',
                    'goals',
                    'success_criteria',
                    'learning_path_id',
                    'mentor_id',
                    'start_date',
                    'end_date',
                    'checkin_frequency'
                ])
            );

            return response()->json([
                'success' => true,
                'message' => 'PIP created successfully',
                'data' => $pip->load(['employee', 'mentor', 'learningPath']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }


    /**
     * Get specific PIP details
     */
    public function show($id)
    {
        $pip = Pip::with([
            'employee',
            'mentor',
            'learningPath',
            'checkins.creator',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $pip,
        ]);
    }

    /**
     * Update PIP status
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,paused,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $pip = $this->pipService->updatePipStatus($id, $request->status, $request->notes);

            return response()->json([
                'success' => true,
                'message' => 'PIP status updated successfully',
                'data' => $pip,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Add a check-in to PIP
     */
    public function addCheckin(Request $request, $pipId)
    {
        $validator = Validator::make($request->all(), [
            'checkin_date' => 'nullable|date',
            'summary' => 'required|string|min:10',
            'status' => 'nullable|in:on_track,off_track,improving,completed',
            'rating' => 'nullable|integer|min:1|max:5',
            'goals_progress' => 'nullable|array',
            'goals_progress.*.goal_id' => 'nullable|integer',
            'goals_progress.*.status' => 'nullable|string',
            'goals_progress.*.notes' => 'nullable|string',
            'manager_notes' => 'nullable|string',
            'next_steps' => 'nullable|string',
            'next_checkin_date' => 'nullable|date|after_or_equal:checkin_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $pip = Pip::findOrFail($pipId);

            $checkin = $pip->addCheckin($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Check-in recorded successfully',
                'data' => $checkin->load('creator'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }


    /**
     * Get PIPs due for check-in
     */
    public function dueForCheckin()
    {
        $pips = $this->pipService->getPipsDueForCheckin();

        return response()->json([
            'success' => true,
            'data' => $pips,
        ]);
    }

    /**
     * Get overdue PIPs
     */
    public function overdue()
    {
        $pips = $this->pipService->getOverduePips();

        return response()->json([
            'success' => true,
            'data' => $pips,
        ]);
    }

    /**
     * Get PIP metrics and statistics
     */
    public function metrics(Request $request)
    {
        $startDate = $request->date_from ? \Carbon\Carbon::parse($request->date_from) : null;
        $endDate = $request->date_to ? \Carbon\Carbon::parse($request->date_to) : null;

        $metrics = $this->pipService->getPipMetrics($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * Get employee PIP history
     */
    public function employeeHistory($employeeId)
    {
        $history = $this->pipService->getEmployeePipHistory($employeeId);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Generate PIP report
     */
    public function report($id)
    {
        $report = $this->pipService->generatePipReport($id);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }
}
