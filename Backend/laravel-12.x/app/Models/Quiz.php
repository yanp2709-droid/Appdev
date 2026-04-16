<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Quiz extends Model
{
    use HasFactory;

    public const DEFAULT_DURATION_MINUTES = 10;

    protected $fillable = [
        'title',
        'category_id',
        'teacher_id',
        'difficulty',
        'duration_minutes',
        'timer_enabled',
        'shuffle_questions',
        'shuffle_options',
        'max_attempts',
        'allow_review_before_submit',
        'show_score_immediately',
        'show_answers_after_submit',
        'show_correct_answers_after_submit',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'timer_enabled' => 'boolean',
        'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean',
        'max_attempts' => 'integer',
        'allow_review_before_submit' => 'boolean',
        'show_score_immediately' => 'boolean',
        'show_answers_after_submit' => 'boolean',
        'show_correct_answers_after_submit' => 'boolean',
    ];

    public static function normalizePayload(array $payload): array
    {
        if (array_key_exists('title', $payload)) {
            $payload['title'] = trim((string) $payload['title']);
        }

        if (array_key_exists('difficulty', $payload)) {
            $payload['difficulty'] = trim((string) $payload['difficulty']);
        }

        foreach ([
            'timer_enabled',
            'shuffle_questions',
            'shuffle_options',
            'allow_review_before_submit',
            'show_score_immediately',
            'show_answers_after_submit',
            'show_correct_answers_after_submit',
        ] as $booleanField) {
            if (array_key_exists($booleanField, $payload)) {
                $payload[$booleanField] = (bool) $payload[$booleanField];
            }
        }

        if (array_key_exists('attempt_limit', $payload) && !array_key_exists('max_attempts', $payload)) {
            $payload['max_attempts'] = $payload['attempt_limit'];
        }

        if (array_key_exists('max_attempts', $payload)) {
            if ($payload['max_attempts'] === '' || $payload['max_attempts'] === null) {
                $payload['max_attempts'] = null;
            } else {
                $payload['max_attempts'] = (int) $payload['max_attempts'];
            }
        }

        if (array_key_exists('duration_minutes', $payload)) {
            if ($payload['duration_minutes'] === '' || $payload['duration_minutes'] === null) {
                $payload['duration_minutes'] = null;
            } else {
                $payload['duration_minutes'] = (int) $payload['duration_minutes'];
            }
        }

        return $payload;
    }

    public static function validatePayload(array $payload): array
    {
        $errors = [];

        $title = trim((string) ($payload['title'] ?? ''));
        if ($title === '') {
            $errors[] = 'Quiz title is required.';
        }

        if (empty($payload['category_id'])) {
            $errors[] = 'Quiz category is required.';
        }

        $difficulty = $payload['difficulty'] ?? null;
        if (!in_array($difficulty, ['Easy', 'Medium', 'Hard'], true)) {
            $errors[] = 'Quiz difficulty must be Easy, Medium, or Hard.';
        }

        $timerEnabled = (bool) ($payload['timer_enabled'] ?? true);
        $durationMinutes = $payload['duration_minutes'] ?? null;

        if ($timerEnabled && ($durationMinutes === null || $durationMinutes === '')) {
            $errors[] = 'Duration is required when the timer is enabled.';
        }

        if ($durationMinutes !== null && $durationMinutes !== '' && (!is_numeric($durationMinutes) || (int) $durationMinutes <= 0)) {
            $errors[] = 'Duration must be a positive integer.';
        }

        $attemptLimit = $payload['max_attempts'] ?? $payload['attempt_limit'] ?? null;
        if ($attemptLimit !== null && $attemptLimit !== '' && (!is_numeric($attemptLimit) || (int) $attemptLimit <= 0)) {
            $errors[] = 'Attempt limit must be a positive integer when provided.';
        }

        if (!empty($payload['show_correct_answers_after_submit']) && empty($payload['show_answers_after_submit'])) {
            $errors[] = 'Correct answers can only be shown after submit when answer review is enabled.';
        }

        return $errors;
    }

    // Relations
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function attempts()
    {
        return $this->hasMany(Quiz_attempt::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
