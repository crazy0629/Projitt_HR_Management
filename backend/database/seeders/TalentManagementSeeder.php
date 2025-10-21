<?php

namespace Database\Seeders;

use App\Models\LearningPath;
use App\Models\Role;
use App\Models\Talent\Note;
use App\Models\Talent\Pip;
use App\Models\Talent\PipCheckin;
use App\Models\Talent\PromotionCandidate;
use App\Models\Talent\PromotionWorkflow;
use App\Models\Talent\RetentionRiskSnapshot;
use App\Models\Talent\SuccessionCandidate;
use App\Models\Talent\SuccessionRole;
use App\Models\User\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TalentManagementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('PRAGMA foreign_keys=OFF');

        // Create sample promotion workflows
        $this->createPromotionWorkflows();

        // Create sample promotion candidates
        $this->createPromotionCandidates();

        // Create succession planning data
        $this->createSuccessionData();

        // Create PIPs
        $this->createPips();

        // Create employee notes
        $this->createEmployeeNotes();

        // Create retention risk snapshots
        $this->createRetentionRiskSnapshots();

        // Re-enable foreign key checks
        DB::statement('PRAGMA foreign_keys=ON');
    }

    private function createPromotionWorkflows()
    {
        $workflows = [
            [
                'name' => 'Senior Developer Promotion',
                'description' => 'Standard workflow for promoting developers to senior level',
                'steps' => [
                    ['role' => 'manager', 'order' => 1, 'required' => true],
                    ['role' => 'hr_manager', 'order' => 2, 'required' => true],
                    ['role' => 'department_head', 'order' => 3, 'required' => false],
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Management Track Promotion',
                'description' => 'Workflow for promoting to management positions',
                'steps' => [
                    ['role' => 'direct_manager', 'order' => 1, 'required' => true],
                    ['role' => 'hr_director', 'order' => 2, 'required' => true],
                    ['role' => 'vp', 'order' => 3, 'required' => true],
                    ['role' => 'ceo', 'order' => 4, 'required' => false],
                ],
                'is_active' => true,
            ],
        ];

        foreach ($workflows as $workflow) {
            PromotionWorkflow::create($workflow);
        }
    }

    private function createPromotionCandidates()
    {
        $users = User::limit(5)->get();
        $roles = Role::limit(3)->get();
        $workflows = PromotionWorkflow::all();

        foreach ($users as $index => $user) {
            if ($roles->count() > 0 && $workflows->count() > 0) {
                $candidate = PromotionCandidate::create([
                    'employee_id' => $user->id,
                    'current_role_id' => $roles->random()->id,
                    'proposed_role_id' => $roles->random()->id,
                    'workflow_id' => $workflows->random()->id,
                    'current_salary' => rand(50000, 120000),
                    'proposed_salary' => rand(60000, 140000),
                    'justification' => 'Outstanding performance and leadership qualities demonstrated over the past year.',
                    'status' => ['draft', 'submitted', 'in_review'][$index % 3],
                    'submitted_at' => $index > 0 ? now()->subDays(rand(1, 30)) : null,
                ]);

                // Add some skills and achievements
                $candidate->update([
                    'skills' => ['Leadership', 'Technical Excellence', 'Communication'],
                    'achievements' => [
                        'Led successful project delivery',
                        'Mentored junior team members',
                        'Improved team productivity by 25%',
                    ],
                ]);
            }
        }
    }

    private function createSuccessionData()
    {
        $roles = Role::limit(3)->get();
        $users = User::limit(8)->get();

        foreach ($roles as $role) {
            $successionRole = SuccessionRole::create([
                'role_id' => $role->id,
                'incumbent_id' => $users->random()->id,
                'criticality' => ['high', 'critical', 'medium'][rand(0, 2)],
                'risk_level' => ['medium', 'high', 'low'][rand(0, 2)],
                'replacement_timeline' => ['short', 'medium', 'long'][rand(0, 2)],
                'is_active' => true,
            ]);

            // Create succession candidates for this role
            $candidates = $users->random(3);
            foreach ($candidates as $candidate) {
                SuccessionCandidate::create([
                    'succession_role_id' => $successionRole->id,
                    'employee_id' => $candidate->id,
                    'target_role_id' => $role->id,
                    'readiness' => ['ready', 'developing', 'long_term'][rand(0, 2)],
                    'target_ready_date' => now()->addMonths(rand(6, 24)),
                    'strengths' => [
                        'Strong technical skills',
                        'Excellent communication',
                        'Leadership potential',
                    ],
                    'development_areas' => [
                        'Strategic thinking',
                        'Public speaking',
                        'Financial acumen',
                    ],
                    'readiness_score' => rand(60, 95),
                ]);
            }
        }
    }

    private function createPips()
    {
        $users = User::limit(3)->get();
        $learningPaths = LearningPath::limit(2)->get();

        foreach ($users as $index => $user) {
            $pip = Pip::create([
                'employee_id' => $user->id,
                'goal_text' => 'Improve performance in key areas including time management, communication, and technical skills.',
                'learning_path_id' => $learningPaths->count() > 0 ? $learningPaths->random()->id : null,
                'mentor_id' => $users->where('id', '!=', $user->id)->random()->id,
                'start_date' => now()->subDays(rand(30, 90)),
                'end_date' => now()->addDays(rand(30, 180)),
                'checkin_frequency' => ['weekly', 'biweekly', 'monthly'][rand(0, 2)],
                'status' => ['active', 'paused', 'completed'][$index % 3],
            ]);

            // Add some check-ins
            for ($i = 0; $i < rand(2, 5); $i++) {
                PipCheckin::create([
                    'pip_id' => $pip->id,
                    'created_by' => $users->random()->id,
                    'summary' => 'Good progress shown in technical skills development. Communication improvements noted.',
                    'next_steps' => 'Continue focus on project delivery timeline management.',
                    'rating' => rand(3, 5),
                    'checkin_date' => now()->subDays(rand(1, 60)),
                ]);
            }
        }
    }

    private function createEmployeeNotes()
    {
        $users = User::limit(6)->get();

        $sampleNotes = [
            'Excellent team player, always willing to help colleagues.',
            'Shows great initiative in taking on challenging projects.',
            'Needs improvement in meeting deadlines consistently.',
            'Strong technical skills, would benefit from leadership training.',
            'Received positive feedback from client presentation.',
            'Expressed interest in career advancement opportunities.',
        ];

        foreach ($users as $user) {
            for ($i = 0; $i < rand(1, 3); $i++) {
                Note::create([
                    'employee_id' => $user->id,
                    'author_id' => $users->where('id', '!=', $user->id)->random()->id,
                    'body' => $sampleNotes[array_rand($sampleNotes)],
                    'visibility' => ['hr_only', 'manager_chain', 'employee_visible'][rand(0, 2)],
                    'is_sensitive' => rand(0, 1) === 1,
                    'created_at' => now()->subDays(rand(1, 365)),
                ]);
            }
        }
    }

    private function createRetentionRiskSnapshots()
    {
        $users = User::limit(8)->get();

        $riskFactors = [
            'workload_high',
            'compensation_below_market',
            'limited_growth_opportunities',
            'poor_work_life_balance',
            'team_dynamics_issues',
            'role_mismatch',
            'lack_of_recognition',
            'manager_relationship',
        ];

        // Create snapshots for the last 6 months
        for ($month = 0; $month < 6; $month++) {
            $period = now()->subMonths($month)->format('Y-m');

            foreach ($users as $user) {
                $risk = ['low', 'medium', 'high'][rand(0, 2)];
                $factorCount = rand(1, 4);
                $selectedFactors = array_slice($riskFactors, 0, $factorCount);

                RetentionRiskSnapshot::create([
                    'employee_id' => $user->id,
                    'period' => $period,
                    'risk' => $risk,
                    'factors' => $selectedFactors,
                    'score' => $risk === 'high' ? rand(70, 100) / 100 :
                             ($risk === 'medium' ? rand(40, 69) / 100 : rand(10, 39) / 100),
                ]);
            }
        }
    }
}
