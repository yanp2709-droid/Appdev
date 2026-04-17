<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Attempt_answer;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\Quiz_attempt;
use App\Models\User;
use Carbon\Carbon;
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

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
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
                             'last_activity_at',
                             'last_viewed_question_id',
                             'last_viewed_question_index',
                         ],
                         'quiz_settings' => [
                             'shuffle_questions',
                             'shuffle_options',
                             'max_attempts',
                             'timer_enabled',
                             'show_score_immediately',
                             'show_answers_after_submit',
                         ],
                         'questions' => [],
                         'saved_answers',
                         'progress',
                         'resumed',
                     ],
                 ])
                 ->assertJsonPath('data.attempt.status', 'in_progress')
                 ->assertJsonPath('data.attempt.quiz_id', $this->quiz->id)
                 ->assertJsonPath('data.resumed', false);

        $this->assertDatabaseHas('quiz_attempts', [
            'student_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'status' => 'in_progress',
        ]);
    }

    /**
     * Test student cannot have multiple active attempts for same quiz
     */
    public function test_student_resumes_existing_active_attempt_instead_of_creating_duplicate()
    {
        $response1 = $this->actingAs($this->student)->postJson('/api/quiz/attempt', [
            'quiz_id' => $this->quiz->id,
        ]);
        $this->assertTrue($response1->json('success'));
        $attemptId = $response1->json('data.attempt.id');

        $response2 = $this->actingAs($this->student)->postJson('/api/quiz/attempt', [
            'quiz_id' => $this->quiz->id,
        ]);

        $response2->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.resumed', true)
            ->assertJsonPath('data.attempt.id', $attemptId);

        $this->assertSame(1, Quiz_attempt::where('student_id', $this->student->id)
            ->where('quiz_id', $this->quiz->id)
            ->count());
    }

    /**
     * Test student can quit an active attempt and start fresh with a reset timer.
     */
    public function test_student_can_quit_attempt_and_restart_with_fresh_timer()
    {
        Carbon::setTestNow(now());

        $startResponse = $this->actingAs($this->student)->postJson('/api/quiz/attempt', [
            'quiz_id' => $this->quiz->id,
        ]);

        $startResponse->assertStatus(200);
        $firstAttemptId = $startResponse->json('data.attempt.id');

        Carbon::setTestNow(now()->addMinutes(5));

        $quitResponse = $this->actingAs($this->student)
            ->postJson("/api/quiz/attempts/{$firstAttemptId}/quit");

        $quitResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.attempt.id', $firstAttemptId)
            ->assertJsonPath('data.attempt.status', 'expired');

        $this->assertDatabaseHas('quiz_attempts', [
            'id' => $firstAttemptId,
            'status' => 'expired',
        ]);

        $restartResponse = $this->actingAs($this->student)->postJson('/api/quiz/attempt', [
            'quiz_id' => $this->quiz->id,
        ]);

        $restartResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.resumed', false);

        $secondAttemptId = $restartResponse->json('data.attempt.id');
        $this->assertNotSame($firstAttemptId, $secondAttemptId);
        $this->assertSame(15.0, $restartResponse->json('data.attempt.duration_minutes'));
        $this->assertGreaterThan(0, $restartResponse->json('data.attempt.remaining_seconds'));
    }

    /**
     * Test only the first submitted attempt in a category exposes a score.
     */
    public function test_only_first_submitted_attempt_in_category_is_scored()
    {
        $firstAttempt = Quiz_attempt::create([
            'student_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(20),
            'expires_at' => now()->addMinutes(10),
            'total_items' => 3,
            'score' => 0,
        ]);

        foreach (Question::where('category_id', $this->category->id)->get() as $question) {
            $correctOption = QuestionOption::where('question_id', $question->id)
                ->where('is_correct', true)
                ->first();

            Attempt_answer::create([
                'quiz_attempt_id' => $firstAttempt->id,
                'question_id' => $question->id,
                'question_option_id' => $correctOption?->id,
                'selected_option_ids' => $correctOption ? [$correctOption->id] : [],
                'is_correct' => true,
            ]);
        }

        $firstResponse = $this->actingAs($this->student)
            ->postJson("/api/quiz/attempts/{$firstAttempt->id}/submit");

        $firstResponse->assertStatus(200)
            ->assertJsonPath('data.is_scored_attempt', true)
            ->assertJsonPath('data.is_practice_attempt', false);

        $secondAttempt = Quiz_attempt::create([
            'student_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(5),
            'expires_at' => now()->addMinutes(10),
            'total_items' => 3,
            'score' => 0,
        ]);

        foreach (Question::where('category_id', $this->category->id)->get() as $question) {
            $correctOption = QuestionOption::where('question_id', $question->id)
                ->where('is_correct', true)
                ->first();

            Attempt_answer::create([
                'quiz_attempt_id' => $secondAttempt->id,
                'question_id' => $question->id,
                'question_option_id' => $correctOption?->id,
                'selected_option_ids' => $correctOption ? [$correctOption->id] : [],
                'is_correct' => true,
            ]);
        }

        $secondResponse = $this->actingAs($this->student)
            ->postJson("/api/quiz/attempts/{$secondAttempt->id}/submit");

        $secondResponse->assertStatus(200)
            ->assertJsonPath('data.is_scored_attempt', false)
            ->assertJsonPath('data.is_practice_attempt', true)
            ->assertJsonPath('data.score', null);

        $historyResponse = $this->actingAs($this->student)
            ->getJson('/api/quiz/attempts');

        $historyResponse->assertStatus(200);

        $attempts = collect($historyResponse->json('data.attempts'));
        $firstHistory = $attempts->firstWhere('id', $firstAttempt->id);
        $secondHistory = $attempts->firstWhere('id', $secondAttempt->id);

        $this->assertNotNull($firstHistory);
        $this->assertNotNull($secondHistory);
        $this->assertTrue($firstHistory['is_scored_attempt']);
        $this->assertFalse($firstHistory['is_practice_attempt']);
        $this->assertSame(100.0, $firstHistory['score_percent']);
        $this->assertFalse($secondHistory['is_scored_attempt']);
        $this->assertTrue($secondHistory['is_practice_attempt']);
        $this->assertNull($secondHistory['score_percent']);
        $this->assertNull($secondHistory['correct_answers']);
    }

    /**
     * Test student cannot start more attempts than configured by the quiz
     */
    public function test_student_cannot_start_more_attempts_than_configured()
    {
        $quiz = Quiz::factory()->create([
            'category_id' => $this->category->id,
            'teacher_id' => $this->teacher->id,
            'duration_minutes' => 15,
            'max_attempts' => 1,
        ]);

        Quiz_attempt::create([
            'student_id' => $this->student->id,
            'quiz_id' => $quiz->id,
            'status' => 'submitted',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'total_items' => 3,
            'score' => 0,
        ]);

        $response = $this->actingAs($this->student)->postJson('/api/quiz/attempt', [
            'quiz_id' => $quiz->id,
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('error.code', 'attempt_limit_reached');

        $this->assertSame(1, Quiz_attempt::where('student_id', $this->student->id)
            ->where('quiz_id', $quiz->id)
            ->count());
    }

    /**
     * Test quiz submission hides score when immediate score visibility is turned off.
     */
    public function test_submit_response_hides_score_when_immediate_score_is_disabled()
    {
        $quiz = Quiz::factory()->create([
            'category_id' => $this->category->id,
            'teacher_id' => $this->teacher->id,
            'duration_minutes' => 15,
            'show_score_immediately' => false,
        ]);

        $attempt = Quiz_attempt::create([
            'student_id' => $this->student->id,
            'quiz_id' => $quiz->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'total_items' => 1,
            'score' => 0,
        ]);

        $response = $this->actingAs($this->student)->postJson("/api/quiz/attempts/{$attempt->id}/submit");

        $response->assertStatus(200)
            ->assertJsonPath('data.attempt.status', 'submitted')
            ->assertJsonPath('data.score', null);
    }

    /**
     * Test attempt detail does not expose answers before submission when the quiz requires submit-first review
     */
    public function test_attempt_detail_hides_answers_before_submission_when_quiz_requires_submit_first_review()
    {
        $quiz = Quiz::factory()->create([
            'category_id' => $this->category->id,
            'teacher_id' => $this->teacher->id,
            'duration_minutes' => 15,
            'show_answers_after_submit' => true,
            'show_score_immediately' => false,
        ]);

        $question = Question::factory()->create([
            'category_id' => $this->category->id,
            'question_type' => 'mcq',
            'points' => 5,
        ]);

        $option = QuestionOption::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $attempt = Quiz_attempt::create([
            'student_id' => $this->student->id,
            'quiz_id' => $quiz->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'total_items' => 1,
            'score' => 0,
        ]);

        Attempt_answer::create([
            'quiz_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'question_option_id' => $option->id,
            'selected_option_ids' => [$option->id],
            'is_correct' => false,
        ]);

        $response = $this->actingAs($this->student)->getJson("/api/quiz/attempts/{$attempt->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.attempt.correct_answers', null)
            ->assertJsonPath('data.attempt.score_percent', null)
            ->assertJsonPath('data.questions.0.correct_option_id', null)
            ->assertJsonPath('data.questions.0.correct_option_ids', []);
    }

    /**
     * Test submitted attempt detail does not expose review when answer review is disabled.
     */
    public function test_submitted_attempt_detail_returns_no_questions_when_review_is_disabled()
    {
        $quiz = Quiz::factory()->create([
            'category_id' => $this->category->id,
            'teacher_id' => $this->teacher->id,
            'duration_minutes' => 15,
            'show_answers_after_submit' => false,
            'show_correct_answers_after_submit' => false,
        ]);

        $question = Question::factory()->create([
            'category_id' => $this->category->id,
            'question_type' => 'mcq',
            'points' => 5,
        ]);

        $option = QuestionOption::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $attempt = Quiz_attempt::create([
            'student_id' => $this->student->id,
            'quiz_id' => $quiz->id,
            'status' => 'submitted',
            'started_at' => now()->subMinutes(15),
            'expires_at' => now(),
            'submitted_at' => now(),
            'total_items' => 1,
            'answered_count' => 1,
            'correct_answers' => 1,
            'score_percent' => 100,
            'score' => 1,
        ]);

        Attempt_answer::create([
            'quiz_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'question_option_id' => $option->id,
            'selected_option_ids' => [$option->id],
            'is_correct' => true,
        ]);

        $response = $this->actingAs($this->student)->getJson("/api/quiz/attempts/{$attempt->id}/detail");

        $response->assertStatus(200)
            ->assertJsonPath('data.attempt.can_review_answers', false)
            ->assertJsonPath('data.attempt.show_correct_answers', false)
            ->assertJsonCount(0, 'data.questions');
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

    public function test_autosave_persists_progress_and_resume_state()
    {
        Carbon::setTestNow('2026-04-03 10:00:00');

        $startResponse = $this->actingAs($this->student)->postJson('/api/quiz/attempt', [
            'quiz_id' => $this->quiz->id,
            'random' => true,
            'limit' => 2,
        ]);

        $attemptId = $startResponse->json('data.attempt.id');
        $questions = $startResponse->json('data.questions');
        $firstQuestion = $questions[0];
        $secondQuestion = $questions[1];
        $selectedOptionId = $firstQuestion['options'][1]['id'];

        Carbon::setTestNow('2026-04-03 10:02:30');

        $this->actingAs($this->student)->postJson("/api/quiz/attempts/{$attemptId}/answer", [
            'question_id' => $firstQuestion['id'],
            'option_id' => $selectedOptionId,
            'is_bookmarked' => true,
            'last_viewed_question_id' => $secondQuestion['id'],
            'last_viewed_question_index' => 1,
        ])->assertStatus(200);

        $resumeResponse = $this->actingAs($this->student)->postJson('/api/quiz/attempt', [
            'quiz_id' => $this->quiz->id,
        ]);

        $resumeResponse->assertStatus(200)
            ->assertJsonPath('data.resumed', true)
            ->assertJsonPath('data.questions.0.id', $firstQuestion['id'])
            ->assertJsonPath('data.questions.1.id', $secondQuestion['id'])
            ->assertJsonPath("data.saved_answers.{$firstQuestion['id']}.selected_option_id", $selectedOptionId)
            ->assertJsonPath("data.saved_answers.{$firstQuestion['id']}.is_bookmarked", true)
            ->assertJsonPath('data.progress.last_viewed_question_id', $secondQuestion['id'])
            ->assertJsonPath('data.progress.last_viewed_question_index', 1)
            ->assertJsonPath('data.progress.answered_count', 1)
            ->assertJsonPath('data.attempt.remaining_seconds', 750);

        $this->assertDatabaseHas('quiz_attempts', [
            'id' => $attemptId,
            'last_viewed_question_id' => $secondQuestion['id'],
            'last_viewed_question_index' => 1,
            'answered_count' => 1,
        ]);

        $this->assertDatabaseHas('attempt_answers', [
            'quiz_attempt_id' => $attemptId,
            'question_id' => $firstQuestion['id'],
            'question_option_id' => $selectedOptionId,
            'is_bookmarked' => true,
        ]);
    }

    public function test_bookmark_only_autosave_does_not_overwrite_previous_answer()
    {
        $attempt = Quiz_attempt::create([
            'student_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'total_items' => 3,
            'score' => 0,
            'question_sequence' => Question::where('category_id', $this->category->id)->pluck('id')->all(),
        ]);

        $question = Question::where('category_id', $this->category->id)
            ->with('options')
            ->first();
        $option = $question->options->first();

        $this->actingAs($this->student)->postJson("/api/quiz/attempts/{$attempt->id}/answer", [
            'question_id' => $question->id,
            'option_id' => $option->id,
        ])->assertStatus(200);

        $this->actingAs($this->student)->postJson("/api/quiz/attempts/{$attempt->id}/answer", [
            'question_id' => $question->id,
            'is_bookmarked' => true,
        ])->assertStatus(200);

        $answer = Attempt_answer::where('quiz_attempt_id', $attempt->id)
            ->where('question_id', $question->id)
            ->first();

        $this->assertNotNull($answer);
        $this->assertSame([$option->id], $answer->selected_option_ids);
        $this->assertTrue($answer->is_bookmarked);
    }

    public function test_expired_attempt_cannot_resume_and_new_attempt_is_created()
    {
        Quiz_attempt::create([
            'student_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(20),
            'expires_at' => now()->subMinute(),
            'total_items' => 3,
            'score' => 0,
        ]);

        $response = $this->actingAs($this->student)->postJson('/api/quiz/attempt', [
            'quiz_id' => $this->quiz->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.resumed', false)
            ->assertJsonPath('data.attempt.status', 'in_progress');

        $this->assertSame(1, Quiz_attempt::where('student_id', $this->student->id)
            ->where('quiz_id', $this->quiz->id)
            ->where('status', 'in_progress')
            ->count());

        $this->assertSame(1, Quiz_attempt::where('student_id', $this->student->id)
            ->where('quiz_id', $this->quiz->id)
            ->where('status', 'expired')
            ->count());
    }

    public function test_status_returns_saved_answers_and_progress_for_resume_recovery()
    {
        $question = Question::where('category_id', $this->category->id)
            ->with('options')
            ->first();
        $option = $question->options->first();

        $attempt = Quiz_attempt::create([
            'student_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(15),
            'total_items' => 3,
            'score' => 0,
            'answered_count' => 1,
            'last_activity_at' => now(),
            'last_viewed_question_id' => $question->id,
            'last_viewed_question_index' => 0,
        ]);

        Attempt_answer::create([
            'quiz_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'question_option_id' => $option->id,
            'selected_option_ids' => [$option->id],
            'is_bookmarked' => true,
        ]);

        $this->actingAs($this->student)
            ->getJson("/api/quiz/attempts/{$attempt->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.saved_answers.' . $question->id . '.selected_option_id', $option->id)
            ->assertJsonPath('data.saved_answers.' . $question->id . '.is_bookmarked', true)
            ->assertJsonPath('data.progress.last_viewed_question_id', $question->id)
            ->assertJsonPath('data.progress.last_viewed_question_index', 0)
            ->assertJsonPath('data.progress.answered_count', 1);
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
     * Test attempt by category auto-creates a default quiz when questions exist.
     */
    public function test_attempt_by_category_creates_default_quiz_when_missing()
    {
        $category = Category::factory()->create([
            'time_limit_minutes' => 12,
        ]);

        $question = Question::factory()->create([
            'category_id' => $category->id,
            'question_type' => 'mcq',
            'points' => 5,
        ]);

        QuestionOption::factory()->count(4)->create([
            'question_id' => $question->id,
            'is_correct' => false,
        ]);

        QuestionOption::where('question_id', $question->id)
            ->first()
            ->update(['is_correct' => true]);

        $this->assertDatabaseMissing('quizzes', [
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($this->student)->postJson('/api/quiz/attempt', [
            'category_id' => $category->id,
        ]);

        $quizId = Quiz::where('category_id', $category->id)->value('id');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.attempt.status', 'in_progress')
            ->assertJsonPath('data.attempt.duration_minutes', 12.0)
            ->assertJsonPath('data.attempt.quiz_id', $quizId);

        $this->assertDatabaseHas('quizzes', [
            'category_id' => $category->id,
            'title' => $category->name . ' Quiz',
            'difficulty' => 'Easy',
            'duration_minutes' => 12,
        ]);
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
