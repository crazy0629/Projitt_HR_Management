<?php

namespace App\Services\Psychometric;

use App\Models\Psychometric\PsychometricAssignment;
use App\Models\Psychometric\PsychometricAuditLog;
use App\Models\Psychometric\PsychometricResult;
use App\Models\Psychometric\PsychometricTest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PsychometricAssignmentService
{
    public function __construct(protected PsychometricScoringService $scoring)
    {
    }

    /**
     * @return Collection<int, PsychometricAssignment>|LengthAwarePaginator<PsychometricAssignment>
     */
    public function list(array $filters = [], bool $paginate = true, int $perPage = 15)
    {
        $query = PsychometricAssignment::query()
            ->with(['test', 'candidate', 'jobApplicant', 'results', 'responses.question'])
            ->orderByDesc('created_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['candidate_id'])) {
            $query->where('candidate_id', $filters['candidate_id']);
        }

        if (! empty($filters['psychometric_test_id'])) {
            $query->where('psychometric_test_id', $filters['psychometric_test_id']);
        }

        if (! empty($filters['job_applicant_id'])) {
            $query->where('job_applicant_id', $filters['job_applicant_id']);
        }

        if (! empty($filters['target_role'])) {
            $query->where('target_role', $filters['target_role']);
        }

        return $paginate ? $query->paginate($perPage) : $query->get();
    }

    /**
     * @param  array<int>  $candidateIds
     * @return Collection<int, PsychometricAssignment>
     */
    public function assign(PsychometricTest $test, array $candidateIds, array $payload): Collection
    {
        $assignerId = Auth::id();
        $timeLimit = $payload['time_limit_minutes'] ?? $test->time_limit_minutes;
        $metadata = is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [];
        $jobApplicantId = Arr::get($payload, 'job_applicant_id');
        $targetRole = Arr::get($payload, 'target_role');
        $expiresAt = Arr::get($payload, 'expires_at') ? Carbon::parse($payload['expires_at']) : null;
        $invitationMessage = Arr::get($payload, 'invitation_message');

        $assignments = new Collection();

        DB::transaction(function () use (
            $candidateIds,
            $assignerId,
            $test,
            $timeLimit,
            $metadata,
            $jobApplicantId,
            $targetRole,
            $expiresAt,
            $invitationMessage,
            &$assignments
        ) {
            foreach ($candidateIds as $candidateId) {
                $this->ensureAttemptsAvailable($test, $candidateId);

                $assignment = PsychometricAssignment::create([
                    'psychometric_test_id' => $test->id,
                    'candidate_id' => $candidateId,
                    'job_applicant_id' => $jobApplicantId,
                    'status' => 'pending',
                    'assigned_by' => $assignerId,
                    'assigned_at' => now(),
                    'expires_at' => $expiresAt,
                    'time_limit_minutes' => $timeLimit,
                    'attempts_used' => 0,
                    'randomization_seed' => Str::uuid()->toString(),
                    'metadata' => $metadata,
                    'target_role' => $targetRole,
                    'question_order' => null,
                    'result_snapshot' => null,
                ]);

                if ($invitationMessage) {
                    $meta = $assignment->metadata ?? [];
                    $meta['invitation_message'] = $invitationMessage;
                    $assignment->metadata = $meta;
                    $assignment->save();
                }

                $assignments->push($assignment->fresh(['test', 'candidate']));

                $this->logAction($assignment, 'assignment_created', [
                    'assigner_id' => $assignerId,
                ]);
            }
        });

        return $assignments;
    }

    public function start(PsychometricAssignment $assignment): PsychometricAssignment
    {
        $assignment->loadMissing('test.questions.options');
        $test = $assignment->test;

        if (! $test) {
            throw new \RuntimeException('Psychometric assignment missing test configuration.');
        }

        if (! in_array($assignment->status, ['pending', 'in_progress'], true)) {
            throw new \RuntimeException('Assignment cannot be started in its current status.');
        }

        if ($test->allowed_attempts !== null && $test->allowed_attempts > 0 && $assignment->attempts_used >= $test->allowed_attempts) {
            throw new \RuntimeException('Maximum attempts exhausted for this candidate.');
        }

        $assignment->status = 'in_progress';
        if (! $assignment->started_at) {
            $assignment->started_at = now();
            $assignment->attempts_used = $assignment->attempts_used + 1;
        }

        if (! $assignment->time_limit_minutes && $test->time_limit_minutes) {
            $assignment->time_limit_minutes = $test->time_limit_minutes;
        }

        if (! $assignment->expires_at && $assignment->time_limit_minutes) {
            $assignment->expires_at = $assignment->started_at->clone()->addMinutes($assignment->time_limit_minutes);
        }

        $questionOrder = $this->buildQuestionOrder($test);
        $metadata = $assignment->metadata ?? [];
        $metadata['option_order'] = $questionOrder['option_order'];

        $assignment->question_order = $questionOrder['questions'];
        $assignment->metadata = $metadata;
        $assignment->save();

        $this->logAction($assignment, 'assignment_started', [
            'question_order' => $assignment->question_order,
        ]);

        return $assignment->fresh(['test.questions', 'candidate']);
    }

    public function submit(PsychometricAssignment $assignment, array $payload): PsychometricAssignment
    {
        $forceSubmit = (bool) ($payload['force_submit'] ?? false);

        if ($assignment->status === 'expired' && ! $forceSubmit) {
            throw new \RuntimeException('Assignment has expired and cannot be submitted.');
        }

        if (in_array($assignment->status, ['completed', 'scored'], true) && ! $forceSubmit) {
            throw new \RuntimeException('Assignment already completed.');
        }

        if (! $assignment->started_at) {
            throw new \RuntimeException('Assignment must be started before submission.');
        }

        if ($assignment->time_limit_minutes && now()->greaterThan($assignment->started_at->clone()->addMinutes($assignment->time_limit_minutes)) && ! $forceSubmit) {
            $assignment->status = 'expired';
            $assignment->completed_at = now();
            $assignment->duration_seconds = $assignment->started_at->diffInSeconds($assignment->completed_at);
            $assignment->save();

            $this->logAction($assignment, 'assignment_expired', []);

            throw new \RuntimeException('Assignment exceeded the time limit.');
        }

        $evaluation = $this->scoring->evaluate($assignment, $payload['responses'] ?? []);

        $assignment->status = 'scored';
        $assignment->completed_at = now();
        $assignment->duration_seconds = $assignment->started_at->diffInSeconds($assignment->completed_at);

        $meta = is_array($assignment->metadata) ? $assignment->metadata : [];
        if (! empty($payload['metadata']) && is_array($payload['metadata'])) {
            $meta = array_merge($meta, $payload['metadata']);
        }
        if ($forceSubmit) {
            $meta['force_submit'] = true;
        }

        $assignment->metadata = $meta;
        $assignment->result_snapshot = $evaluation['summary'];
        $assignment->save();

        $this->logAction($assignment, 'assignment_submitted', [
            'summary' => $evaluation['summary'],
        ]);

        return $assignment->fresh(['results', 'responses.question', 'responses.option']);
    }

    public function reportSummary(array $filters = []): array
    {
        $resultsQuery = PsychometricResult::query()->with('test:id,title,category');

        if (! empty($filters['from'])) {
            $resultsQuery->where('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $resultsQuery->where('created_at', '<=', $filters['to']);
        }

        $results = $resultsQuery->get();

        $byTest = $results->groupBy('psychometric_test_id')->map(function ($group) {
            $first = $group->first();

            return [
                'test_id' => $first->psychometric_test_id,
                'test_title' => optional($first->test)->title,
                'category' => optional($first->test)->category,
                'average_weighted_score' => round((float) $group->avg('weighted_score'), 2),
                'assignments' => (int) $group->pluck('assignment_id')->unique()->count(),
                'candidates' => (int) $group->pluck('candidate_id')->unique()->count(),
            ];
        })->values();

        $assignmentsQuery = PsychometricAssignment::query()->with('candidate:id,first_name,last_name,email');

        if (! empty($filters['from'])) {
            $assignmentsQuery->where('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $assignmentsQuery->where('created_at', '<=', $filters['to']);
        }

        $assignments = $assignmentsQuery->get();

        $byRole = $assignments
            ->filter(fn (PsychometricAssignment $assignment) => ! empty($assignment->target_role))
            ->groupBy('target_role')
            ->map(function ($group, $role) {
                $average = $group->avg(fn (PsychometricAssignment $assignment) => (float) ($assignment->result_snapshot['total_weighted_score'] ?? 0));

                return [
                    'target_role' => $role,
                    'average_weighted_score' => round((float) $average, 2),
                    'assignments' => $group->count(),
                ];
            })->values();

        $byCandidate = $assignments
            ->groupBy('candidate_id')
            ->map(function ($group, $candidateId) {
                /** @var PsychometricAssignment $first */
                $first = $group->first();
                $averagePercentile = $group->avg(fn (PsychometricAssignment $assignment) => (float) ($assignment->result_snapshot['percentile'] ?? 0));

                return [
                    'candidate_id' => $candidateId,
                    'candidate_name' => trim(($first->candidate->first_name ?? '').' '.($first->candidate->last_name ?? '')),
                    'candidate_email' => $first->candidate->email ?? null,
                    'assignments' => $group->count(),
                    'average_percentile' => round((float) $averagePercentile, 2),
                ];
            })->values();

        return [
            'by_test' => $byTest,
            'by_role' => $byRole,
            'by_candidate' => $byCandidate,
        ];
    }

    protected function ensureAttemptsAvailable(PsychometricTest $test, int $candidateId): void
    {
        if (! $test->allowed_attempts || $test->allowed_attempts <= 0) {
            return;
        }

        $existingAttempts = PsychometricAssignment::query()
            ->where('psychometric_test_id', $test->id)
            ->where('candidate_id', $candidateId)
            ->whereNotIn('status', ['cancelled'])
            ->count();

        if ($existingAttempts >= $test->allowed_attempts) {
            throw new \RuntimeException('Candidate has reached the attempt limit for this test.');
        }
    }

    protected function buildQuestionOrder(PsychometricTest $test): array
    {
        $questions = $test->questions()->orderBy('base_order')->orderBy('id')->get();
        $optionOrder = [];

        if ($test->randomize_questions) {
            $questions = $questions->shuffle();
        }

        $questionIds = $questions->pluck('id')->values()->toArray();

        foreach ($questions as $question) {
            $options = $question->options()->orderBy('position')->orderBy('id')->get();
            if ($question->randomize_options) {
                $options = $options->shuffle();
            }
            $optionOrder[$question->id] = $options->pluck('id')->values()->toArray();
        }

        return [
            'questions' => $questionIds,
            'option_order' => $optionOrder,
        ];
    }

    protected function logAction(PsychometricAssignment $assignment, string $action, array $context = []): void
    {
        try {
            PsychometricAuditLog::create([
                'psychometric_test_id' => $assignment->psychometric_test_id,
                'assignment_id' => $assignment->id,
                'candidate_id' => $assignment->candidate_id,
                'actor_id' => Auth::id(),
                'action' => $action,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'context' => $context,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Failed to record psychometric audit log', [
                'assignment_id' => $assignment->id,
                'action' => $action,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
