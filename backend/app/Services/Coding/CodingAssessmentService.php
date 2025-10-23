<?php

namespace App\Services\Coding;

use App\Exceptions\Coding\CodeExecutionException;
use App\Models\Coding\CodingAssessmentAssignment;
use App\Models\Coding\CodingSubmission;
use App\Models\Coding\CodingSubmissionTestResult;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CodingAssessmentService
{
    public function __construct(protected CodeExecutionService $executor)
    {
    }

    public function evaluateSubmission(CodingSubmission $submission): CodingSubmission
    {
        $submission->refresh();
        $assessment = $submission->assessment()->with('testCases')->firstOrFail();

        $testCases = $assessment->testCases->map(function ($test) {
            return [
                'id' => (string) $test->id,
                'name' => $test->name,
                'input' => $test->input,
                'expected_output' => $test->expected_output,
                'weight' => max(1, (int) ($test->weight ?? 1)),
                'timeout_seconds' => $test->timeout_seconds ?? 5,
            ];
        })->values();

        $testCaseMap = $testCases->mapWithKeys(fn ($test) => [$test['id'] => $test]);

        if ($testCases->isEmpty()) {
            throw new CodeExecutionException('Coding assessment does not have any test cases configured.');
        }

        $submission->status = 'running';
        $submission->save();

        try {
            $results = $this->executor->execute($submission->language, $submission->source_code, $testCases->toArray());
        } catch (CodeExecutionException $exception) {
            $submission->status = 'failed';
            $submission->error_type = 'executor_error';
            $submission->error_message = $exception->getMessage();
            $existingMetadata = is_array($submission->metadata) ? $submission->metadata : [];
            $submission->metadata = array_merge($existingMetadata, ['executor_context' => $exception->context()]);
            $submission->save();
            throw $exception;
        }

        $totalWeight = max(1, (int) $testCases->sum(fn ($test) => $test['weight']));
        $earnedWeight = 0;
        $passedCount = 0;
        $failedCount = 0;
        $timeoutCount = 0;
        $aggregatedStdout = [];
        $aggregatedStderr = [];
        $errorTypes = [];
        $errorMessages = [];
        $executionTimes = [];
        $memoryUsageSamples = [];

        DB::transaction(function () use (
            $submission,
            $results,
            &$earnedWeight,
            &$passedCount,
            &$failedCount,
            &$timeoutCount,
            &$aggregatedStdout,
            &$aggregatedStderr,
            &$errorTypes,
            &$errorMessages,
            &$executionTimes,
            &$memoryUsageSamples,
            $testCaseMap
        ) {
            $submission->testResults()->delete();

            foreach ($results as $result) {
                $normalizedStatus = $this->normalizeStatus(Arr::get($result, 'status'));
                $testCaseId = (string) Arr::get($result, 'id');
                $testCase = $testCaseMap->get($testCaseId);
                $weight = (float) ($testCase['weight'] ?? Arr::get($result, 'weight', 1));
                $errorType = Arr::get($result, 'error_type') ?? Arr::get($result, 'errorCode');
                $errorTypes[] = $errorType ? strtolower((string) $errorType) : null;
                $errorMessages[] = Arr::get($result, 'error');

                if ($normalizedStatus === 'passed') {
                    $earnedWeight += $weight;
                    $passedCount++;
                } elseif ($normalizedStatus === 'timeout') {
                    $timeoutCount++;
                } else {
                    $failedCount++;
                }

                $aggregatedStdout[] = Arr::get($result, 'stdout');
                $aggregatedStderr[] = Arr::get($result, 'stderr');
                $executionTimes[] = Arr::get($result, 'execution_time_ms');
                $memoryUsageSamples[] = Arr::get($result, 'memory_kb');

                CodingSubmissionTestResult::create([
                    'submission_id' => $submission->id,
                    'test_case_id' => is_numeric($testCaseId) ? (int) $testCaseId : null,
                    'status' => $normalizedStatus,
                    'error_type' => $errorType,
                    'score_earned' => $normalizedStatus === 'passed' ? $weight : 0,
                    'execution_time_ms' => Arr::get($result, 'execution_time_ms'),
                    'memory_kb' => Arr::get($result, 'memory_kb'),
                    'stdout' => Arr::get($result, 'stdout'),
                    'stderr' => Arr::get($result, 'stderr'),
                    'error_message' => Arr::get($result, 'error'),
                ]);
            }
        });

        $score = round(($earnedWeight / $totalWeight) * (float) $assessment->max_score, 2);
        $submission->score = $score;
        $submission->max_score = (float) $assessment->max_score;
        $submission->passed_count = $passedCount;
        $submission->failed_count = $failedCount;
        $submission->total_count = $passedCount + $failedCount + $timeoutCount;
        $submission->execution_time_ms = array_sum(array_filter(array_map(fn ($time) => (int) $time, $executionTimes)));
        $submission->memory_kb = $this->maxOrNull($memoryUsageSamples);
        $submission->stdout = implode("\n", array_filter($aggregatedStdout));
        $submission->stderr = implode("\n", array_filter($aggregatedStderr));
        $existingMetadata = is_array($submission->metadata) ? $submission->metadata : [];
        $submission->metadata = array_merge($existingMetadata, ['executor_results' => $results]);

        [$submissionStatus, $submissionErrorType, $submissionErrorMessage] = $this->determineSubmissionOutcome(
            $passedCount,
            $failedCount,
            $timeoutCount,
            array_filter($errorTypes),
            array_filter($errorMessages)
        );

        $submission->status = $submissionStatus;
        $submission->error_type = $submissionErrorType;
        $submission->error_message = $submissionErrorMessage;

        $submission->save();

        $assignment = $submission->assignment;
        if ($assignment instanceof CodingAssessmentAssignment) {
            if (in_array($submission->status, ['completed', 'failed', 'timeout'], true)) {
                $assignment->status = 'submitted';
                $assignment->completed_at = $assignment->completed_at ?? now();
            }
            $assignment->save();
        }

        return $submission->fresh([
            'assessment',
            'candidate',
            'assignment.candidate',
            'assignment.talentable',
            'testResults',
            'reviews.reviewer',
        ]);
    }

    protected function normalizeStatus(?string $status): string
    {
        return match (strtolower((string) $status)) {
            'success', 'ok', 'passed', 'pass' => 'passed',
            'timeout', 'time_limit_exceeded' => 'timeout',
            'error', 'runtime_error', 'compile_error', 'syntax_error', 'invalid', 'invalid_input' => 'error',
            default => 'failed',
        };
    }

    protected function determineSubmissionOutcome(
        int $passedCount,
        int $failedCount,
        int $timeoutCount,
        array $errorTypes,
        array $errorMessages
    ): array {
        $normalizedTypes = collect($errorTypes)
            ->map(fn ($type) => strtolower((string) $type))
            ->filter()
            ->values();

        $firstErrorMessage = collect($errorMessages)->first(fn ($message) => ! empty($message));

        if ($timeoutCount > 0 && $passedCount === 0 && $failedCount === 0) {
            return ['timeout', 'timeout', $firstErrorMessage];
        }

        if ($normalizedTypes->contains('syntax_error')) {
            return ['failed', 'syntax_error', $firstErrorMessage];
        }

        if ($normalizedTypes->contains('compile_error')) {
            return ['failed', 'compile_error', $firstErrorMessage];
        }

        if ($normalizedTypes->contains('runtime_error')) {
            return ['failed', 'runtime_error', $firstErrorMessage];
        }

        if ($failedCount > 0 && $passedCount === 0) {
            return ['failed', 'test_failure', $firstErrorMessage];
        }

        if ($timeoutCount > 0) {
            return ['completed', 'partial_timeout', $firstErrorMessage];
        }

        if ($failedCount > 0) {
            return ['completed', 'test_failure', $firstErrorMessage];
        }

        return ['completed', null, null];
    }

    protected function maxOrNull(array $values): ?int
    {
        $filtered = array_filter(array_map(fn ($value) => is_numeric($value) ? (int) $value : null, $values), fn ($value) => $value !== null);

        if (empty($filtered)) {
            return null;
        }

        return max($filtered);
    }
}
