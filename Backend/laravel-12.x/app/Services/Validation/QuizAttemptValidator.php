<?php

namespace App\Services\Validation;

use App\Models\Quiz_attempt;
use App\Models\Question;
use Carbon\Carbon;

/**
 * Comprehensive validation service for quiz attempts
 * Handles business logic validation like ownership, state transitions, expiration
 */
class QuizAttemptValidator
{
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_EXPIRED = 'expired';

    /**
     * Validate that user owns the attempt
     */
    public function validateOwnership(Quiz_attempt $attempt, int $userId): bool
    {
        return $attempt->student_id === $userId;
    }

    /**
     * Validate that attempt is not expired and update status if needed
     *
     * Returns true if attempt is expired, false otherwise
     */
    public function validateAndUpdateExpiration(Quiz_attempt &$attempt): bool
    {
        if ($attempt->status === self::STATUS_SUBMITTED) {
            return false;
        }

        if ($attempt->isExpired()) {
            if ($attempt->status !== self::STATUS_EXPIRED) {
                $attempt->status = self::STATUS_EXPIRED;
                $attempt->save();
            }
            return true;
        }

        return false;
    }

    /**
     * Validate that attempt is still active (not submitted or expired)
     */
    public function isAttemptActive(Quiz_attempt $attempt): bool
    {
        if ($attempt->status === self::STATUS_SUBMITTED) {
            return false;
        }

        return !$this->validateAndUpdateExpiration($attempt);
    }

    /**
     * Validate that question belongs to attempt's quiz category
     */
    public function questionBelongsToQuiz(Question $question, Quiz_attempt $attempt): bool
    {
        return $question->category_id === $attempt->quiz->category_id;
    }

    /**
     * Validate that question option belongs to question
     */
    public function optionBelongsToQuestion(int $optionId, Question $question): bool
    {
        return $question->options()
            ->where('id', $optionId)
            ->exists();
    }

    /**
     * Validate answer requirements based on question type
     */
    public function validateAnswer(string $questionType, ?int $optionId, ?string $textAnswer): array
    {
        $errors = [];

        $questionType = Question::normalizeQuestionType($questionType) ?? $questionType;

        if ($questionType === Question::TYPE_SHORT_ANSWER) {
            if (empty(trim((string) $textAnswer))) {
                $errors[] = 'Text answer is required for short answer questions.';
            }
        } elseif (in_array($questionType, [Question::TYPE_MCQ, Question::TYPE_TRUE_FALSE, Question::TYPE_MULTI_SELECT], true)) {
            if (empty($optionId)) {
                $errors[] = 'Option selection is required for choice-based questions.';
            }
        }

        return $errors;
    }

    /**
     * Validate attempt timer constraints
     */
    public function getRemainingSeconds(Quiz_attempt $attempt): int
    {
        if (!$attempt->expires_at) {
            return 0;
        }

        $seconds = now()->diffInSeconds($attempt->expires_at, false);
        return max($seconds, 0);
    }

    /**
     * Validate that no duplicate active attempts exist
     */
    public function hasDuplicateActiveAttempt(int $studentId, int $quizId): bool
    {
        $now = now();

        return Quiz_attempt::where('student_id', $studentId)
            ->where('quiz_id', $quizId)
            ->where('status', self::STATUS_IN_PROGRESS)
            ->where(function ($query) use ($now) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', $now);
            })
            ->exists();
    }

    /**
     * Validate student can attempt quiz (rate limiting, etc)
     */
    public function canAttemptQuiz(int $studentId, int $quizId): array
    {
        $errors = [];

        // Check for active attempt
        if ($this->hasDuplicateActiveAttempt($studentId, $quizId)) {
            $errors[] = [
                'code' => 'active_attempt_exists',
                'message' => 'An active attempt already exists for this quiz.',
            ];
        }

        return $errors;
    }

    /**
     * Validate state transition for submission
     */
    public function canSubmitAttempt(Quiz_attempt $attempt): array
    {
        $errors = [];

        if ($attempt->status === self::STATUS_SUBMITTED) {
            $errors[] = [
                'code' => 'attempt_already_submitted',
                'message' => 'This attempt has already been submitted.',
            ];
        }

        if ($attempt->isExpired()) {
            $errors[] = [
                'code' => 'attempt_expired',
                'message' => 'This attempt has expired and cannot be submitted.',
            ];
        }

        return $errors;
    }
}
