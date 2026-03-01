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
        $role = Role::firstWhere('name', 'Admin');
        if (! $role) {
            $role = Role::create(['name' => 'Admin']);
        }

        Teacher::create([
            'name' => 'Default Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);
    }
}