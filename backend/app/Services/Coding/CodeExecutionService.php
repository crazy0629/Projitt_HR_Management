<?php

namespace App\Services\Coding;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Exceptions\Coding\CodeExecutionException;

class CodeExecutionService
{
    protected string $baseUrl;

    protected ?string $apiKey;

    protected int $timeout;

    public function __construct()
    {
        $config = config('services.code_executor', []);
        $this->baseUrl = rtrim((string) ($config['base_url'] ?? ''), '/');
        $this->apiKey = $config['api_key'] ?? env('CODE_EXECUTOR_API_KEY');
        $this->timeout = (int) ($config['timeout'] ?? 20);

    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(string $language, string $sourceCode, array $testCases): array
    {
        if ($this->shouldSimulate()) {
            return $this->simulateExecution($language, $sourceCode, $testCases);
        }

        $this->ensureConfigured();

        $payload = [
            'language' => $language,
            'source' => $sourceCode,
            'tests' => collect($testCases)->map(function ($test) {
                return [
                    'id' => $test['id'] ?? (string) Str::uuid(),
                    'name' => $test['name'] ?? null,
                    'input' => Arr::get($test, 'input', ''),
                    'expected_output' => Arr::get($test, 'expected_output', ''),
                    'timeout' => Arr::get($test, 'timeout_seconds', 5),
                ];
            })->values()->all(),
        ];

        try {
            $response = Http::timeout($this->timeout)
                ->withHeader('Authorization', 'Bearer '.$this->apiKey)
                ->acceptJson()
                ->post($this->baseUrl.'/v1/execute', $payload);
        } catch (\Exception $exception) {
            throw new CodeExecutionException('Failed to reach code execution service.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new CodeExecutionException('Code execution service returned an error.', $response->status(), $response->json());
        }

        $body = $response->json();

        if (! is_array($body) || ! isset($body['results'])) {
            throw new CodeExecutionException('Invalid response from code execution service.', $response->status(), $body);
        }

        return $body['results'];
    }

    protected function ensureConfigured(): void
    {
        if (empty($this->baseUrl)) {
            throw new CodeExecutionException('Code executor base URL is not configured.');
        }

        if (empty($this->apiKey)) {
            throw new CodeExecutionException('Code executor API key is not configured.');
        }
    }

    protected function shouldSimulate(): bool
    {
        return empty($this->baseUrl) || empty($this->apiKey) || config('services.code_executor.simulate', false);
    }

    protected function simulateExecution(string $language, string $sourceCode, array $testCases): array
    {
        logger()->warning('Simulating code execution due to missing executor configuration.', [
            'language' => $language,
        ]);

        return collect($testCases)->map(function ($test, int $index) use ($language) {
            $id = (string) Arr::get($test, 'id', (string) Str::uuid());
            $name = Arr::get($test, 'name', 'Test Case '.($index + 1));

            return [
                'id' => $id,
                'name' => $name,
                'status' => 'passed',
                'stdout' => sprintf('Simulated execution for %s (%s)', $name, $language),
                'stderr' => null,
                'execution_time_ms' => 0,
                'memory_kb' => null,
                'weight' => Arr::get($test, 'weight', 1),
                'simulated' => true,
            ];
        })->all();
    }
}
