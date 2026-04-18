<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizRetakeAllowance extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'quiz_id',
        'additional_graded_attempts',
        'updated_by',
    ];

    protected $casts = [
        'additional_graded_attempts' => 'integer',
        'updated_by' => 'integer',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
