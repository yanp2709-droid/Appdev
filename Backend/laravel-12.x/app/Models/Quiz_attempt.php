<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Quiz_attempt extends Model
{
    use HasFactory;
    protected $fillable = [
        'student_id',
        'quiz_id',
        'score',
        'status',
        'started_at',
        'expires_at',
        'submitted_at',
        'total_items',
        'answered_count',
        'correct_answers',
        'score_percent',
        'question_sequence',
        'last_activity_at',
        'last_viewed_question_id',
        'last_viewed_question_index',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'submitted_at' => 'datetime',
        'score_percent' => 'float',
        'question_sequence' => 'array',
        'last_activity_at' => 'datetime',
        'last_viewed_question_index' => 'integer',
    ];

    /**
     * Ensure score_percent is always returned as float
     */
    protected function scorePercent(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn($value) => is_numeric($value) ? (float)$value : 0.0,
        );
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function answers()
    {
        return $this->hasMany(Attempt_answer::class);
    }

    /**
     * Check if attempt has expired
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return now()->greaterThan($this->expires_at);
    }

    /**
     * Check if attempt is active (not submitted and not expired)
     */
    public function isActive(): bool
    {
        if ($this->status === 'submitted') {
            return false;
        }

        return !$this->isExpired();
    }

    /**
     * Get remaining time in seconds
     */
    public function getRemainingSeconds(): int
    {
        if (!$this->expires_at) {
            return 0;
        }

        $seconds = now()->diffInSeconds($this->expires_at, false);
        return max($seconds, 0);
    }

    /**
     * Get attempt duration in minutes
     */
    public function getDurationMinutes(): ?int
    {
        if ($this->started_at && $this->expires_at) {
            return $this->started_at->diffInMinutes($this->expires_at);
        }

        return null;
    }

    /**
     * Mark attempt as expired
     */
    public function markExpired(): self
    {
        if ($this->status !== 'expired' && $this->isExpired()) {
            $this->status = 'expired';
            $this->save();
        }

        return $this;
    }
}
