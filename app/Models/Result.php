<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    protected $fillable = [
        'student_id',
        'quiz_id',
        'score',
        'correct_answers',
        'total_items',
        'submitted_at',
        'status'
    ];
}