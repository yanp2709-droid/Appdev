<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        return $this->hasMany(Question::class);
    }

    // A quiz has many attempts
    public function attempts()
    {
        return $this->hasMany(Quiz_attempt::class);
    }
}
