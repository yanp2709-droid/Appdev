<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attempt_answer extends Model
{
    protected $fillable = [
        'quiz_attempt_id',
        'question_id',
        'answer_id',
        'question_option_id',
        'selected_option_ids',
        'text_answer',
        'is_correct',
    ];

    protected $casts = [
        'selected_option_ids' => 'array',
        'is_correct' => 'boolean',
    ];

    // Relations
    public function quizAttempt()
    {
        return $this->belongsTo(Quiz_attempt::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function answer()
    {
        return $this->belongsTo(Answer::class);
    }

    public function questionOption()
    {
        return $this->belongsTo(QuestionOption::class, 'question_option_id');
    }

    public function getSelectedOptionIdsAttribute($value): array
    {
        if ($value !== null) {
            $decoded = is_array($value) ? $value : json_decode($value, true);

            if (is_array($decoded)) {
                return array_values(array_map('intval', $decoded));
            }
        }

        if (!empty($this->attributes['question_option_id'])) {
            return [(int) $this->attributes['question_option_id']];
        }

        return [];
    }
}
