<?php

namespace Database\Seeders\Question;

use App\Models\Question\Question;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class QuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $answerTypes = ['short', 'long_detail', 'dropdown', 'checkbox', 'file_upload'];
        $availableTags = ['personal', 'work', 'education', 'skills', 'books', 'goals', 'experience', 'projects', 'software', 'business', 'analysis'];

        $questions = [
            // HR Interview Questions
            'Tell us about yourself.',
            'What are your greatest strengths?',
            'Why do you want to work for our company?',
            'How do you handle stress and pressure?',
            'Describe a conflict you faced at work and how you resolved it.',
            'What motivates you at work?',
            'Where do you see yourself in 5 years?',
            'Why should we hire you?',
            'What are your salary expectations?',
            'Are you willing to relocate?',

            // Software Engineer Questions
            'What programming languages are you proficient in?',
            'Describe a recent project you worked on.',
            'Explain the software development lifecycle.',
            'What is the difference between REST and SOAP?',
            'What are design patterns and why are they important?',
            'How do you manage version control?',
            'What is your experience with unit testing?',
            'Describe a time you debugged a complex issue.',
            'What tools do you use for CI/CD?',
            'How do you ensure code quality in a team environment?',

            // Business Analyst Questions
            'How do you gather business requirements?',
            'Describe a time you translated a business need into a technical solution.',
            'What tools do you use for process modeling?',
            'How do you manage stakeholder expectations?',
            'Describe your experience with data analysis.',
            'What’s your approach to risk analysis?',
            'How do you handle changing requirements?',
            'What is your experience with Agile methodologies?',
            'How do you validate a business requirement?',
            'How do you prioritize conflicting requirements?',
        ];

        foreach ($questions as $text) {
            $type = $answerTypes[array_rand($answerTypes)];

            Question::create([
                'question_name' => $text,
                'answer_type'   => $type,
                'is_required'   => fake()->boolean(),

                'options' => in_array($type, ['dropdown', 'checkbox'])
                    ? fake()->randomElements(
                        ['Yes', 'No', 'Maybe', 'N/A', 'Strongly Agree', 'Disagree', 'Neutral'],
                        rand(2, 5)
                    )
                    : null,

                'tags' => fake()->randomElements($availableTags, rand(1, 3)),

                'created_by' => 1,
            ]);
        }

        $remaining = 100 - count($questions);

        for ($i = 0; $i < $remaining; $i++) {
            $type = $answerTypes[array_rand($answerTypes)];

            Question::create([
                'question_name' => Str::title(fake()->sentence(rand(3, 6), true)),
                'answer_type'   => $type,
                'is_required'   => fake()->boolean(),

                'options' => in_array($type, ['dropdown', 'checkbox'])
                    ? fake()->randomElements(
                        ['Yes', 'No', 'Maybe', 'N/A', 'Strongly Agree', 'Disagree', 'Neutral'],
                        rand(2, 5)
                    )
                    : null,

                'tags' => fake()->randomElements($availableTags, rand(1, 3)),

                'created_by' => 1,
            ]);
        }
    }
}
