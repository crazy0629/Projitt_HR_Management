<?php

namespace App\Http\Controllers\Coding;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coding\AssignCodingAssessmentRequest;
use App\Models\Coding\CodingAssessment;
use App\Models\Coding\CodingAssessmentAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CodingAssessmentAssignmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CodingAssessmentAssignment::with(['assessment', 'candidate', 'talentable'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('candidate_id'), fn ($q) => $q->where('candidate_id', $request->input('candidate_id')))
            ->when($request->filled('coding_assessment_id'), fn ($q) => $q->where('coding_assessment_id', $request->input('coding_assessment_id')))
            ->orderByDesc('created_at');

        $assignments = getData(
            $query,
            $request->boolean('pagination', true),
            $request->integer('per_page', 15),
            $request->integer('page', 1)
        );

        return successResponse(config('messages.success'), $assignments, 200);
    }

    public function store(AssignCodingAssessmentRequest $request, int $assessmentId): JsonResponse
    {
        $assessment = CodingAssessment::findOrFail($assessmentId);
        $data = $request->validated();
        $authId = Auth::id();

        $assignments = DB::transaction(function () use ($data, $assessment, $authId) {
            $created = [];

            foreach ($data['candidate_ids'] as $candidateId) {
                $created[] = CodingAssessmentAssignment::create([
                    'coding_assessment_id' => $assessment->id,
                    'candidate_id' => $candidateId,
                    'talentable_type' => $data['talentable_type'] ?? null,
                    'talentable_id' => $data['talentable_id'] ?? null,
                    'status' => 'pending',
                    'assigned_by' => $authId,
                    'assigned_at' => now(),
                    'expires_at' => $data['expires_at'] ?? null,
                    'invitation_message' => $data['invitation_message'] ?? null,
                    'metadata' => $data['metadata'] ?? null,
                ]);
            }

            return CodingAssessmentAssignment::with(['assessment', 'candidate', 'talentable'])
                ->whereIn('id', array_map(fn ($assignment) => $assignment->id, $created))
                ->get();
        });

        return successResponse(config('messages.success'), $assignments, 201);
    }

    public function show(int $assignmentId): JsonResponse
    {
        $assignment = CodingAssessmentAssignment::with([
            'assessment.testCases',
            'candidate',
            'talentable',
            'submissions.candidate',
            'submissions.testResults',
            'submissions.reviews.reviewer',
        ])
            ->findOrFail($assignmentId);

        return successResponse(config('messages.success'), $assignment, 200);
    }
}
