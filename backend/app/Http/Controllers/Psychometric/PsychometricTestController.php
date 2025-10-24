<?php

namespace App\Http\Controllers\Psychometric;

use App\Http\Controllers\Controller;
use App\Http\Requests\Psychometric\StorePsychometricTestRequest;
use App\Http\Requests\Psychometric\UpdatePsychometricTestRequest;
use App\Models\Psychometric\PsychometricTest;
use App\Services\Psychometric\PsychometricTestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PsychometricTestController extends Controller
{
    public function __construct(protected PsychometricTestService $tests)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $data = $this->tests->list(
            $request->only(['category', 'search', 'published']),
            $request->boolean('pagination', true),
            $request->integer('per_page', 15)
        );

        return successResponse(config('messages.success'), $data, 200);
    }

    public function store(StorePsychometricTestRequest $request): JsonResponse
    {
        $test = $this->tests->create($request->validated());

        return successResponse(config('messages.success'), $test, 201);
    }

    public function show($psychometricTestId): JsonResponse
    {
        $psychometricTest = PsychometricTest::findOrFail($psychometricTestId);
        $psychometricTest->load(['dimensions', 'questions.options']);

        return successResponse(config('messages.success'), $psychometricTest, 200);
    }

    public function update(UpdatePsychometricTestRequest $request, $psychometricTestId): JsonResponse
    {
        $psychometricTest = PsychometricTest::findOrFail($psychometricTestId);
        $updated = $this->tests->update($psychometricTest, $request->validated());

        return successResponse(config('messages.success'), $updated, 200);
    }

    public function destroy($psychometricTestId): JsonResponse
    {
        $psychometricTest = PsychometricTest::findOrFail($psychometricTestId);
        $this->tests->delete($psychometricTest);

        return successResponse(config('messages.success'), null, 200);
    }
}
