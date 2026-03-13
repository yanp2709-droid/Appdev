<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class StudentUserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'email' => 'student@test.com',
            'password' => bcrypt('password'),
            'role' => 'student',
        ]);
    }
}