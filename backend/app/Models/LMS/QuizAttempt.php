<?php

namespace App\Models\LMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'enrollment_id',
        'score',
        'is_passed',
        'attempt_no',
        'answers_json',
        'time_taken_seconds',
        'started_at',
        'submitted_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'is_passed' => 'boolean',
        'attempt_no' => 'integer',
        'answers_json' => 'array',
        'time_taken_seconds' => 'integer',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    protected $attributes = [
        'score' => 0,
        'is_passed' => false,
        'attempt_no' => 1,
    ];

    // Relationships
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(CourseLesson::class, 'lesson_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id');
    }

    // Scopes
    public function scopePassed($query)
    {
        return $query->where('is_passed', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('is_passed', false);
    }

    public function scopeByAttemptNumber($query, $attemptNo)
    {
        return $query->where('attempt_no', $attemptNo);
    }

    public function scopeOrderByScore($query, $direction = 'desc')
    {
        return $query->orderBy('score', $direction);
    }

    public function scopeOrderByDate($query, $direction = 'desc')
    {
        return $query->orderBy('submitted_at', $direction);
    }

    // Helper methods
    public function isPassed(): bool
    {
        return $this->is_passed;
    }

    public function isFailed(): bool
    {
        return ! $this->is_passed;
    }

    public function getTimeTakenMinutes(): ?float
    {
        return $this->time_taken_seconds ? round($this->time_taken_seconds / 60, 2) : null;
    }

    public function getGradeLetter(): string
    {
        if ($this->score >= 90) {
            return 'A';
        }
        if ($this->score >= 80) {
            return 'B';
        }
        if ($this->score >= 70) {
            return 'C';
        }
        if ($this->score >= 60) {
            return 'D';
        }

        return 'F';
    }

    public function getAnswers(): array
    {
        return $this->answers_json ?? [];
    }

    public function setAnswers(array $answers): void
    {
        $this->answers_json = $answers;
    }

    public function complete(array $answers, array $gradeResults): void
    {
        $this->setAnswers($answers);
        $this->score = $gradeResults['score'];
        $this->is_passed = $gradeResults['passed'];
        $this->submitted_at = now();

        if ($this->started_at) {
            $this->time_taken_seconds = $this->started_at->diffInSeconds(now());
        }

        $this->save();

        // Log events
        $eventType = $this->is_passed ? 'quiz_completed' : 'quiz_failed';
        LMSEvent::logEvent(
            $this->enrollment->employee_id,
            $eventType,
            $this->enrollment->course_id,
            $this->lesson_id,
            null,
            [
                'attempt_id' => $this->id,
                'attempt_no' => $this->attempt_no,
                'score' => $this->score,
                'time_taken' => $this->time_taken_seconds,
            ]
        );

        // If passed, mark lesson as completed
        if ($this->is_passed) {
            $lessonProgress = $this->enrollment->getLessonProgress($this->lesson_id);
            if ($lessonProgress && ! $lessonProgress->isCompleted()) {
                $lessonProgress->complete();
            }
        }
    }

    public function start(): void
    {
        $this->started_at = now();
        $this->save();

        LMSEvent::logEvent(
            $this->enrollment->employee_id,
            'quiz_started',
            $this->enrollment->course_id,
            $this->lesson_id,
            null,
            [
                'attempt_id' => $this->id,
                'attempt_no' => $this->attempt_no,
            ]
        );
    }

    public function getDetailedResults(): array
    {
        $quiz = $this->lesson->quiz;
        if (! $quiz) {
            return [];
        }

        $questions = $quiz->questions()->with('options')->get();
        $userAnswers = $this->getAnswers();
        $results = [];

        foreach ($questions as $question) {
            $questionId = $question->id;
            $selectedOptions = $userAnswers[$questionId] ?? [];

            if (! is_array($selectedOptions)) {
                $selectedOptions = [$selectedOptions];
            }

            $correctOptions = $question->getCorrectOptionIds();
            $isCorrect = $question->validateAnswer($selectedOptions);

            $results[] = [
                'question_id' => $questionId,
                'question_text' => $question->text,
                'question_type' => $question->type,
                'selected_options' => $selectedOptions,
                'correct_options' => $correctOptions,
                'is_correct' => $isCorrect,
                'weight' => $question->weight,
                'explanation' => $question->explanation,
                'options' => $question->options->map(function ($option) use ($selectedOptions) {
                    return [
                        'id' => $option->id,
                        'text' => $option->text,
                        'is_correct' => $option->is_correct,
                        'was_selected' => in_array($option->id, $selectedOptions),
                    ];
                })->toArray(),
            ];
        }

        return $results;
    }

    public static function createForUser(int $enrollmentId, int $lessonId): self
    {
        $enrollment = Enrollment::findOrFail($enrollmentId);
        $lesson = CourseLesson::findOrFail($lessonId);
        $quiz = $lesson->quiz;

        if (! $quiz) {
            throw new \Exception('Lesson does not have a quiz');
        }

        $attemptNo = $quiz->getAttemptNumberForUser($enrollmentId);

        if (! $quiz->canUserAttempt($enrollmentId)) {
            throw new \Exception('User has exceeded maximum attempts for this quiz');
        }

        return static::create([
            'lesson_id' => $lessonId,
            'enrollment_id' => $enrollmentId,
            'attempt_no' => $attemptNo,
        ]);
    }
}
