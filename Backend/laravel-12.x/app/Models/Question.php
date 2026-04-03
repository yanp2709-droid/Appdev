<?php

namespace App\Models;

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

    // Get validation errors for this question
    public function getValidationErrors()
    {
        $errors = [];

        $questionType = self::normalizeQuestionType($this->question_type) ?? $this->question_type;

        if (in_array($questionType, [self::TYPE_MCQ, self::TYPE_MULTI_SELECT], true) && count($this->options) < 2) {
            $errors[] = 'Choice-based questions must have at least 2 options';
        }

        if ($questionType === self::TYPE_MCQ) {
            $correctCount = $this->options->where('is_correct', true)->count();
            if ($correctCount !== 1) {
                $errors[] = 'Multiple choice questions must have exactly 1 correct option';
            }
        }

        if ($questionType === self::TYPE_MULTI_SELECT) {
            $correctCount = $this->options->where('is_correct', true)->count();
            if ($correctCount === 0) {
                $errors[] = 'Multi-select questions must have at least 1 correct option';
            }
        }

        if ($questionType === self::TYPE_TRUE_FALSE) {
            if (count($this->options) !== 2) {
                $errors[] = 'True/False questions must have exactly 2 options';
            }
            if ($this->options->where('is_correct', true)->count() !== 1) {
                $errors[] = 'True/False questions must have exactly 1 correct option';
            }
        }

        if ($questionType === 'ordering' && count($this->options) < 2) {
            $errors[] = 'Ordering questions must have at least 2 items';
        }

        if ($questionType === self::TYPE_SHORT_ANSWER && empty($this->answer_key)) {
            $errors[] = 'Short answer questions must have an answer key or rubric';
        }

        return $errors;
    }
}
