<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attempt_answer extends Model
{
    protected $fillable = [
        'quiz_attempt_id',
        'question_id',
        'answer_id',
        'text_answer',
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
}
