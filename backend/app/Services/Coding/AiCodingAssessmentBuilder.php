<?php

namespace App\Services\Coding;

use App\Exceptions\Coding\AiGenerationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AiCodingAssessmentBuilder
{
    protected string $baseUrl;

    protected string $model;

    protected int $timeout;

    protected ?string $apiKey;

    public function __construct()
    {
        $config = config('services.openai', []);
        $this->baseUrl = rtrim((string) ($config['base_url'] ?? 'https://api.openai.com/v1'), '/');
        $this->model = $config['assessment_model'] ?? $config['model'] ?? 'gpt-4o-mini';
        $this->timeout = (int) ($config['timeout'] ?? 30);
        $this->apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');

        if (empty($this->apiKey)) {
            throw new AiGenerationException('OpenAI API key is not configured.');
        }
    }

    public function generate(array $parameters): array
    {
        $prompt = $this->buildPrompt($parameters);

        try {
            $response = Http::timeout($this->timeout)
                ->withHeader('Authorization', 'Bearer '.$this->apiKey)
                ->acceptJson()
                ->post($this->baseUrl.'/chat/completions', [
                    'model' => $this->model,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are an assistant that designs programming assessments. '
                                .'Always respond with strict JSON that matches the requested structure.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ]);
        } catch (\Exception $exception) {
            throw new AiGenerationException('Failed to reach OpenAI service.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new AiGenerationException('OpenAI service returned an error.', $response->status(), $response->json());
        }

        $payload = $response->json();
        $content = Arr::get($payload, 'choices.0.message.content');

        if (! is_string($content)) {
            throw new AiGenerationException('Malformed response from OpenAI.', $response->status(), $payload);
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw new AiGenerationException('Unable to decode assessment JSON from OpenAI.', $response->status(), ['raw_content' => $content]);
        }

        $testCases = $this->normalizeTestCases(Arr::get($decoded, 'test_cases', []), $decoded);

        if (empty($testCases)) {
            throw new AiGenerationException('OpenAI did not return any test cases.', $response->status(), $decoded);
        }

        return [
            'model' => $this->model,
            'title' => Arr::get($decoded, 'title'),
            'description' => Arr::get($decoded, 'description'),
            'time_limit_minutes' => Arr::get($decoded, 'time_limit_minutes'),
            'max_score' => Arr::get($decoded, 'max_score'),
            'rubric' => Arr::get($decoded, 'rubric'),
            'metadata' => Arr::get($decoded, 'metadata'),
            'test_cases' => $testCases,
        ];
    }

    protected function buildPrompt(array $parameters): string
    {
        $segments = [
            'Create a coding assessment definition as JSON with keys: title, description, time_limit_minutes, '
            .'max_score, rubric (optional array), metadata (optional object), test_cases (array). '
            .'Each test case needs name, input, expected_output, weight, is_hidden, timeout_seconds.',
            'Follow these requirements exactly and keep inputs/output concise but unambiguous.',
        ];

        if (! empty($parameters['title'])) {
            $segments[] = 'Assessment title: '.Str::limit((string) $parameters['title'], 200, '');
        }

        if (! empty($parameters['description'])) {
            $segments[] = 'Context: '.Str::limit((string) $parameters['description'], 800, '');
        }

        if (! empty($parameters['difficulty'])) {
            $segments[] = 'Difficulty level: '.$parameters['difficulty'];
        }

        if (! empty($parameters['languages'])) {
            $segments[] = 'Primary languages to target: '.implode(', ', (array) $parameters['languages']);
        }

        if (! empty($parameters['time_limit_minutes'])) {
            $segments[] = 'Time limit minutes: '.$parameters['time_limit_minutes'];
        }

        if (! empty($parameters['max_score'])) {
            $segments[] = 'Max score: '.$parameters['max_score'];
        }

        if (! empty($parameters['generation_prompt'])) {
            $segments[] = 'User instructions: '.(string) $parameters['generation_prompt'];
        }

        $segments[] = 'Ensure weights sum up proportionally and timeout_seconds is between 1 and 30.';
        $segments[] = 'Return at least 3 test cases, including at least one hidden case.';

        return implode("\n\n", array_filter($segments));
    }

    /**
     * @param  array<int, mixed>  $testCases
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeTestCases(array $testCases, ?array $rawPayload = null): array
    {
        $invalidCases = [];

        $normalized = collect($testCases)
            ->filter(fn ($case) => is_array($case))
            ->values()
            ->map(function (array $case, int $index) use (&$invalidCases) {
                $input = Arr::get($case, 'input');
                $expectedOutput = Arr::get($case, 'expected_output');

                $input = $this->stringifyIoPayload($input);
                $expectedOutput = $this->stringifyIoPayload($expectedOutput);

                if ($input === null || $expectedOutput === null) {
                    $invalidCases[] = Arr::only($case, ['name', 'input', 'expected_output', 'weight', 'is_hidden', 'timeout_seconds']);

                    return null;
                }

                return [
                    'name' => Arr::get($case, 'name') ?: 'Test Case '.($index + 1),
                    'input' => $input,
                    'expected_output' => $expectedOutput,
                    'weight' => max(1, (int) Arr::get($case, 'weight', 1)),
                    'is_hidden' => (bool) Arr::get($case, 'is_hidden', $index > 0),
                    'timeout_seconds' => max(1, min(30, (int) Arr::get($case, 'timeout_seconds', 5))),
                ];
            })
            ->filter()
            ->values()
            ->all();

        if (empty($normalized)) {
            throw new AiGenerationException(
                'OpenAI did not return any usable test cases.',
                0,
                [
                    'invalid_cases' => $invalidCases,
                    'raw_test_cases' => $testCases,
                    'raw_payload' => $rawPayload,
                ]
            );
        }

        if (! collect($normalized)->contains(fn ($case) => $case['is_hidden'])) {
            $normalized[array_key_last($normalized)]['is_hidden'] = true;
        }

        if (count($normalized) < 3) {
            throw new AiGenerationException(
                'OpenAI returned fewer than the required number of test cases.',
                0,
                [
                    'valid_cases' => $normalized,
                    'invalid_cases' => $invalidCases,
                    'raw_test_cases' => $testCases,
                    'raw_payload' => $rawPayload,
                ]
            );
        }

        return $normalized;
    }

    protected function stringifyIoPayload(mixed $value): ?string
    {
        if (is_string($value)) {
            $value = trim($value);

            return $value === '' ? null : $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_array($value) || is_object($value)) {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return $encoded === false ? null : $encoded;
        }

        return null;
    }
}
