<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategoriesSeeder extends Seeder
{
    public function run()
    {
        $categories = [
            'Programming Basics',
            'Computer Hardware',
            'Networking Basics',
            'General IT Knowledge'
        ];

        foreach ($categories as $cat) {
            Category::create(['name' => $cat]);
        }
    }
}