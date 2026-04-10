<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

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
        'is_bookmarked',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'is_bookmarked' => 'boolean',
    ];

    /**
     * Get selected option IDs with fallback to question_option_id
     */
    protected function selectedOptionIds(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $decoded = null;
                
                // Handle the raw value from attributes
                if ($value !== null) {
                    if (is_array($value)) {
                        $decoded = $value;
                    } else {
                        $decoded = json_decode($value, true) ?: null;
                    }
                }

                if (is_array($decoded)) {
                    return array_values(array_map('intval', $decoded));
                }

                // Fallback to question_option_id
                if (!empty($this->attributes['question_option_id'])) {
                    return [(int) $this->attributes['question_option_id']];
                }

                return [];
            },
            set: function ($value) {
                if (is_array($value)) {
                    return json_encode($value);
                }
                return $value;
            }
        );
    }

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
}
