<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'student_id',
        'section',
        'year_level',
        'course',
        'privacy_consent',
        'is_protected',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'privacy_consent' => 'boolean',
            'is_protected' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (self $user): void {
            if ($user->isProtected()) {
                throw ValidationException::withMessages([
                    'user' => 'Protected users cannot be deleted.',
                ]);
            }

            if ($user->isAdmin() && static::query()
                ->where('role', 'admin')
                ->whereKeyNot($user->getKey())
                ->doesntExist()) {
                throw ValidationException::withMessages([
                    'user' => 'The last admin account cannot be deleted.',
                ]);
            }
        });
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->isTeacher() && $this->is_active;
    }

    // Helper functions (optional but useful)

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isStudent()
    {
        return $this->role === 'student';
    }

    public function isTeacher()
    {
        return $this->role === 'teacher';
    }

    public function isProtected(): bool
    {
        return (bool) $this->is_protected;
    }

    // Relations
    public function quizAttempts()
    {
        return $this->hasMany(Quiz_attempt::class, 'student_id');
    }

    public function quizRetakeAllowances()
    {
        return $this->hasMany(QuizRetakeAllowance::class, 'student_id');
    }
}
