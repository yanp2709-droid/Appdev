<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttemptAnswer extends Model
{
    protected $fillable = [
        'quiz_attempt_id',   // foreign key to attempts
        'question_id',
        'answer_id',         // if you store a reference answer
        'question_option_id', // for selected option
        'text_answer',       // for short answer
        'is_correct',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    // Relations
    public function attempt()
    {
        return $this->belongsTo(Attempt::class, 'quiz_attempt_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function option()
    {
        return $this->belongsTo(QuestionOption::class, 'question_option_id');
    }

    public function answer()
    {
        return $this->belongsTo(Answer::class);
    }
}