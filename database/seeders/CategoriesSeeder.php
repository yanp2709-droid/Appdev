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
            ],
            [
                'name' => 'Computer Hardware',
                'description' => 'Understand computer components and architecture',
                'is_published' => true,
            ],
            [
                'name' => 'Networking Basics',
                'description' => 'Introduction to computer networks',
                'is_published' => true,
            ],
            [
                'name' => 'General IT Knowledge',
                'description' => 'General information technology concepts',
                'is_published' => true,
            ],
        ];

        foreach ($categories as $cat) {
            Category::create($cat);
        }
    }
}