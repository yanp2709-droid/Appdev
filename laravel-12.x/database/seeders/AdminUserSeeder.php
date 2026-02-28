<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Teacher;
use App\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // Optional: create a default admin
        Teacher::create([
            'name' => 'Default Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
    }
}