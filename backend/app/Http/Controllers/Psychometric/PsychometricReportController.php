<?php

namespace App\Http\Controllers\Psychometric;

use App\Http\Controllers\Controller;
use App\Services\Psychometric\PsychometricAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PsychometricReportController extends Controller
{
    public function __construct(protected PsychometricAssignmentService $assignments)
    {
    }

    public function summary(Request $request): JsonResponse
    {
        $data = $this->assignments->reportSummary($request->only(['from', 'to']));

        return successResponse(config('messages.success'), $data, 200);
    }
}
