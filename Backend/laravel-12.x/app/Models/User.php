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
        'academic_year',
        'student_id',
        'section',
        'year_level',
        'course',
        'school_year',
        'privacy_consent',
        'is_protected',
        'is_active',
        // Teacher profile fields
        'profile_picture',
        'position',
        'subjects_teaching',
        'it_specialization',
        'educational_background',
        'skills_technologies',
        'certifications',
        'years_experience',
        'professional_summary',
        'contact_info',
        'office_schedule',
        'department',
        'programming_languages',
        'frameworks_tools',
        'database_experience',
        'software_expertise',
        'research_interests',
        'current_projects',
        'achievements',
        'portfolio_links',
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
            // Teacher profile fields
            'subjects_teaching' => 'array',
            'skills_technologies' => 'array',
            'certifications' => 'array',
            'programming_languages' => 'array',
            'frameworks_tools' => 'array',
            'database_experience' => 'array',
            'software_expertise' => 'array',
            'achievements' => 'array',
            'portfolio_links' => 'array',
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

            if ($user->isStudent() && $user->quizAttempts()->exists()) {
                throw ValidationException::withMessages([
                    'user' => 'This student cannot be deleted because they already have quiz records.',
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
