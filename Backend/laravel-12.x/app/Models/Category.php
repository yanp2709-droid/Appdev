<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_published',
        'time_limit_minutes',
    ];

    // A category has many questions
    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}
