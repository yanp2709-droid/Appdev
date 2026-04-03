<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\Quiz_attempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizAttemptTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private User $teacher;
    private Category $category;
    private Quiz $quiz;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->student()->create();
        $this->teacher = User::factory()->teacher()->create();
        $this->category = Category::factory()->create(['time_limit_minutes' => 10]);
        $this->quiz = Quiz::factory()->create([
            'category_id' => $this->category->id,
            'duration_minutes' => 15,
        ]);

        // Create sample questions with options
        for ($i = 0; $i < 3; $i++) {
            $question = Question::factory()->create([
                'category_id' => $this->category->id,
                'question_type' => 'mcq',
                'points' => 5,
            ]);

            QuestionOption::factory()->count(4)->create([
                'question_id' => $question->id,
                'is_correct' => false,
            ]);

            // Set one option as correct
            QuestionOption::where('question_id', $question->id)
                ->first()
                ->update(['is_correct' => true]);
        }
    }

    /**
     * Test student can start a quiz attempt
     */
    public function test_student_can_start_quiz_attempt()
    {
        $response = $this->actingAs($this->student)->postJson('/api/quiz/attempt', [
            'quiz_id' => $this->quiz->id,
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'attempt' => [
                             'id',
                             'quiz_id',
                             'status',
                             'started_at',
                             'expires_at',
                             'duration_minutes',
                             'remaining_seconds',
                         ],
                         'questions' => [],
                     ],
                 ])
                 ->assertJsonPath('data.attempt.status', 'in_progress')
                 ->assertJsonPath('data.attempt.quiz_id', $this->quiz->id);

        $this->assertDatabaseHas('quiz_attempts', [
            'student_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'status' => 'in_progress',
        ]);
    }

    /**
     * Test student cannot have multiple active attempts for same quiz
     */
    public function test_student_cannot_have_multiple_active_attempts()
    {
        // Start first attempt
        $response1 = $this->actingAs($this->student)->postJson('/api/quiz/attempt', [
            'quiz_id' => $this->quiz->id,
        ]);
        $this->assertTrue($response1->json('success'));

        // Try to start second attempt
        $response2 = $this->actingAs($this->student)->postJson('/api/quiz/attempt', [
            'quiz_id' => $this->quiz->id,
        ]);

        $response2->assertStatus(409)
                  ->assertJsonPath('error.code', 'active_attempt_exists')
                  ->assertJsonPath('success', false);
    }

    /**
     * Test unauthorized user cannot start attempt
     */
    public function test_unauthorized_user_cannot_start_attempt()
    {
        $response = $this->postJson('/api/quiz/attempt', [
            'quiz_id' => $this->quiz->id,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test non-student role cannot start attempt
     */
    public function test_non_student_role_cannot_start_attempt()
    {
        $response = $this->actingAs($this->teacher)->postJson('/api/quiz/attempt', [
            'quiz_id' => $this->quiz->id,
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test attempt not found returns 404
     */
    public function test_attempt_not_found_returns_404()
    {
        $response = $this->actingAs($this->student)
            ->postJson('/api/quiz/attempts/99999/answer', [
                'question_id' => 1,
                'option_id' => 1,
            ]);

        $response->assertStatus(404)
                 ->assertJsonPath('error.code', 'not_found');
    }

    /**
     * Test student can save answer to question
     */
    public function test_student_can_save_answer_to_mcq()
    {
        $attempt = Quiz_attempt::create([
            'student_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'total_items' => 3,
            'score' => 0,
        ]);

        $question = Question::where('category_id', $this->category->id)
            ->with('options')
            ->first();
        $option = $question->options->first();

        $response = $this->actingAs($this->student)->postJson(
            "/api/quiz/attempts/{$attempt->id}/answer",
            [
                'question_id' => $question->id,
                'option_id' => $option->id,
            ]
        );

        $response->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('message', 'Answer saved.');

        $this->assertDatabaseHas('attempt_answers', [
            'quiz_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'question_option_id' => $option->id,
        ]);
    }

    /**
     * Test student can save text answer
     */
    public function test_student_can_save_text_answer()
    {
        $question = Question::factory()->create([
            'category_id' => $this->category->id,
            'question_type' => 'short_answer',
            'answer_key' => 'Paris',
        ]);

        $attempt = Quiz_attempt::create([
            'student_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'total_items' => 1,
            'score' => 0,
        ]);

        $response = $this->actingAs($this->student)->postJson(
            "/api/quiz/attempts/{$attempt->id}/answer",
            [
                'question_id' => $question->id,
                'text_answer' => 'Paris',
            ]
        );

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);

        $this->assertDatabaseHas('attempt_answers', [
            'quiz_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'text_answer' => 'Paris',
        ]);
    }

    public function test_student_can_save_true_false_answer_using_boolean_payload()
    {
        $question = Question::factory()->create([
            'category_id' => $this->category->id,
            'question_type' => 'tf',
        ]);

        $trueOption = QuestionOption::factory()->create([
            'question_id' => $question->id,
            'option_text' => 'True',
            'is_correct' => true,
        ]);

        QuestionOption::factory()->create([
            'question_id' => $question->id,
            'option_text' => 'False',
            'is_correct' => false,
        ]);

        $attempt = Quiz_attempt::create([
            'student_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'total_items' => 1,
            'score' => 0,
        ]);

        $response = $this->actingAs($this->student)->postJson(
            "/api/quiz/attempts/{$attempt->id}/answer",
            [
                'question_id' => $question->id,
                'answer' => true,
            ]
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.question_type', 'true_false')
            ->assertJsonPath('data.selected_option_id', $trueOption->id)
            ->assertJsonPath('data.selected_option_ids.0', $trueOption->id);

        $this->assertDatabaseHas('attempt_answers', [
            'quiz_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'question_option_id' => $trueOption->id,
        ]);
    }

    public function test_true_false_answer_rejects_multiple_selections()
    {
        $question = Question::factory()->create([
            'category_id' => $this->category->id,
            'question_type' => 'tf',
        ]);

        $options = QuestionOption::factory()->count(2)->create([
            'question_id' => $question->id,
            'is_correct' => false,
        ]);

        $attempt = Quiz_attempt::create([
            'student_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'total_items' => 1,
            'score' => 0,
        ]);

        $response = $this->actingAs($this->student)->postJson(
            "/api/quiz/attempts/{$attempt->id}/answer",
            [
                'question_id' => $question->id,
                'answer' => $options->pluck('id')->all(),
            ]
        );

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');
    }

    public function test_student_can_save_multi_select_answer()
    {
        $question = Question::factory()->create([
            'category_id' => $this->category->id,
            'question_type' => 'multi_select',
        ]);

        $selectedOptions = collect([
            QuestionOption::factory()->create([
                'question_id' => $question->id,
                'is_correct' => true,
            ]),
            QuestionOption::factory()->create([
                'question_id' => $question->id,
                'is_correct' => true,
            ]),
        ]);

        QuestionOption::factory()->create([
            'question_id' => $question->id,
            'is_correct' => false,
        ]);

        $attempt = Quiz_attempt::create([
            'student_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'total_items' => 1,
            'score' => 0,
        ]);

        $response = $this->actingAs($this->student)->postJson(
            "/api/quiz/attempts/{$attempt->id}/answer",
            [
                'question_id' => $question->id,
                'answer' => $selectedOptions->pluck('id')->all(),
            ]
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.question_type', 'multi_select')
            ->assertJsonPath('data.selected_option_id', null)
            ->assertJsonPath('data.selected_option_ids', $selectedOptions->pluck('id')->sort()->values()->all());

        $this->assertDatabaseHas('attempt_answers', [
            'quiz_attempt_id' => $attempt->id,
            'question_id' => $question->id,
        ]);
    }

    public function test_multi_select_answer_rejects_duplicate_option_ids()
    {
        $question = Question::factory()->create([
            'category_id' => $this->category->id,
            'question_type' => 'multi_select',
        ]);

        $option = QuestionOption::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        QuestionOption::factory()->create([
            'question_id' => $question->id,
            'is_correct' => false,
        ]);

        $attempt = Quiz_attempt::create([
            'student_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'total_items' => 1,
            'score' => 0,
        ]);

        $response = $this->actingAs($this->student)->postJson(
            "/api/quiz/attempts/{$attempt->id}/answer",
            [
                'question_id' => $question->id,
                'option_ids' => [$option->id, $option->id],
            ]
        );

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');
    }

    public function test_changing_existing_answer_replaces_previous_selection()
    {
        $question = Question::factory()->create([
            'category_id' => $this->category->id,
            'question_type' => 'multi_select',
        ]);

        $firstSelection = QuestionOption::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);
        $replacementSelection = QuestionOption::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $attempt = Quiz_attempt::create([
            'student_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'total_items' => 1,
            'score' => 0,
        ]);

        $this->actingAs($this->student)->postJson(
            "/api/quiz/attempts/{$attempt->id}/answer",
            [
                'question_id' => $question->id,
                'option_ids' => [$firstSelection->id],
            ]
        )->assertStatus(200);

        $this->actingAs($this->student)->postJson(
            "/api/quiz/attempts/{$attempt->id}/answer",
            [
                'question_id' => $question->id,
                'option_ids' => [$replacementSelection->id],
            ]
        )->assertStatus(200);

        $answer = \App\Models\Attempt_answer::where('quiz_attempt_id', $attempt->id)
            ->where('question_id', $question->id)
            ->first();

        $this->assertNotNull($answer);
        $this->assertSame([$replacementSelection->id], $answer->selected_option_ids);
    }

    /**
     * Test answer requires either option_id or text_answer
     */
    public function test_answer_requires_option_or_text()
    {
        $attempt = Quiz_attempt::create([
            'student_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'total_items' => 1,
            'score' => 0,
        ]);

        $question = Question::where('category_id', $this->category->id)->first();

        $response = $this->actingAs($this->student)->postJson(
            "/api/quiz/attempts/{$attempt->id}/answer",
            [
                'question_id' => $question->id,
            ]
        );

        $response->assertStatus(422)
                 ->assertJsonPath('error.code', 'validation_error');
    }

    /**
     * Test cannot save answer to expired attempt
     */
    public function test_cannot_answer_expired_attempt()
    {
        $attempt = Quiz_attempt::create([
            'student_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(20),
            'expires_at' => now()->subMinute(),
            'total_items' => 1,
            'score' => 0,
        ]);

        $question = Question::where('category_id', $this->category->id)
            ->with('options')
            ->first();
        $option = $question->options->first();

        $response = $this->actingAs($this->student)->postJson(
            "/api/quiz/attempts/{$attempt->id}/answer",
            [
                'question_id' => $question->id,
                'option_id' => $option->id,
            ]
        );

        $response->assertStatus(410)
                 ->assertJsonPath('error.code', 'attempt_expired');
    }

    /**
     * Test cannot answer already submitted attempt
     */
    public function test_cannot_answer_submitted_attempt()
    {
        $attempt = Quiz_attempt::create([
            'student_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'status' => 'submitted',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'submitted_at' => now(),
            'total_items' => 1,
            'score' => 0,
        ]);

        $question = Question::where('category_id', $this->category->id)
            ->with('options')
            ->first();
        $option = $question->options->first();

        $response = $this->actingAs($this->student)->postJson(
            "/api/quiz/attempts/{$attempt->id}/answer",
            [
                'question_id' => $question->id,
                'option_id' => $option->id,
            ]
        );

        $response->assertStatus(409)
                 ->assertJsonPath('error.code', 'attempt_submitted');
    }

    /**
     * Test cannot start attempt without quiz_id or category_id
     */
    public function test_attempt_requires_quiz_or_category()
    {
        $response = $this->actingAs($this->student)->postJson('/api/quiz/attempt', []);

        $response->assertStatus(422)
                 ->assertJsonPath('error.code', 'validation_error');
    }

    /**
     * Test attempt with invalid quiz returns 404
     */
    public function test_attempt_with_invalid_quiz()
    {
        $response = $this->actingAs($this->student)->postJson('/api/quiz/attempt', [
            'quiz_id' => 99999,
        ]);

        $response->assertStatus(404)
                 ->assertJsonPath('error.code', 'quiz_not_found');
    }

    /**
     * Test get attempt status
     */
    public function test_get_attempt_status()
    {
        $attempt = Quiz_attempt::create([
            'student_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'total_items' => 3,
            'score' => 0,
        ]);

        $response = $this->actingAs($this->student)
            ->getJson("/api/quiz/attempts/{$attempt->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonStructure([
                     'data' => [
                         'attempt' => [
                             'id',
                             'status',
                             'remaining_seconds',
                         ],
                         'answered_count',
                         'total_items',
                     ],
                 ]);
    }

    /**
     * Test cannot access another student's attempt
     */
    public function test_cannot_access_other_students_attempt()
    {
        $otherStudent = User::factory()->student()->create();
        $attempt = Quiz_attempt::create([
            'student_id' => $otherStudent->id,
            'quiz_id' => $this->quiz->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'total_items' => 1,
            'score' => 0,
        ]);

        $response = $this->actingAs($this->student)
            ->getJson("/api/quiz/attempts/{$attempt->id}");

        $response->assertStatus(404);
    }
}
