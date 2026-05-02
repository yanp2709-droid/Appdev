<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Legacy compatibility seeder.
 *
 * The older question bank has been retired in favor of QuizDatasetSeeder,
 * which seeds realistic subject > quiz > question data at scale.
 */
class QuestionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(QuizDatasetSeeder::class);
    }
}
