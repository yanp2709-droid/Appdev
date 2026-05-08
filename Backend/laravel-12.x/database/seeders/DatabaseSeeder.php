<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            AcademicYearDataSeeder::class,
            CategoriesSeeder::class,
            QuestionsSeeder::class,
            StudentId2302Seeder::class,
            DummyStudentsPerSchoolYearSeeder::class,
        ]);
    }
}
