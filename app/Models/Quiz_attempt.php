<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz_attempt extends Model
{
    protected $fillable = [
        'student_id',
        'quiz_id',
        'score',
    ];
}
