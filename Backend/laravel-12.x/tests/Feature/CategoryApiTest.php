<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test student can fetch categories.
     */
    public function test_student_can_fetch_categories()
    {
        $user = User::factory()->create();

        Category::factory()->count(3)->create([
            'is_published' => true
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/categories');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'name',
                             'description'
                         ]
                     ]
                 ]);
    }
}
