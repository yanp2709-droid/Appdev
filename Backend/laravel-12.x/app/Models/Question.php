<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'question_type',
        'question_text',
        'points',
        'answer_key',
    ];

    protected $casts = [
        'points' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Relation to options
    public function options()
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order_index');
    }

    public function getValidationErrors()
    {
        $errors = [];

        if ($this->question_type === 'mcq' && count($this->options) < 2) {
            $errors[] = 'MCQ questions must have at least 2 options';
        }

        if ($this->question_type === 'mcq') {
            $correctCount = $this->options->where('is_correct', true)->count();
            if ($correctCount === 0) {
                $errors[] = 'MCQ questions must have at least 1 correct option';
            }
        }

        if ($this->question_type === 'tf') {
            if (count($this->options) !== 2) {
                $errors[] = 'True/False questions must have exactly 2 options';
            }
            if ($this->options->where('is_correct', true)->count() !== 1) {
                $errors[] = 'True/False questions must have exactly 1 correct option';
            }
        }

        if ($this->question_type === 'ordering' && count($this->options) < 2) {
            $errors[] = 'Ordering questions must have at least 2 items';
        }

        if ($this->question_type === 'short_answer' && empty($this->answer_key)) {
            $errors[] = 'Short answer questions must have an answer key or rubric';
        }

        return $errors;
    }
}