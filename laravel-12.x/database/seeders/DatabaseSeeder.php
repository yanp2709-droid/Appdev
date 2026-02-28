<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([

            // 1️⃣ Roles first (no dependencies)
            RolesSeeder::class,

            // 2️⃣ Admin user (depends on roles)
            AdminUserSeeder::class,

            // 3️⃣ Categories (independent)
            CategoriesSeeder::class,

            // 4️⃣ Questions (creates quizzes + questions + options)
            QuestionsSeeder::class,

        ]);
    }
}