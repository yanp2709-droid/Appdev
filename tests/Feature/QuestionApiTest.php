<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test questions can be fetched by category.
     */
    public function test_questions_can_be_fetched_by_category()
    {
        $user = User::factory()->create();

        $category = Category::factory()->create();

        Question::factory()->count(5)->create([
            'category_id' => $category->id
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/questions?category_id={$category->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data'
                 ]);
    }
}
