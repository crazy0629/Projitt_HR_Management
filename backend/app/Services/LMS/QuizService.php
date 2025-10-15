<?php

namespace App\Services\LMS;

use App\Models\LMS\CourseLesson;
use App\Models\LMS\Enrollment;
use App\Models\LMS\QuizAttempt;
use Illuminate\Support\Facades\DB;

class QuizService
{
    /**
     * Start a new quiz attempt
     */
    public function startQuizAttempt(int $enrollmentId, int $lessonId): QuizAttempt
    {
        return DB::transaction(function () use ($enrollmentId, $lessonId) {
            $enrollment = Enrollment::findOrFail($enrollmentId);
            $lesson = CourseLesson::findOrFail($lessonId);
            $quiz = $lesson->quiz;

            if (! $quiz) {
                throw new \Exception('Lesson does not have a quiz');
            }

            // Check if user can attempt
            if (! $quiz->canUserAttempt($enrollmentId)) {
                throw new \Exception('Maximum attempts exceeded for this quiz');
            }

            // Create quiz attempt
            $attempt = QuizAttempt::createForUser($enrollmentId, $lessonId);
            $attempt->start();

            return $attempt;
        });
    }

    /**
     * Submit quiz answers and grade
     */
    public function submitQuizAttempt(int $attemptId, array $answers): array
    {
        return DB::transaction(function () use ($attemptId, $answers) {
            $attempt = QuizAttempt::findOrFail($attemptId);
            $quiz = $attempt->lesson->quiz;

            if (! $quiz) {
                throw new \Exception('Quiz not found');
            }

            // Grade the answers
            $gradeResults = $quiz->calculateScore($answers);

            // Complete the attempt
            $attempt->complete($answers, $gradeResults);

            return [
                'attempt_id' => $attempt->id,
                'score' => $gradeResults['score'],
                'passed' => $gradeResults['passed'],
                'total_weight' => $gradeResults['total_weight'],
                'earned_weight' => $gradeResults['earned_weight'],
                'questions' => $gradeResults['questions'],
                'can_retry' => $quiz->canUserAttempt($attempt->enrollment_id),
                'attempts_remaining' => $quiz->hasAttemptsLimit()
                    ? $quiz->attempts_allowed - $quiz->attempts()->where('enrollment_id', $attempt->enrollment_id)->count()
                    : null,
            ];
        });
    }

    /**
     * Get quiz data for taking
     */
    public function getQuizForAttempt(int $attemptId): array
    {
        $attempt = QuizAttempt::with(['lesson.quiz', 'enrollment'])->findOrFail($attemptId);
        $quiz = $attempt->lesson->quiz;

        if (! $quiz) {
            throw new \Exception('Quiz not found');
        }

        // Get randomized questions
        $questions = $quiz->getRandomizedQuestions();

        return [
            'attempt_id' => $attempt->id,
            'attempt_no' => $attempt->attempt_no,
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'passing_score' => $quiz->passing_score,
                'time_limit_minutes' => $quiz->time_limit_minutes,
                'has_time_limit' => $quiz->hasTimeLimit(),
                'questions_count' => count($questions),
            ],
            'questions' => $questions,
            'started_at' => $attempt->started_at,
            'time_remaining_seconds' => $quiz->hasTimeLimit() && $attempt->started_at
                ? max(0, ($quiz->time_limit_minutes * 60) - $attempt->started_at->diffInSeconds(now()))
                : null,
        ];
    }

    /**
     * Get quiz attempt results
     */
    public function getQuizResults(int $attemptId): array
    {
        $attempt = QuizAttempt::with(['lesson.quiz', 'enrollment.employee'])->findOrFail($attemptId);
        $quiz = $attempt->lesson->quiz;

        $detailedResults = $attempt->getDetailedResults();

        return [
            'attempt' => [
                'id' => $attempt->id,
                'attempt_no' => $attempt->attempt_no,
                'score' => $attempt->score,
                'is_passed' => $attempt->is_passed,
                'grade_letter' => $attempt->getGradeLetter(),
                'time_taken_minutes' => $attempt->getTimeTakenMinutes(),
                'submitted_at' => $attempt->submitted_at,
            ],
            'quiz' => [
                'title' => $quiz->title,
                'passing_score' => $quiz->passing_score,
                'show_results_immediately' => $quiz->show_results_immediately,
            ],
            'results' => $detailedResults,
            'can_retry' => $quiz->canUserAttempt($attempt->enrollment_id),
            'attempts_remaining' => $quiz->hasAttemptsLimit()
                ? max(0, $quiz->attempts_allowed - $quiz->attempts()->where('enrollment_id', $attempt->enrollment_id)->count())
                : null,
            'best_attempt' => $quiz->getUserBestAttempt($attempt->enrollment_id)?->only([
                'id', 'score', 'is_passed', 'submitted_at',
            ]),
        ];
    }

    /**
     * Get user's quiz history for a lesson
     */
    public function getUserQuizHistory(int $enrollmentId, int $lessonId): array
    {
        $lesson = CourseLesson::with('quiz')->findOrFail($lessonId);
        $quiz = $lesson->quiz;

        if (! $quiz) {
            return [
                'has_quiz' => false,
                'attempts' => [],
            ];
        }

        $attempts = $quiz->attempts()
            ->where('enrollment_id', $enrollmentId)
            ->orderBy('attempt_no', 'desc')
            ->get();

        $bestAttempt = $quiz->getUserBestAttempt($enrollmentId);

        return [
            'has_quiz' => true,
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'passing_score' => $quiz->passing_score,
                'attempts_allowed' => $quiz->attempts_allowed,
                'has_attempts_limit' => $quiz->hasAttemptsLimit(),
            ],
            'attempts' => $attempts->map(function ($attempt) {
                return [
                    'id' => $attempt->id,
                    'attempt_no' => $attempt->attempt_no,
                    'score' => $attempt->score,
                    'is_passed' => $attempt->is_passed,
                    'grade_letter' => $attempt->getGradeLetter(),
                    'time_taken_minutes' => $attempt->getTimeTakenMinutes(),
                    'submitted_at' => $attempt->submitted_at,
                ];
            }),
            'best_attempt' => $bestAttempt ? [
                'score' => $bestAttempt->score,
                'is_passed' => $bestAttempt->is_passed,
                'attempt_no' => $bestAttempt->attempt_no,
            ] : null,
            'can_attempt' => $quiz->canUserAttempt($enrollmentId),
            'attempts_remaining' => $quiz->hasAttemptsLimit()
                ? max(0, $quiz->attempts_allowed - $attempts->count())
                : null,
            'has_passed' => $quiz->hasUserPassed($enrollmentId),
        ];
    }
}
