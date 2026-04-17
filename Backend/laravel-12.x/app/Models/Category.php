<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Category extends Model
{
    use HasFactory;

    public const QUIZ_SETTING_FIELDS = [
        'difficulty',
        'timer_enabled',
        'shuffle_questions',
        'shuffle_options',
        'max_attempts',
        'allow_review_before_submit',
        'show_score_immediately',
        'show_answers_after_submit',
        'show_correct_answers_after_submit',
    ];

    protected $fillable = [
        'name',
        'description',
        'is_published',
        'time_limit_minutes',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'time_limit_minutes' => 'integer',
    ];

    // A category has many questions
    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function quiz()
    {
        return $this->hasOne(Quiz::class);
    }

    public function getQuizFormState(): array
    {
        $quiz = $this->quiz;

        return [
            'time_limit_minutes' => (int) ($this->time_limit_minutes ?? $quiz?->duration_minutes ?? Quiz::DEFAULT_DURATION_MINUTES),
            'difficulty' => $quiz?->difficulty ?? 'Easy',
            'timer_enabled' => (bool) ($quiz?->timer_enabled ?? true),
            'shuffle_questions' => (bool) ($quiz?->shuffle_questions ?? false),
            'shuffle_options' => (bool) ($quiz?->shuffle_options ?? false),
            'max_attempts' => $quiz?->max_attempts,
            'allow_review_before_submit' => (bool) ($quiz?->allow_review_before_submit ?? false),
            'show_score_immediately' => (bool) ($quiz?->show_score_immediately ?? true),
            'show_answers_after_submit' => (bool) ($quiz?->show_answers_after_submit ?? false),
            'show_correct_answers_after_submit' => (bool) ($quiz?->show_correct_answers_after_submit ?? false),
        ];
    }

    public function syncQuizConfiguration(array $settings): Quiz
    {
        $quiz = $this->quiz()->first();
        $defaultTitle = trim($this->name) !== '' ? trim($this->name) . ' Quiz' : 'Category Quiz';
        $durationMinutes = (int) ($this->time_limit_minutes ?? Quiz::DEFAULT_DURATION_MINUTES);

        $payload = Quiz::normalizePayload(array_merge([
            'title' => $quiz?->title ?: $defaultTitle,
            'category_id' => $this->id,
            'teacher_id' => $quiz?->teacher_id,
            'difficulty' => $quiz?->difficulty ?? 'Easy',
            'duration_minutes' => $durationMinutes,
            'timer_enabled' => $quiz?->timer_enabled ?? true,
            'shuffle_questions' => $quiz?->shuffle_questions ?? false,
            'shuffle_options' => $quiz?->shuffle_options ?? false,
            'max_attempts' => $quiz?->max_attempts,
            'allow_review_before_submit' => $quiz?->allow_review_before_submit ?? false,
            'show_score_immediately' => $quiz?->show_score_immediately ?? true,
            'show_answers_after_submit' => $quiz?->show_answers_after_submit ?? false,
            'show_correct_answers_after_submit' => $quiz?->show_correct_answers_after_submit ?? false,
        ], $settings, [
            'title' => $quiz?->title ?: $defaultTitle,
            'category_id' => $this->id,
            'duration_minutes' => $durationMinutes,
        ]));

        $errors = Quiz::validatePayload($payload);
        if (!empty($errors)) {
            throw ValidationException::withMessages([
                'quiz' => $errors,
            ]);
        }

        if ($quiz) {
            $quiz->update($payload);
            $quiz->refresh();
        } else {
            $quiz = Quiz::create($payload);
        }

        $this->setRelation('quiz', $quiz);

        return $quiz;
    }
}
