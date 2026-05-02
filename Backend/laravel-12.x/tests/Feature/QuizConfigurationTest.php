<?php

namespace Tests\Feature;

use App\Models\Attempt_answer;
use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\Quiz_attempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_quiz_with_advanced_configuration(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/quiz/create', [
            'title' => 'Configured Quiz',
            'category_id' => $category->id,
            'teacher_id' => $teacher->id,
            'difficulty' => 'Medium',
            'timer_enabled' => true,
            'duration_minutes' => 20,
            'shuffle_questions' => true,
            'shuffle_options' => true,
            'attempt_limit' => 2,
            'allow_review_before_submit' => true,
            'show_score_immediately' => false,
            'show_answers_after_submit' => true,
            'show_correct_answers_after_submit' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.quiz.attempt_limit', 2)
            ->assertJsonPath('data.quiz.allow_review_before_submit', true)
            ->assertJsonPath('data.quiz.show_correct_answers_after_submit', true);

        $this->assertDatabaseHas('quizzes', [
            'title' => 'Configured Quiz',
            'teacher_id' => $teacher->id,
            'max_attempts' => 2,
            'allow_review_before_submit' => true,
            'show_score_immediately' => false,
            'show_answers_after_submit' => true,
            'show_correct_answers_after_submit' => true,
        ]);
    }

    public function test_quiz_creation_rejects_timer_without_duration(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/quiz/create', [
            'title' => 'Broken Quiz',
            'category_id' => $category->id,
            'difficulty' => 'Easy',
            'timer_enabled' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');
    }

    public function test_attempt_detail_respects_review_and_answer_visibility_flags(): void
    {
        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();
        $category = Category::factory()->create();

        $quiz = Quiz::factory()->create([
            'category_id' => $category->id,
            'teacher_id' => $teacher->id,
            'allow_review_before_submit' => true,
            'show_answers_after_submit' => true,
            'show_correct_answers_after_submit' => false,
            'show_score_immediately' => false,
        ]);

        $question = Question::factory()->create([
            'category_id' => $category->id,
            'question_type' => 'mcq',
            'points' => 5,
        ]);

        $correctOption = QuestionOption::factory()->create([
            'question_id' => $question->id,
            'option_text' => 'Correct',
            'is_correct' => true,
            'order_index' => 0,
        ]);

        $selectedOption = QuestionOption::factory()->create([
            'question_id' => $question->id,
            'option_text' => 'Selected',
            'is_correct' => false,
            'order_index' => 1,
        ]);

        $attempt = Quiz_attempt::create([
            'student_id' => $student->id,
            'quiz_id' => $quiz->id,
            'status' => 'submitted',
            'started_at' => now(),
            'submitted_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'total_items' => 1,
            'answered_count' => 1,
            'correct_answers' => 0,
            'score_percent' => 0,
            'score' => 0,
        ]);

        Attempt_answer::create([
            'quiz_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'question_option_id' => $selectedOption->id,
            'selected_option_ids' => [$selectedOption->id],
            'is_correct' => false,
        ]);

        $response = $this->actingAs($student, 'sanctum')
            ->getJson("/api/quiz/attempts/{$attempt->id}/detail");

        $response->assertStatus(200)
            ->assertJsonPath('data.attempt.score_percent', null)
            ->assertJsonPath('data.attempt.correct_answers', null)
            ->assertJsonPath('data.questions.0.correct_option_id', null)
            ->assertJsonPath('data.questions.0.correct_option_ids', []);

        $quiz->update([
            'show_correct_answers_after_submit' => true,
            'show_score_immediately' => true,
        ]);

        $response = $this->actingAs($student, 'sanctum')
            ->getJson("/api/quiz/attempts/{$attempt->id}/detail");

        $response->assertStatus(200)
            ->assertJsonPath('data.attempt.score_percent', 0.0)
            ->assertJsonPath('data.attempt.correct_answers', 0)
            ->assertJsonPath('data.questions.0.correct_option_id', $correctOption->id);
    }

    public function test_public_can_view_quiz_details_without_authentication(): void
    {
        $teacher = User::factory()->teacher()->create();
        $category = Category::factory()->create();
        $quiz = Quiz::factory()->create([
            'category_id' => $category->id,
            'teacher_id' => $teacher->id,
            'difficulty' => 'Easy',
            'duration_minutes' => 10,
            'timer_enabled' => true,
            'shuffle_questions' => false,
            'shuffle_options' => false,
        ]);

        $response = $this->getJson("/api/quiz/{$quiz->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.quiz.id', $quiz->id)
            ->assertJsonPath('data.quiz.category_id', $category->id)
            ->assertJsonPath('data.quiz.title', $quiz->title);
    }
}
