<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Teacher;
use App\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        $adminRole = Role::where('name', 'teacher')->first();

        Teacher::firstOrCreate(
            ['email' => 'admin@techquiz.com'], // check first
            [
                'name' => 'Default Admin',
                'password' => bcrypt('password'),
                'role_id' => $adminRole->id,
            ]
        );
    }
}