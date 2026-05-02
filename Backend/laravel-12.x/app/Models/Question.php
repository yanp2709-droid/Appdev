<?php

namespace App\Models;

use App\Models\QuestionOption;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    public const TYPE_MCQ = 'mcq';
    public const TYPE_TRUE_FALSE = 'tf';
    public const TYPE_MULTI_SELECT = 'multi_select';
    public const TYPE_SHORT_ANSWER = 'short_answer';

    public const TYPE_ALIASES = [
        'mcq' => self::TYPE_MCQ,
        'multiple_choice' => self::TYPE_MCQ,
        'tf' => self::TYPE_TRUE_FALSE,
        'true_false' => self::TYPE_TRUE_FALSE,
        'multi_select' => self::TYPE_MULTI_SELECT,
        'short_answer' => self::TYPE_SHORT_ANSWER,
    ];

    public const API_TYPE_MAP = [
        self::TYPE_MCQ => 'multiple_choice',
        self::TYPE_TRUE_FALSE => 'true_false',
        self::TYPE_MULTI_SELECT => 'multi_select',
        self::TYPE_SHORT_ANSWER => 'short_answer',
    ];

    protected $fillable = [
        'category_id',
        'quiz_id',
        'question_type',
        'question_text',
        'points',
        'answer_key',
    ];

    protected $casts = [
        'points' => 'integer',
    ];

    // Relation to category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    // Relation to options (for MCQ, TF, Ordering)
    public function options()
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order_index');
    }

    public static function normalizeQuestionType(?string $type): ?string
    {
        if ($type === null) {
            return null;
        }

        $normalized = strtolower(trim($type));

        return self::TYPE_ALIASES[$normalized] ?? null;
    }

    public static function toApiQuestionType(?string $type): ?string
    {
        $normalized = self::normalizeQuestionType($type);

        return $normalized ? (self::API_TYPE_MAP[$normalized] ?? $normalized) : null;
    }

    public static function validatePayload(array $payload, ?self $question = null): array
    {
        $errors = [];

        $questionText = trim($payload['question_text'] ?? '');
        if ($questionText === '') {
            $errors[] = 'Question text is required.';
        }

        $questionType = self::normalizeQuestionType($payload['question_type'] ?? null);
        if ($questionType === null) {
            $errors[] = 'Question type is invalid.';
            return $errors;
        }

        $points = $payload['points'] ?? null;
        if ($points !== null && $points !== '') {
            if (!is_numeric($points) || (int) $points != $points) {
                $errors[] = 'Points must be a whole number.';
            } elseif ((int) $points < 1) {
                $errors[] = 'Points must be at least 1.';
            } elseif ((int) $points > 1000) {
                $errors[] = 'Points cannot exceed 1000.';
            }
        }

        $optionsPayload = $payload['options'] ?? [];
        $optionErrors = [];
        $correctCount = 0;
        $seenTexts = [];
        $optionIds = [];

        if (in_array($questionType, [self::TYPE_MCQ, self::TYPE_TRUE_FALSE, self::TYPE_MULTI_SELECT], true)) {
            if (!is_array($optionsPayload)) {
                $errors[] = 'Options must be provided as an array.';
            } else {
                foreach ($optionsPayload as $index => $option) {
                    $text = trim($option['option_text'] ?? '');
                    if ($text === '') {
                        $optionErrors[] = sprintf('Option #%d must have a non-empty label.', $index + 1);
                        continue;
                    }

                    $normalizedText = mb_strtolower($text);
                    if (in_array($normalizedText, $seenTexts, true)) {
                        $optionErrors[] = sprintf('Option text "%s" is duplicated.', $text);
                    }

                    $seenTexts[] = $normalizedText;

                    if (!empty($option['is_correct'])) {
                        $correctCount++;
                    }

                    if (isset($option['id']) && $option['id'] !== null) {
                        $optionIds[] = $option['id'];
                    }
                }

                if ($questionType === self::TYPE_TRUE_FALSE) {
                    if (count($optionsPayload) !== 2) {
                        $errors[] = 'True/False questions must have exactly 2 options.';
                    }

                    if ($correctCount !== 1) {
                        $errors[] = 'True/False questions must have exactly 1 correct option.';
                    }

                    if (!self::hasTrueFalseOptionLabels($seenTexts)) {
                        $errors[] = 'True/False option labels should clearly represent True and False.';
                    }
                }

                if ($questionType === self::TYPE_MULTI_SELECT) {
                    if (count($optionsPayload) < 2) {
                        $errors[] = 'Multi-select questions must have at least 2 options.';
                    }

                    if ($correctCount === 0) {
                        $errors[] = 'Multi-select questions must have at least 1 correct option.';
                    }
                }

                if ($questionType === self::TYPE_MCQ) {
                    if (count($optionsPayload) < 2) {
                        $errors[] = 'Multiple choice questions must have at least 2 options.';
                    }

                    if ($correctCount !== 1) {
                        $errors[] = 'Multiple choice questions must have exactly 1 correct option.';
                    }
                }
            }
        }

        if ($questionType === self::TYPE_SHORT_ANSWER && trim($payload['answer_key'] ?? '') === '') {
            $errors[] = 'Short answer questions must have an answer key or rubric.';
        }

        if ($question !== null && !empty($optionIds) && is_array($optionsPayload)) {
            $allowedOptionIds = $question->options()->pluck('id')->all();
            $invalidIds = array_diff($optionIds, $allowedOptionIds);
            if (!empty($invalidIds)) {
                $errors[] = 'One or more option IDs do not belong to this question.';
            }
        }

        return array_merge($errors, $optionErrors);
    }

    public static function normalizeQuestionPayload(array $payload): array
    {
        if (!empty($payload['options']) && is_array($payload['options'])) {
            foreach ($payload['options'] as $index => $option) {
                $payload['options'][$index]['order_index'] = $index;
                $payload['options'][$index]['is_correct'] = !empty($option['is_correct']);
                $payload['options'][$index]['option_text'] = trim($option['option_text'] ?? '');
            }
        }

        return $payload;
    }

    public function getValidationErrors()
    {
        $payload = [
            'question_text' => $this->question_text,
            'question_type' => $this->question_type,
            'answer_key' => $this->answer_key,
            'options' => $this->options->map(function (QuestionOption $option) {
                return [
                    'id' => $option->id,
                    'option_text' => $option->option_text,
                    'is_correct' => $option->is_correct,
                ];
            })->all(),
        ];

        return self::validatePayload($payload, $this);
    }

    public function isPreviewReady(): bool
    {
        return empty($this->getValidationErrors());
    }

    public function getPreviewPayload(bool $includeCorrectAnswer = false): array
    {
        return [
            'id' => $this->id,
            'question_text' => $this->question_text,
            'type' => self::toApiQuestionType($this->question_type),
            'points' => $this->points,
            'options' => $this->options->map(function (QuestionOption $option) use ($includeCorrectAnswer) {
                $payload = [
                    'id' => $option->id,
                    'option_text' => $option->option_text,
                    'order_index' => $option->order_index,
                ];

                if ($includeCorrectAnswer) {
                    $payload['is_correct'] = $option->is_correct;
                }

                return $payload;
            })->values()->all(),
        ];
    }

    protected static function hasTrueFalseOptionLabels(array $normalizedTexts): bool
    {
        $validLabels = ['true', 'false', 't', 'f', 'yes', 'no', 'y', 'n'];

        return count(array_filter($normalizedTexts, static fn ($text) => in_array($text, $validLabels, true))) >= 2;
    }
}
