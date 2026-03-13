<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz_attempt extends Model
{
    protected $fillable = [
        'student_id',
        'quiz_id',
        'score',
        'status',
        'started_at',
        'expires_at',
        'submitted_at',
        'total_items',
        'answered_count',
        'correct_answers',
        'score_percent',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function answers()
    {
        return $this->hasMany(Attempt_answer::class);
    }
}
