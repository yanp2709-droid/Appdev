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

class QuizScoringTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private Category $category;
    private Quiz $quiz;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->student()->create();
        $this->category = Category::factory()->create();
        $this->quiz = Quiz::factory()->create([
            'category_id' => $this->category->id,
            'duration_minutes' => 15,
        ]);
    }

    /**
     * Test student can submit attempt
     */
    public function test_student_can_submit_attempt()
    {
        $attempt = $this->createAttemptWithAnswers(3, 2);

        $response = $this->actingAs($this->student)
            ->postJson("/api/quiz/attempts/{$attempt->id}/submit");

        $response->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.attempt.status', 'submitted')
                 ->assertJsonStructure([
                     'data' => [
                         'attempt' => ['submitted_at'],
                         'score' => [
                             'total_items',
                             'answered_count',
                             'correct_answers',
                             'score_percent',
                         ],
                     ],
                 ]);

        $this->assertDatabaseHas('quiz_attempts', [
            'id' => $attempt->id,
            'status' => 'submitted',
        ]);
    }

    /**
     * Test scoring correctly counts answers
     */
    public function test_scoring_counts_correct_answers()
    {
        $question1 = Question::factory()->create([
            'category_id' => $this->category->id,
            'question_type' => 'mcq',
            'points' => 5,
        ]);

        $question2 = Question::factory()->create([
            'category_id' => $this->category->id,
            'question_type' => 'mcq',
            'points' => 5,
        ]);

        $question3 = Question::factory()->create([
            'category_id' => $this->category->id,
            'question_type' => 'short_answer',
            'answer_key' => 'correct',
            'points' => 5,
        ]);

        // Create options
        $option1_correct = QuestionOption::factory()->create([
            'question_id' => $question1->id,
            'is_correct' => true,
        ]);
        QuestionOption::factory()->create([
            'question_id' => $question1->id,
            'is_correct' => false,
        ]);

        $option2_wrong = QuestionOption::factory()->create([
            'question_id' => $question2->id,
            'is_correct' => false,
        ]);
        QuestionOption::factory()->create([
            'question_id' => $question2->id,
            'is_correct' => true,
        ]);

        $attempt = Quiz_attempt::create([
            'student_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'total_items' => 3,
            'score' => 0,
        ]);

        // Save answers
        Attempt_answer::create([
            'quiz_attempt_id' => $attempt->id,
            'question_id' => $question1->id,
            'question_option_id' => $option1_correct->id,
        ]);

        Attempt_answer::create([
            'quiz_attempt_id' => $attempt->id,
            'question_id' => $question2->id,
            'question_option_id' => $option2_wrong->id,
        ]);

        Attempt_answer::create([
            'quiz_attempt_id' => $attempt->id,
            'question_id' => $question3->id,
            'text_answer' => 'CORRECT',
        ]);

        $response = $this->actingAs($this->student)
            ->postJson("/api/quiz/attempts/{$attempt->id}/submit");

        $response->assertStatus(200)
                 ->assertJsonPath('data.score.total_items', 3)
                 ->assertJsonPath('data.score.answered_count', 3)
                 ->assertJsonPath('data.score.correct_answers', 2);
    }

    /**
     * Test text answer is case insensitive
     */
    public function test_text_answer_case_insensitive()
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

        Attempt_answer::create([
            'quiz_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'text_answer' => 'paris',
        ]);

        $response = $this->actingAs($this->student)
            ->postJson("/api/quiz/attempts/{$attempt->id}/submit");

        $response->assertStatus(200)
                 ->assertJsonPath('data.score.correct_answers', 1);
    }

    /**
     * Test cannot submit already submitted attempt
     */
    public function test_cannot_submit_already_submitted_attempt()
    {
        $attempt = Quiz_attempt::create([
            'student_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'status' => 'submitted',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'submitted_at' => now(),
            'total_items' => 1,
            'score' => 50,
        ]);

        $response = $this->actingAs($this->student)
            ->postJson("/api/quiz/attempts/{$attempt->id}/submit");

        $response->assertStatus(409)
                 ->assertJsonPath('error.code', 'attempt_submitted');
    }

    /**
     * Test cannot submit expired attempt
     */
    public function test_cannot_submit_expired_attempt()
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

        $response = $this->actingAs($this->student)
            ->postJson("/api/quiz/attempts/{$attempt->id}/submit");

        $response->assertStatus(410)
                 ->assertJsonPath('error.code', 'attempt_expired');
    }

    /**
     * Test scoring handles unanswered questions
     */
    public function test_scoring_handles_unanswered_questions()
    {
        $question1 = Question::factory()->create([
            'category_id' => $this->category->id,
            'question_type' => 'mcq',
        ]);

        $question2 = Question::factory()->create([
            'category_id' => $this->category->id,
            'question_type' => 'mcq',
        ]);

        QuestionOption::factory()->create([
            'question_id' => $question1->id,
            'is_correct' => true,
        ]);

        QuestionOption::factory()->create([
            'question_id' => $question2->id,
            'is_correct' => true,
        ]);

        $attempt = Quiz_attempt::create([
            'student_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'total_items' => 2,
            'score' => 0,
        ]);

        // Only answer first question
        $option = $question1->options()->where('is_correct', true)->first();
        Attempt_answer::create([
            'quiz_attempt_id' => $attempt->id,
            'question_id' => $question1->id,
            'question_option_id' => $option->id,
        ]);

        $response = $this->actingAs($this->student)
            ->postJson("/api/quiz/attempts/{$attempt->id}/submit");

        $response->assertStatus(200)
                 ->assertJsonPath('data.score.total_items', 2)
                 ->assertJsonPath('data.score.answered_count', 1)
                 ->assertJsonPath('data.score.correct_answers', 1);
    }

    /**
     * Test score percent calculation
     */
    public function test_score_percent_calculation()
    {
        // Create 10 questions
        $questions = Question::factory()->count(10)->create([
            'category_id' => $this->category->id,
            'question_type' => 'mcq',
        ]);

        // Create options
        $options = [];
        foreach ($questions as $question) {
            $options[$question->id] = QuestionOption::factory()->create([
                'question_id' => $question->id,
                'is_correct' => true,
            ]);
        }

        $attempt = Quiz_attempt::create([
            'student_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'total_items' => 10,
            'score' => 0,
        ]);

        // Answer 7 questions correctly
        foreach ($questions->take(7) as $question) {
            Attempt_answer::create([
                'quiz_attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'question_option_id' => $options[$question->id]->id,
            ]);
        }

        $response = $this->actingAs($this->student)
            ->postJson("/api/quiz/attempts/{$attempt->id}/submit");

        // 7 out of 10 = 70%
        $response->assertStatus(200)
                 ->assertJsonPath('data.score.score_percent', 70.0);
    }

    /**
     * Test attempt status auto-expires on submit
     */
    public function test_expired_attempt_status_updated_on_status_check()
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

        $this->actingAs($this->student)
            ->getJson("/api/quiz/attempts/{$attempt->id}");

        $this->assertDatabaseHas('quiz_attempts', [
            'id' => $attempt->id,
            'status' => 'expired',
        ]);
    }

    /**
     * Helper method to create attempt with answers
     */
    private function createAttemptWithAnswers(int $questionCount, int $correctCount): Quiz_attempt
    {
        $questions = Question::factory()
            ->count($questionCount)
            ->create([
                'category_id' => $this->category->id,
                'question_type' => 'mcq',
            ]);

        $options = [];
        foreach ($questions as $question) {
            // Create one correct and one wrong option
            $options[$question->id] = [
                'correct' => QuestionOption::factory()->create([
                    'question_id' => $question->id,
                    'is_correct' => true,
                ]),
                'wrong' => QuestionOption::factory()->create([
                    'question_id' => $question->id,
                    'is_correct' => false,
                ]),
            ];
        }

        $attempt = Quiz_attempt::create([
            'student_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'total_items' => $questionCount,
            'score' => 0,
        ]);

        // Answer first $correctCount questions correctly
        for ($i = 0; $i < $correctCount; $i++) {
            Attempt_answer::create([
                'quiz_attempt_id' => $attempt->id,
                'question_id' => $questions[$i]->id,
                'question_option_id' => $options[$questions[$i]->id]['correct']->id,
            ]);
        }

        // Answer remaining questions incorrectly
        for ($i = $correctCount; $i < $questionCount; $i++) {
            Attempt_answer::create([
                'quiz_attempt_id' => $attempt->id,
                'question_id' => $questions[$i]->id,
                'question_option_id' => $options[$questions[$i]->id]['wrong']->id,
            ]);
        }

        return $attempt;
    }
}
