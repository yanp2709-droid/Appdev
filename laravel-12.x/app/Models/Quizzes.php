<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quizzes extends Model
{
    protected $fillable = [
        'title',
        'category_id',
        'teacher_id',
        'difficulty',
        
    ];
}
