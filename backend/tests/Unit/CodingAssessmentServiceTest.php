<?php

namespace Tests\Unit;

use App\Models\Coding\CodingAssessment;
use App\Models\Coding\CodingAssessmentAssignment;
use App\Models\Coding\CodingSubmission;
use App\Models\User\User;
use App\Services\Coding\CodeExecutionService;
use App\Services\Coding\CodingAssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CodingAssessmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_evaluate_submission_with_partial_success(): void
    {
        [$submission, $assessment] = $this->createSubmissionFixture(3);

        $mock = Mockery::mock(CodeExecutionService::class);
        $mock->shouldReceive('execute')
            ->once()
            ->with('php', $submission->source_code, Mockery::type('array'))
            ->andReturn([
                [
                    'id' => (string) $assessment->testCases[0]->id,
                    'status' => 'passed',
                    'weight' => 1,
                    'execution_time_ms' => 120,
                    'memory_kb' => 256,
                ],
                [
                    'id' => (string) $assessment->testCases[1]->id,
                    'status' => 'failed',
                    'weight' => 1,
                    'error' => 'Wrong answer',
                ],
                [
                    'id' => (string) $assessment->testCases[2]->id,
                    'status' => 'passed',
                    'weight' => 1,
                    'execution_time_ms' => 150,
                    'memory_kb' => 300,
                ],
            ]);
        $this->app->instance(CodeExecutionService::class, $mock);

        $service = $this->app->make(CodingAssessmentService::class);
        $evaluated = $service->evaluateSubmission($submission);

        $this->assertSame('completed', $evaluated->status);
        $this->assertSame('test_failure', $evaluated->error_type);
        $this->assertSame(2, $evaluated->passed_count);
        $this->assertSame(1, $evaluated->failed_count);
        $this->assertSame(0, $evaluated->testResults()->where('status', 'timeout')->count());
        $this->assertGreaterThan(0, $evaluated->score);
        $this->assertEquals(66.67, $evaluated->score);
        $this->assertEquals('submitted', $evaluated->assignment->status);
    }

    public function test_evaluate_submission_with_syntax_error(): void
    {
        [$submission, $assessment] = $this->createSubmissionFixture();

        $mock = Mockery::mock(CodeExecutionService::class);
        $mock->shouldReceive('execute')
            ->once()
            ->with('php', $submission->source_code, Mockery::type('array'))
            ->andReturn([
                [
                    'id' => (string) $assessment->testCases[0]->id,
                    'status' => 'error',
                    'error_type' => 'syntax_error',
                    'error' => 'Unexpected token',
                ],
            ]);
        $this->app->instance(CodeExecutionService::class, $mock);

        $service = $this->app->make(CodingAssessmentService::class);
        $evaluated = $service->evaluateSubmission($submission);

        $this->assertSame('failed', $evaluated->status);
        $this->assertSame('syntax_error', $evaluated->error_type);
        $this->assertEquals(0.0, (float) $evaluated->score);
        $this->assertNotNull($evaluated->error_message);
    }

    public function test_evaluate_submission_with_full_timeout(): void
    {
        [$submission, $assessment] = $this->createSubmissionFixture();

        $mock = Mockery::mock(CodeExecutionService::class);
        $mock->shouldReceive('execute')
            ->once()
            ->with('php', $submission->source_code, Mockery::type('array'))
            ->andReturn([
                [
                    'id' => (string) $assessment->testCases[0]->id,
                    'status' => 'timeout',
                    'execution_time_ms' => 5000,
                    'memory_kb' => 128,
                ],
            ]);
        $this->app->instance(CodeExecutionService::class, $mock);

        $service = $this->app->make(CodingAssessmentService::class);
        $evaluated = $service->evaluateSubmission($submission);

        $this->assertSame('timeout', $evaluated->status);
        $this->assertSame('timeout', $evaluated->error_type);
        $this->assertSame(0, $evaluated->passed_count);
        $this->assertSame(1, $evaluated->total_count);
    }

    /**
     * @return array{0: \App\Models\Coding\CodingSubmission, 1: \App\Models\Coding\CodingAssessment}
     */
    protected function createSubmissionFixture(int $testCaseCount = 1): array
    {
        $candidate = User::query()->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
            'department' => 'Engineering',
            'password' => bcrypt('password'),
        ]);

        $assessment = CodingAssessment::create([
            'title' => 'Sample Assessment',
            'description' => 'Sample description',
            'languages' => ['php'],
            'difficulty' => 'beginner',
            'time_limit_minutes' => 60,
            'max_score' => 100,
            'rubric' => null,
            'metadata' => [],
            'created_by' => $candidate->id,
            'updated_by' => $candidate->id,
        ]);

        for ($i = 0; $i < $testCaseCount; $i++) {
            $assessment->testCases()->create([
                'name' => 'Case '.($i + 1),
                'input' => '1 2',
                'expected_output' => '3',
                'weight' => 1,
                'is_hidden' => $i > 0,
                'timeout_seconds' => 5,
            ]);
        }

        $assignment = CodingAssessmentAssignment::create([
            'coding_assessment_id' => $assessment->id,
            'candidate_id' => $candidate->id,
            'status' => 'pending',
            'assigned_by' => $candidate->id,
            'assigned_at' => now(),
        ]);

        $submission = CodingSubmission::create([
            'assignment_id' => $assignment->id,
            'coding_assessment_id' => $assessment->id,
            'candidate_id' => $candidate->id,
            'language' => 'php',
            'source_code' => '<?php echo 42; ?>',
            'status' => 'pending',
            'total_count' => $testCaseCount,
        ]);

        $assessment = $assessment->fresh(['testCases']);

        return [$submission->fresh(), $assessment];
    }
}
