<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Quiz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryQuizSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_can_create_backing_quiz_configuration(): void
    {
        $category = Category::factory()->create([
            'name' => 'Programming Basics',
            'time_limit_minutes' => 20,
        ]);

        $quiz = $category->syncQuizConfiguration([
            'difficulty' => 'Medium',
            'timer_enabled' => true,
            'shuffle_questions' => true,
            'shuffle_options' => true,
            'max_attempts' => 3,
            'allow_review_before_submit' => true,
            'show_score_immediately' => false,
            'show_answers_after_submit' => true,
            'show_correct_answers_after_submit' => true,
        ]);

        $this->assertInstanceOf(Quiz::class, $quiz);
        $this->assertDatabaseHas('quizzes', [
            'id' => $quiz->id,
            'category_id' => $category->id,
            'title' => 'Programming Basics Quiz',
            'difficulty' => 'Medium',
            'duration_minutes' => 20,
            'timer_enabled' => true,
            'shuffle_questions' => true,
            'shuffle_options' => true,
            'max_attempts' => 3,
            'allow_review_before_submit' => true,
            'show_score_immediately' => false,
            'show_answers_after_submit' => true,
            'show_correct_answers_after_submit' => true,
        ]);
    }

    public function test_category_sync_updates_existing_backing_quiz(): void
    {
        $category = Category::factory()->create([
            'name' => 'OOP',
            'time_limit_minutes' => 15,
        ]);

        $quiz = Quiz::factory()->create([
            'category_id' => $category->id,
            'title' => 'OOP Quiz',
            'difficulty' => 'Easy',
            'duration_minutes' => 15,
            'timer_enabled' => true,
            'shuffle_questions' => false,
            'shuffle_options' => false,
            'max_attempts' => null,
            'allow_review_before_submit' => false,
            'show_score_immediately' => true,
            'show_answers_after_submit' => false,
            'show_correct_answers_after_submit' => false,
        ]);

        $category->update([
            'time_limit_minutes' => 25,
        ]);

        $updatedQuiz = $category->syncQuizConfiguration([
            'difficulty' => 'Hard',
            'timer_enabled' => false,
            'shuffle_questions' => true,
            'shuffle_options' => true,
            'max_attempts' => 2,
            'allow_review_before_submit' => true,
            'show_score_immediately' => false,
            'show_answers_after_submit' => true,
            'show_correct_answers_after_submit' => true,
        ]);

        $this->assertSame($quiz->id, $updatedQuiz->id);
        $this->assertDatabaseHas('quizzes', [
            'id' => $quiz->id,
            'category_id' => $category->id,
            'title' => 'OOP Quiz',
            'difficulty' => 'Hard',
            'duration_minutes' => 25,
            'timer_enabled' => false,
            'shuffle_questions' => true,
            'shuffle_options' => true,
            'max_attempts' => 2,
            'allow_review_before_submit' => true,
            'show_score_immediately' => false,
            'show_answers_after_submit' => true,
            'show_correct_answers_after_submit' => true,
        ]);
    }
}
