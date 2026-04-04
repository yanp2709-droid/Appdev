<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_fetch_preview_payload_for_valid_question(): void
    {
        $teacher = User::factory()->teacher()->create();
        $category = Category::factory()->create();

        $question = Question::factory()->create([
            'category_id' => $category->id,
            'question_type' => 'multi_select',
            'question_text' => 'Pick fruits',
        ]);

        QuestionOption::factory()->create([
            'question_id' => $question->id,
            'option_text' => 'Apple',
            'is_correct' => true,
            'order_index' => 0,
        ]);

        QuestionOption::factory()->create([
            'question_id' => $question->id,
            'option_text' => 'Banana',
            'is_correct' => true,
            'order_index' => 1,
        ]);

        QuestionOption::factory()->create([
            'question_id' => $question->id,
            'option_text' => 'Desk',
            'is_correct' => false,
            'order_index' => 2,
        ]);

        $response = $this->actingAs($teacher, 'sanctum')
            ->getJson("/api/admin/questions/{$question->id}/preview?include_correct_answers=1");

        $response->assertStatus(200)
            ->assertJsonPath('data.question.type', 'multi_select')
            ->assertJsonPath('data.question.options.0.is_correct', true);
    }

    public function test_preview_blocks_invalid_question_payload(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $question = Question::factory()->create([
            'category_id' => $category->id,
            'question_type' => 'tf',
            'question_text' => 'Broken true false',
        ]);

        QuestionOption::factory()->create([
            'question_id' => $question->id,
            'option_text' => 'True',
            'is_correct' => true,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/questions/{$question->id}/preview");

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');
    }
}
