<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategoriesSeeder extends Seeder
{
    public function run()
    {
        $categories = [
            [
                'name' => 'Programming Basics',
                'description' => 'Learn fundamental programming concepts',
                'is_published' => true,
                'time_limit_minutes' => 15,
            ],
            [
                'name' => 'Computer Hardware',
                'description' => 'Understand computer components and architecture',
                'is_published' => true,
                'time_limit_minutes' => 15,
            ],
            [
                'name' => 'Networking Basics',
                'description' => 'Introduction to computer networks',
                'is_published' => true,
                'time_limit_minutes' => 15,
            ],
            [
                'name' => 'General IT Knowledge',
                'description' => 'General information technology concepts',
                'is_published' => true,
                'time_limit_minutes' => 15,
            ],
            [
                'name' => 'Cybersecurity Basics',
                'description' => 'Foundational security concepts and best practices',
                'is_published' => true,
                'time_limit_minutes' => 15,
            ],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['name' => $cat['name']],
                $cat
            );
        }
    }
}
