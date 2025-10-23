<?php

namespace App\Http\Controllers\Coding;

use App\Exceptions\Coding\CodeExecutionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Coding\ReviewCodingSubmissionRequest;
use App\Http\Requests\Coding\SubmitCodingSolutionRequest;
use App\Models\Coding\CodingAssessmentAssignment;
use App\Models\Coding\CodingSubmission;
use App\Models\Coding\CodingSubmissionReview;
use App\Services\Coding\CodingAssessmentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CodingSubmissionController extends Controller
{
    public function __construct(protected CodingAssessmentService $assessmentService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = CodingSubmission::with(['assessment', 'candidate', 'assignment.candidate', 'assignment.talentable'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('candidate_id'), fn ($q) => $q->where('candidate_id', $request->input('candidate_id')))
            ->when($request->filled('coding_assessment_id'), fn ($q) => $q->where('coding_assessment_id', $request->input('coding_assessment_id')))
            ->orderByDesc('created_at');

        $submissions = getData(
            $query,
            $request->boolean('pagination', true),
            $request->integer('per_page', 15),
            $request->integer('page', 1)
        );

        return successResponse(config('messages.success'), $submissions, 200);
    }

    public function show(int $submissionId): JsonResponse
    {
        $submission = CodingSubmission::with([
            'assessment.testCases',
            'candidate',
            'assignment.candidate',
            'assignment.talentable',
            'testResults',
            'reviews.reviewer',
        ])
            ->findOrFail($submissionId);

        return successResponse(config('messages.success'), $submission, 200);
    }

    public function store(SubmitCodingSolutionRequest $request, int $assignmentId): JsonResponse
    {
        try {
            $assignment = CodingAssessmentAssignment::with(['assessment.testCases'])
                ->findOrFail($assignmentId);
        } catch (ModelNotFoundException) {
            return response()->json([
                'status' => false,
                'message' => 'Coding assignment not found.',
                'data' => null,
                'error' => [
                    'code' => 'assignment_not_found',
                ],
            ], 404);
        }

        if ($assignment->expires_at && now()->greaterThan($assignment->expires_at)) {
            return response()->json([
                'status' => false,
                'message' => 'Assignment has expired.',
                'data' => null,
                'error' => ['code' => 'assignment_expired'],
            ], 422);
        }

        $authId = Auth::id();

        if ($assignment->candidate_id && $authId && $assignment->candidate_id !== $authId) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to submit for this assignment.',
                'data' => null,
                'error' => ['code' => 'forbidden'],
            ], 403);
        }
        $data = $request->validated();

        if ($assignment->status === 'pending') {
            $assignment->status = 'in_progress';
            $assignment->save();
        }

        $totalCases = $assignment->assessment?->testCases?->count() ?? 0;

        $submission = DB::transaction(function () use ($assignment, $authId, $data, $totalCases) {
            return CodingSubmission::create([
                'assignment_id' => $assignment->id,
                'coding_assessment_id' => $assignment->coding_assessment_id,
                'candidate_id' => $authId ?? $assignment->candidate_id,
                'language' => $data['language'],
                'source_code' => $data['source_code'],
                'status' => 'pending',
                'total_count' => $totalCases,
                'metadata' => $data['metadata'] ?? null,
            ]);
        });

        try {
            $submission = $this->assessmentService->evaluateSubmission($submission);
        } catch (CodeExecutionException $exception) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage(),
                'data' => null,
                'error' => $exception->context(),
            ], 422);
        }

        return successResponse(config('messages.success'), $submission, 201);
    }

    public function review(ReviewCodingSubmissionRequest $request, int $submissionId): JsonResponse
    {
        $submission = CodingSubmission::with('reviews')->findOrFail($submissionId);
        $data = $request->validated();

        $review = CodingSubmissionReview::create([
            'submission_id' => $submission->id,
            'reviewer_id' => Auth::id(),
            'score_adjustment' => $data['score_adjustment'] ?? 0,
            'comment' => $data['comment'] ?? null,
            'rubric_scores' => $data['rubric_scores'] ?? null,
        ]);

        if (isset($data['score_adjustment'])) {
            $submission->score = (float) $submission->score + (float) $data['score_adjustment'];
            $submission->save();
        }

        $submission->load(['reviews.reviewer']);

        return successResponse(config('messages.success'), [
            'submission' => $submission,
            'review' => $review,
        ], 200);
    }
}
