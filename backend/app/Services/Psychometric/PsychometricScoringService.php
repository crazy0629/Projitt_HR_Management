<?php

namespace App\Services\Psychometric;

use App\Models\Psychometric\PsychometricAssignment;
use App\Models\Psychometric\PsychometricQuestion;
use App\Models\Psychometric\PsychometricQuestionOption;
use App\Models\Psychometric\PsychometricResponse;
use App\Models\Psychometric\PsychometricResult;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PsychometricScoringService
{
    /**
     * @param  array<int, array<string, mixed>>  $responsesPayload
     * @return array{summary: array<string, mixed>, responses: Collection<int, PsychometricResponse>}
     */
    public function evaluate(PsychometricAssignment $assignment, array $responsesPayload): array
    {
        $assignment->loadMissing(['test.dimensions', 'test.questions.options']);
        $test = $assignment->test;

        if (! $test) {
            throw new \RuntimeException('Psychometric assignment is not linked to a test.');
        }

        $questionMap = $test->questions->keyBy('id');

        $responsesCollection = collect($responsesPayload)->map(function ($response) use ($questionMap) {
            $questionId = (int) Arr::get($response, 'question_id');
            $question = $questionMap->get($questionId);

            if (! $question instanceof PsychometricQuestion) {
                throw new \InvalidArgumentException("Invalid question supplied: {$questionId}");
            }

            $selectedOptionIds = collect(Arr::get($response, 'selected_option_ids', []))
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values();

            return [
                'question' => $question,
                'question_id' => $question->id,
                'option_id' => Arr::has($response, 'option_id') ? (int) Arr::get($response, 'option_id') : null,
                'selected_option_ids' => $selectedOptionIds,
                'numeric_response' => Arr::has($response, 'numeric_response') ? (float) Arr::get($response, 'numeric_response') : null,
                'text_response' => Arr::get($response, 'text_response'),
                'metadata' => Arr::get($response, 'metadata'),
                'time_spent_seconds' => Arr::has($response, 'time_spent_seconds') ? (int) Arr::get($response, 'time_spent_seconds') : null,
            ];
        })->keyBy('question_id');

        // Ensure required questions are answered
        $missingRequired = $test->questions
            ->filter(fn (PsychometricQuestion $question) => $question->is_required)
            ->reject(fn (PsychometricQuestion $question) => $responsesCollection->has($question->id));

        if ($missingRequired->isNotEmpty()) {
            $missingCodes = $missingRequired->map(fn ($question) => $question->reference_code ?? $question->id)->implode(', ');
            throw new \RuntimeException("Required questions missing responses: {$missingCodes}");
        }

        $dimensionStats = $test->dimensions->mapWithKeys(function ($dimension) {
            return [$dimension->id => [
                'key' => $dimension->key,
                'name' => $dimension->name,
                'weight' => (float) ($dimension->weight ?? 1),
                'raw_score' => 0.0,
                'weighted_score' => 0.0,
                'max_raw' => 0.0,
                'max_weighted' => 0.0,
            ]];
        })->toArray();

        $overall = [
            'raw_score' => 0.0,
            'weighted_score' => 0.0,
            'max_raw' => 0.0,
            'max_weighted' => 0.0,
        ];

        $responses = DB::transaction(function () use ($assignment, $responsesCollection, &$dimensionStats, &$overall) {
            $storedResponses = new EloquentCollection();

            foreach ($responsesCollection as $payload) {
                /** @var PsychometricQuestion $question */
                $question = $payload['question'];
                $dimensionId = $question->dimension_id;

                $response = PsychometricResponse::updateOrCreate(
                    [
                        'assignment_id' => $assignment->id,
                        'psychometric_test_id' => $assignment->psychometric_test_id,
                        'question_id' => $question->id,
                    ],
                    [
                        'option_id' => $payload['option_id'],
                        'numeric_response' => $payload['numeric_response'],
                        'text_response' => $payload['text_response'],
                        'metadata' => $this->mergeResponseMetadata($payload),
                        'time_spent_seconds' => $payload['time_spent_seconds'],
                        'responded_at' => now(),
                    ]
                );

                $storedResponses->push($response);

                $questionScores = $this->computeQuestionScore($question, $response);

                $overall['raw_score'] += $questionScores['raw'];
                $overall['weighted_score'] += $questionScores['weighted'];
                $overall['max_raw'] += $questionScores['max_raw'];
                $overall['max_weighted'] += $questionScores['max_weighted'];

                if ($dimensionId && isset($dimensionStats[$dimensionId])) {
                    $dimensionStats[$dimensionId]['raw_score'] += $questionScores['raw'];
                    $dimensionStats[$dimensionId]['weighted_score'] += $questionScores['weighted'] * $dimensionStats[$dimensionId]['weight'];
                    $dimensionStats[$dimensionId]['max_raw'] += $questionScores['max_raw'];
                    $dimensionStats[$dimensionId]['max_weighted'] += $questionScores['max_weighted'] * $dimensionStats[$dimensionId]['weight'];
                }
            }

            // Persist dimension-level summaries
            PsychometricResult::where('assignment_id', $assignment->id)->delete();

            foreach ($dimensionStats as $dimensionId => $stats) {
                PsychometricResult::create([
                    'assignment_id' => $assignment->id,
                    'psychometric_test_id' => $assignment->psychometric_test_id,
                    'candidate_id' => $assignment->candidate_id,
                    'dimension_id' => $dimensionId,
                    'dimension_key' => $stats['key'],
                    'raw_score' => round($stats['raw_score'], 2),
                    'weighted_score' => round($stats['weighted_score'], 2),
                    'percentile' => $this->calculatePercentile($stats['weighted_score'], $stats['max_weighted']),
                    'band' => $this->determineBand($stats['weighted_score'], $stats['max_weighted']),
                    'metadata' => [
                        'max_raw' => $stats['max_raw'],
                        'max_weighted' => $stats['max_weighted'],
                    ],
                ]);
            }

            return $storedResponses->load(['question', 'option']);
        });

        $summary = [
            'total_raw_score' => round($overall['raw_score'], 2),
            'total_weighted_score' => round($overall['weighted_score'], 2),
            'max_raw_score' => round(max($overall['max_raw'], 0), 2),
            'max_weighted_score' => round(max($overall['max_weighted'], 0), 2),
            'percentile' => $this->calculatePercentile($overall['weighted_score'], $overall['max_weighted']),
            'band' => $this->determineBand($overall['weighted_score'], $overall['max_weighted']),
            'dimensions' => collect($dimensionStats)->map(function ($stats) {
                return [
                    'key' => $stats['key'],
                    'name' => $stats['name'],
                    'raw_score' => round($stats['raw_score'], 2),
                    'weighted_score' => round($stats['weighted_score'], 2),
                    'max_raw_score' => round($stats['max_raw'], 2),
                    'max_weighted_score' => round($stats['max_weighted'], 2),
                    'percentile' => $this->calculatePercentile($stats['weighted_score'], $stats['max_weighted']),
                    'band' => $this->determineBand($stats['weighted_score'], $stats['max_weighted']),
                ];
            })->values()->toArray(),
        ];

        return [
            'summary' => $summary,
            'responses' => $responses,
        ];
    }

    protected function computeQuestionScore(PsychometricQuestion $question, PsychometricResponse $response): array
    {
        $questionWeight = max(0.0, (float) ($question->weight ?? 1));
        $maxRaw = 0.0;
        $rawScore = 0.0;

        $options = $question->options;
        $optionMap = $options->keyBy('id');

        $metadata = is_array($response->metadata) ? $response->metadata : [];
        $selectedOptionIds = collect($metadata['selected_option_ids'] ?? []);

        switch ($question->question_type) {
            case 'likert':
            case 'multiple_choice':
                $option = $optionMap->get($response->option_id);
                $rawScore = $option instanceof PsychometricQuestionOption
                    ? (float) ($option->score ?? 0) * (float) ($option->weight ?? 1)
                    : 0.0;
                $maxRaw = $this->maxOptionScore($options);
                break;

            case 'multi_select':
                $rawScore = $selectedOptionIds
                    ->map(fn ($id) => $optionMap->get($id))
                    ->filter()
                    ->sum(fn (PsychometricQuestionOption $option) => (float) ($option->score ?? 0) * (float) ($option->weight ?? 1));
                $maxRaw = $this->maxMultiSelectScore($options);
                break;

            case 'numeric':
                $rawScore = (float) ($response->numeric_response ?? 0);
                $maxRaw = $this->determineNumericMax($question);
                break;

            default:
                $rawScore = 0.0;
                $maxRaw = 0.0;
        }

        $maxRaw = max($maxRaw, 0.0);

        return [
            'raw' => $rawScore,
            'weighted' => $rawScore * $questionWeight,
            'max_raw' => $maxRaw,
            'max_weighted' => $maxRaw * $questionWeight,
        ];
    }

    protected function maxOptionScore(Collection $options): float
    {
        return (float) $options->map(fn (PsychometricQuestionOption $option) => (float) ($option->score ?? 0) * (float) ($option->weight ?? 1))->max();
    }

    protected function maxMultiSelectScore(Collection $options): float
    {
        return (float) $options
            ->filter(fn (PsychometricQuestionOption $option) => ($option->score ?? 0) > 0)
            ->sum(fn (PsychometricQuestionOption $option) => (float) ($option->score ?? 0) * (float) ($option->weight ?? 1));
    }

    protected function determineNumericMax(PsychometricQuestion $question): float
    {
        $metadata = is_array($question->metadata) ? $question->metadata : [];
        $max = Arr::get($metadata, 'max_numeric', Arr::get($metadata, 'max', null));

        if ($max !== null) {
            return (float) $max;
        }

        return 100.0; // default
    }

    protected function calculatePercentile(float $score, float $maxScore): ?float
    {
        if ($maxScore <= 0) {
            return null;
        }

        return round(min(100, max(0, ($score / $maxScore) * 100)), 2);
    }

    protected function determineBand(float $score, float $maxScore): ?string
    {
        $percentile = $this->calculatePercentile($score, $maxScore);

        if ($percentile === null) {
            return null;
        }

        return match (true) {
            $percentile >= 80 => 'high',
            $percentile >= 50 => 'medium',
            $percentile >= 20 => 'low',
            default => 'very_low',
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function mergeResponseMetadata(array $payload): array
    {
        $metadata = is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [];
        $selectedOptionIds = $payload['selected_option_ids'] ?? [];

        if (! empty($selectedOptionIds)) {
            $metadata['selected_option_ids'] = $selectedOptionIds;
        }

        return $metadata;
    }
}
