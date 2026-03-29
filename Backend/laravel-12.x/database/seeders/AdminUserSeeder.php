<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        User::firstOrCreate(
            ['email' => 'admin@techquiz.com'],
            [
                'name' => 'Default Admin',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'is_protected' => true,
            ]
        );
    }
}
