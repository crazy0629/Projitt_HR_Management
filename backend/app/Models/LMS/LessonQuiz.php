<?php

namespace App\Models\LMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LessonQuiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'title',
        'description',
        'passing_score',
        'attempts_allowed',
        'time_limit_minutes',
        'randomize_questions',
        'randomize_options',
        'show_results_immediately',
    ];

    protected $casts = [
        'passing_score' => 'integer',
        'attempts_allowed' => 'integer',
        'time_limit_minutes' => 'integer',
        'randomize_questions' => 'boolean',
        'randomize_options' => 'boolean',
        'show_results_immediately' => 'boolean',
    ];

    protected $attributes = [
        'passing_score' => 80,
        'randomize_questions' => false,
        'randomize_options' => false,
        'show_results_immediately' => true,
    ];

    // Relationships
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(CourseLesson::class, 'lesson_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(LessonQuizQuestion::class, 'quiz_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'lesson_id', 'lesson_id');
    }

    // Scopes and query methods
    public function scopeWithQuestions($query)
    {
        return $query->with(['questions' => function ($q) {
            $q->orderBy('order_index')->with(['options' => function ($oq) {
                $oq->orderBy('order_index');
            }]);
        }]);
    }

    // Helper methods
    public function hasTimeLimit(): bool
    {
        return ! is_null($this->time_limit_minutes) && $this->time_limit_minutes > 0;
    }

    public function hasAttemptsLimit(): bool
    {
        return ! is_null($this->attempts_allowed) && $this->attempts_allowed > 0;
    }

    public function getQuestionsCount(): int
    {
        return $this->questions()->count();
    }

    public function getTotalWeight(): int
    {
        return $this->questions()->sum('weight');
    }

    public function getRandomizedQuestions(): array
    {
        $questions = $this->questions()->with('options')->get();

        if ($this->randomize_questions) {
            $questions = $questions->shuffle();
        } else {
            $questions = $questions->sortBy('order_index');
        }

        return $questions->map(function ($question) {
            $options = $question->options;

            if ($this->randomize_options) {
                $options = $options->shuffle();
            } else {
                $options = $options->sortBy('order_index');
            }

            $question->setRelation('options', $options);

            return $question;
        })->values()->toArray();
    }

    public function calculateScore(array $answers): array
    {
        $questions = $this->questions()->with('options')->get();
        $totalWeight = $this->getTotalWeight();
        $earnedWeight = 0;
        $results = [];

        foreach ($questions as $question) {
            $questionId = $question->id;
            $userAnswers = $answers[$questionId] ?? [];

            if (! is_array($userAnswers)) {
                $userAnswers = [$userAnswers];
            }

            $correctOptions = $question->options->where('is_correct', true)->pluck('id')->toArray();
            $isCorrect = empty(array_diff($correctOptions, $userAnswers)) &&
                        empty(array_diff($userAnswers, $correctOptions));

            if ($isCorrect) {
                $earnedWeight += $question->weight;
            }

            $results[$questionId] = [
                'user_answers' => $userAnswers,
                'correct_answers' => $correctOptions,
                'is_correct' => $isCorrect,
                'weight' => $question->weight,
                'earned_weight' => $isCorrect ? $question->weight : 0,
            ];
        }

        $score = $totalWeight > 0 ? round(($earnedWeight / $totalWeight) * 100) : 0;
        $passed = $score >= $this->passing_score;

        return [
            'score' => $score,
            'passed' => $passed,
            'total_weight' => $totalWeight,
            'earned_weight' => $earnedWeight,
            'questions' => $results,
        ];
    }

    public function getAttemptNumberForUser(int $enrollmentId): int
    {
        return $this->attempts()
            ->where('enrollment_id', $enrollmentId)
            ->max('attempt_no') + 1;
    }

    public function canUserAttempt(int $enrollmentId): bool
    {
        if (! $this->hasAttemptsLimit()) {
            return true;
        }

        $attemptCount = $this->attempts()
            ->where('enrollment_id', $enrollmentId)
            ->count();

        return $attemptCount < $this->attempts_allowed;
    }

    public function getUserBestAttempt(int $enrollmentId): ?QuizAttempt
    {
        return $this->attempts()
            ->where('enrollment_id', $enrollmentId)
            ->orderBy('score', 'desc')
            ->orderBy('submitted_at', 'desc')
            ->first();
    }

    public function getUserLastAttempt(int $enrollmentId): ?QuizAttempt
    {
        return $this->attempts()
            ->where('enrollment_id', $enrollmentId)
            ->orderBy('submitted_at', 'desc')
            ->first();
    }

    public function hasUserPassed(int $enrollmentId): bool
    {
        $bestAttempt = $this->getUserBestAttempt($enrollmentId);

        return $bestAttempt && $bestAttempt->is_passed;
    }
}
