<?php

namespace Tests\Feature;

use App\Models\Psychometric\PsychometricTest;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PsychometricModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_psychometric_flow_from_creation_to_reporting(): void
    {
        $admin = User::query()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'department' => 'HR',
            'password' => Hash::make('password'),
        ]);

        $candidate = User::query()->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'department' => 'Engineering',
            'password' => Hash::make('password'),
        ]);

        Sanctum::actingAs($admin);

        $testPayload = [
            'title' => 'Leadership Potential Assessment',
            'category' => 'leadership',
            'description' => 'Evaluate leadership competencies.',
            'instructions' => 'Answer honestly.',
            'time_limit_minutes' => 30,
            'allowed_attempts' => 1,
            'randomize_questions' => false,
            'is_published' => true,
            'scoring_model' => [
                'bands' => [
                    'high' => ['min' => 80],
                    'medium' => ['min' => 50],
                ],
            ],
            'dimensions' => [
                [
                    'key' => 'communication',
                    'name' => 'Communication',
                    'weight' => 1.5,
                ],
                [
                    'key' => 'problem_solving',
                    'name' => 'Problem Solving',
                    'weight' => 1.2,
                ],
            ],
            'questions' => [
                [
                    'reference_code' => 'COMM_1',
                    'question_text' => 'I proactively communicate with stakeholders.',
                    'question_type' => 'likert',
                    'dimension_key' => 'communication',
                    'weight' => 2,
                    'options' => [
                        ['label' => 'Strongly Disagree', 'score' => 1],
                        ['label' => 'Disagree', 'score' => 2],
                        ['label' => 'Neutral', 'score' => 3],
                        ['label' => 'Agree', 'score' => 4],
                        ['label' => 'Strongly Agree', 'score' => 5],
                    ],
                ],
                [
                    'reference_code' => 'PROB_1',
                    'question_text' => 'Comfort working through ambiguous challenges.',
                    'question_type' => 'multiple_choice',
                    'dimension_key' => 'problem_solving',
                    'weight' => 3,
                    'options' => [
                        ['label' => 'Low', 'score' => 1, 'weight' => 1],
                        ['label' => 'Moderate', 'score' => 3, 'weight' => 1],
                        ['label' => 'High', 'score' => 5, 'weight' => 1],
                    ],
                ],
                [
                    'reference_code' => 'PROB_2',
                    'question_text' => 'Select strategies you use to resolve conflicts.',
                    'question_type' => 'multi_select',
                    'dimension_key' => 'communication',
                    'weight' => 1,
                    'options' => [
                        ['label' => 'Active listening', 'score' => 3],
                        ['label' => 'Escalate immediately', 'score' => -1],
                        ['label' => 'Collaborative solutioning', 'score' => 4],
                    ],
                ],
            ],
        ];

        $createResponse = $this->postJson('/psychometric/tests', $testPayload);
        $createResponse->assertCreated();

        $testData = $createResponse->json('data');
        $testId = $testData['id'];

        $assignResponse = $this->postJson("/psychometric/tests/{$testId}/assign", [
            'candidate_ids' => [$candidate->id],
            'target_role' => 'Engineering Manager',
            'time_limit_minutes' => 25,
        ]);

        $assignResponse->assertCreated();
        $assignmentId = $assignResponse->json('data.0.id');

        $startResponse = $this->postJson("/psychometric/assignments/{$assignmentId}/start");
        $startResponse->assertOk();

        $test = PsychometricTest::with('questions.options')->findOrFail($testId);
        $responses = [];
        foreach ($test->questions as $question) {
            $options = $question->options;
            if ($question->question_type === 'likert' || $question->question_type === 'multiple_choice') {
                $responses[] = [
                    'question_id' => $question->id,
                    'option_id' => $options->last()->id,
                    'time_spent_seconds' => 30,
                ];
            } elseif ($question->question_type === 'multi_select') {
                $responses[] = [
                    'question_id' => $question->id,
                    'selected_option_ids' => $options->pluck('id')->take(2)->values()->toArray(),
                    'time_spent_seconds' => 20,
                ];
            }
        }

        Sanctum::actingAs($candidate);

        $submitResponse = $this->postJson("/psychometric/assignments/{$assignmentId}/submit", [
            'responses' => $responses,
        ]);

        $submitResponse->assertOk()
            ->assertJsonPath('data.status', 'scored')
            ->assertJsonPath('data.result_snapshot.percentile', fn ($value) => $value !== null);

        Sanctum::actingAs($admin);

        $summaryResponse = $this->getJson('/psychometric/reports/summary');
        $summaryResponse->assertOk();
        $summary = $summaryResponse->json('data.by_test');

        $this->assertNotEmpty($summary);
        $this->assertSame($testId, $summary[0]['test_id']);
    }
}
