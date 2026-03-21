<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Schema;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'category_id',
        'difficulty',
        'duration_minutes',
    ];

    // A quiz belongs to a category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // A quiz has many questions
    public function questions()
    {
        if (Schema::hasColumn('questions', 'quiz_id')) {
            return $this->hasMany(Question::class, 'quiz_id');
        }

        return $this->hasMany(Question::class, 'category_id', 'category_id');
    }

    // A quiz has many attempts
    public function attempts()
    {
        return $this->hasMany(Quiz_attempt::class);
    }
}
