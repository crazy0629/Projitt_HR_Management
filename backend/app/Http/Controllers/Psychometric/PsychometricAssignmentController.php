<?php

namespace App\Http\Controllers\Psychometric;

use App\Http\Controllers\Controller;
use App\Http\Requests\Psychometric\AssignPsychometricTestRequest;
use App\Http\Requests\Psychometric\SubmitPsychometricAssignmentRequest;
use App\Models\Psychometric\PsychometricAssignment;
use App\Models\Psychometric\PsychometricTest;
use App\Services\Psychometric\PsychometricAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PsychometricAssignmentController extends Controller
{
    public function __construct(protected PsychometricAssignmentService $assignments)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $data = $this->assignments->list(
            $request->only(['status', 'candidate_id', 'psychometric_test_id', 'job_applicant_id', 'target_role']),
            $request->boolean('pagination', true),
            $request->integer('per_page', 15)
        );

        return successResponse(config('messages.success'), $data, 200);
    }

    public function assign(AssignPsychometricTestRequest $request, $psychometricTest): JsonResponse
    {
        $psychometricTestModel = PsychometricTest::findOrFail($psychometricTest);
        $assignments = $this->assignments->assign($psychometricTestModel, $request->input('candidate_ids'), $request->validated());

        return successResponse(config('messages.success'), $assignments, 201);
    }

    public function show($psychometricAssignment): JsonResponse
    {
        $psychometricAssignmentModel = PsychometricAssignment::findOrFail($psychometricAssignment);
        $psychometricAssignmentModel->load(['test.dimensions', 'test.questions.options', 'candidate', 'jobApplicant', 'results', 'responses.question']);

        return successResponse(config('messages.success'), $psychometricAssignmentModel, 200);
    }

    public function start($psychometricAssignment): JsonResponse
    {
        $psychometricAssignmentModel = PsychometricAssignment::findOrFail($psychometricAssignment);
        $updated = $this->assignments->start($psychometricAssignmentModel);

        return successResponse(config('messages.success'), $updated, 200);
    }

    public function submit(SubmitPsychometricAssignmentRequest $request, $psychometricAssignment): JsonResponse
    {
        $psychometricAssignmentModel = PsychometricAssignment::findOrFail($psychometricAssignment);
        $updated = $this->assignments->submit($psychometricAssignmentModel, $request->validated());

        return successResponse(config('messages.success'), $updated, 200);
    }
}
