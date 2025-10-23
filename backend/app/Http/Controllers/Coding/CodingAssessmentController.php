<?php

namespace App\Http\Controllers\Coding;

use App\Exceptions\Coding\AiGenerationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Coding\StoreCodingAssessmentRequest;
use App\Http\Requests\Coding\UpdateCodingAssessmentRequest;
use App\Models\Coding\CodingAssessment;
use App\Models\Coding\CodingTestCase;
use App\Services\Coding\AiCodingAssessmentBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CodingAssessmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CodingAssessment::query()->withCount('testCases');

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->input('difficulty'));
        }

        if ($request->filled('language')) {
            $query->whereJsonContains('languages', $request->input('language'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $assessments = getData(
            $query->orderByDesc('created_at'),
            $request->boolean('pagination', true),
            $request->integer('per_page', 15),
            $request->integer('page', 1)
        );

        return successResponse(config('messages.success'), $assessments, 200);
    }

    public function store(StoreCodingAssessmentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $generateWithAi = (bool) ($data['generate_with_ai'] ?? false);
        $generationPrompt = $data['generation_prompt'] ?? null;
        unset($data['generate_with_ai'], $data['generation_prompt']);

        if ($generateWithAi) {
            try {
                $builder = app(AiCodingAssessmentBuilder::class);
                $generated = $builder->generate(array_merge($data, [
                    'generation_prompt' => $generationPrompt,
                ]));
            } catch (AiGenerationException $exception) {
                return response()->json([
                    'status' => false,
                    'message' => $exception->getMessage(),
                    'data' => null,
                    'error' => [
                        'code' => 'ai_generation_failed',
                        'context' => $exception->context(),
                    ],
                ], 422);
            }

            if (! empty($generated['test_cases'])) {
                $data['test_cases'] = $generated['test_cases'];
            }

            if (! empty($generated['description']) && empty($data['description'])) {
                $data['description'] = $generated['description'];
            }

            if (! empty($generated['time_limit_minutes'])) {
                $data['time_limit_minutes'] = (int) $generated['time_limit_minutes'];
            }

            if (! empty($generated['max_score'])) {
                $data['max_score'] = (int) $generated['max_score'];
            }

            if (! empty($generated['rubric']) && empty($data['rubric'])) {
                $data['rubric'] = $generated['rubric'];
            }

            $metadata = is_array($data['metadata'] ?? null) ? $data['metadata'] : [];
            $metadata['ai_generation'] = [
                'provider' => 'openai',
                'model' => $generated['model'] ?? config('services.openai.assessment_model'),
                'prompt' => $generationPrompt,
            ];
            if (! empty($generated['metadata']) && is_array($generated['metadata'])) {
                $metadata['ai_generation']['raw_metadata'] = $generated['metadata'];
            }
            $data['metadata'] = $metadata;
        }

        if (empty($data['test_cases']) || ! is_array($data['test_cases'])) {
            return response()->json([
                'status' => false,
                'message' => 'Unable to create assessment without test cases.',
                'data' => null,
                'error' => [
                    'code' => 'missing_test_cases',
                ],
            ], 422);
        }

        $assessment = DB::transaction(function () use ($data) {
            $testCases = $data['test_cases'];
            unset($data['test_cases']);

            $assessment = CodingAssessment::create(array_merge($data, [
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]));

            foreach ($testCases as $case) {
                CodingTestCase::create([
                    'coding_assessment_id' => $assessment->id,
                    'name' => $case['name'] ?? null,
                    'input' => $case['input'],
                    'expected_output' => $case['expected_output'],
                    'weight' => $case['weight'] ?? 1,
                    'is_hidden' => $case['is_hidden'] ?? false,
                    'timeout_seconds' => $case['timeout_seconds'] ?? 5,
                ]);
            }

            return $assessment->load('testCases');
        });

        return successResponse(config('messages.success'), $assessment, 201);
    }

    public function show(int $assessmentId): JsonResponse
    {
        $assessment = CodingAssessment::with('testCases')->findOrFail($assessmentId);

        return successResponse(config('messages.success'), $assessment, 200);
    }

    public function update(UpdateCodingAssessmentRequest $request, int $assessmentId): JsonResponse
    {
        $assessment = CodingAssessment::findOrFail($assessmentId);
        $data = $request->validated();

        $assessment = DB::transaction(function () use ($assessment, $data) {
            $testCases = $data['test_cases'] ?? null;
            unset($data['test_cases']);

            if (! empty($data)) {
                $assessment->fill($data);
                $assessment->updated_by = Auth::id();
                $assessment->save();
            }

            if (is_array($testCases)) {
                foreach ($testCases as $case) {
                    $action = $case['_action'] ?? (isset($case['id']) ? 'update' : 'create');

                    if ($action === 'delete' && isset($case['id'])) {
                        CodingTestCase::where('coding_assessment_id', $assessment->id)
                            ->where('id', $case['id'])
                            ->delete();
                        continue;
                    }

                    if ($action === 'update' && isset($case['id'])) {
                        $test = CodingTestCase::where('coding_assessment_id', $assessment->id)
                            ->where('id', $case['id'])
                            ->first();

                        if ($test) {
                            $test->update([
                                'name' => $case['name'] ?? $test->name,
                                'input' => $case['input'] ?? $test->input,
                                'expected_output' => $case['expected_output'] ?? $test->expected_output,
                                'weight' => $case['weight'] ?? $test->weight,
                                'is_hidden' => $case['is_hidden'] ?? $test->is_hidden,
                                'timeout_seconds' => $case['timeout_seconds'] ?? $test->timeout_seconds,
                            ]);
                        }

                        continue;
                    }

                    if ($action === 'create') {
                        CodingTestCase::create([
                            'coding_assessment_id' => $assessment->id,
                            'name' => $case['name'] ?? null,
                            'input' => $case['input'],
                            'expected_output' => $case['expected_output'],
                            'weight' => $case['weight'] ?? 1,
                            'is_hidden' => $case['is_hidden'] ?? false,
                            'timeout_seconds' => $case['timeout_seconds'] ?? 5,
                        ]);
                    }
                }
            }

            return $assessment->load('testCases');
        });

        return successResponse(config('messages.success'), $assessment, 200);
    }

    public function destroy(int $assessmentId): JsonResponse
    {
        $assessment = CodingAssessment::findOrFail($assessmentId);
        $assessment->deleted_by = Auth::id();
        $assessment->save();
        $assessment->delete();

        return successResponse(config('messages.success'), null, 200);
    }
}
