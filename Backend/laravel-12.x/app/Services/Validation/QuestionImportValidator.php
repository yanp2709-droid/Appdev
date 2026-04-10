<?php

namespace App\Services\Validation;

use App\Models\Question;

/**
 * Validation for question imports
 */
class QuestionImportValidator
{
    public const REQUIRED_COLUMNS = [
        'question_text',
        'category',
        'question_type',
        'options',
        'correct_answer',
        'points',
        'answer_key',
    ];

    public const QUESTION_TYPES = ['mcq', 'multiple_choice', 'tf', 'true_false', 'multi_select', 'short_answer'];

    /**
     * Validate question type
     */
    public function validateQuestionType(string $type): array
    {
        if (!in_array($type, self::QUESTION_TYPES, true)) {
            return [
                'field' => 'question_type',
                'message' => 'Question type must be one of: ' . implode(', ', self::QUESTION_TYPES),
            ];
        }
        return [];
    }

    /**
     * Validate options for question type
     */
    public function validateOptions(string $questionType, array $options): array
    {
        $errors = [];

        $questionType = Question::normalizeQuestionType($questionType) ?? $questionType;

        if ($questionType === Question::TYPE_SHORT_ANSWER) {
            // Short answers don't need options
            return [];
        }

        if ($questionType === Question::TYPE_TRUE_FALSE) {
            // True/False must have exactly 2 options
            if (count($options) !== 2) {
                $errors[] = [
                    'field' => 'options',
                    'message' => 'True/False questions must have exactly 2 options.',
                ];
            }
        } elseif (in_array($questionType, [Question::TYPE_MCQ, Question::TYPE_MULTI_SELECT], true)) {
            // Must have at least 2 options
            if (count($options) < 2) {
                $errors[] = [
                    'field' => 'options',
                    'message' => 'Questions must have at least 2 options.',
                ];
            }
        }

        return $errors;
    }

    /**
     * Validate points
     */
    public function validatePoints($points): array
    {
        if ($points === null) {
            return [];
        }

        if (!is_numeric($points) || (int) $points < 1) {
            return [
                [
                    'field' => 'points',
                    'message' => 'Points must be a positive integer.',
                ],
            ];
        }

        return [];
    }

    /**
     * Validate answer key for short answer questions
     */
    public function validateAnswerKey(string $questionType, ?string $answerKey): array
    {
        if ($questionType === 'short_answer') {
            if (empty(trim((string) $answerKey))) {
                return [
                    [
                        'field' => 'answer_key',
                        'message' => 'Answer key is required for short answer questions.',
                    ],
                ];
            }
        }
        return [];
    }

    /**
     * Validate points field max length
     */
    public function validateFieldLengths(array $question): array
    {
        $errors = [];

        if (strlen($question['question_text'] ?? '') > 1000) {
            $errors[] = [
                'field' => 'question_text',
                'message' => 'Question text cannot exceed 1000 characters.',
            ];
        }

        if (strlen($question['answer_key'] ?? '') > 500) {
            $errors[] = [
                'field' => 'answer_key',
                'message' => 'Answer key cannot exceed 500 characters.',
            ];
        }

        return $errors;
    }
}
