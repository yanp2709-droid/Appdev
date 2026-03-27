<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Only allow admin role to access Filament admin panel
        return $this->role === 'admin';
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

    // Relations
    public function quizAttempts()
    {
        return $this->hasMany(Quiz_attempt::class, 'student_id');
    }
}
