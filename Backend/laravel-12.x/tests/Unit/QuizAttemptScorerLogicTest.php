<?php

namespace Tests\Unit;

use App\Models\Attempt_answer;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Services\Scoring\QuizAttemptScorer;
use Illuminate\Database\Eloquent\Collection;
use ReflectionClass;
use Tests\TestCase;

class QuizAttemptScorerLogicTest extends TestCase
{
    public function test_it_scores_single_choice_answers_by_correct_option(): void
    {
        $question = new Question(['question_type' => Question::TYPE_MCQ]);
        $question->setRelation('options', new Collection([
            new QuestionOption(['id' => 1, 'is_correct' => false]),
            new QuestionOption(['id' => 2, 'is_correct' => true]),
        ]));

        $answer = new Attempt_answer([
            'question_option_id' => 2,
        ]);

        $this->assertTrue($this->invokeScoreAnswer($question, $answer));
    }

    public function test_it_scores_multi_select_answers_as_correct_only_for_exact_match(): void
    {
        $question = new Question(['question_type' => Question::TYPE_MULTI_SELECT]);
        $question->setRelation('options', new Collection([
            new QuestionOption(['id' => 10, 'is_correct' => true]),
            new QuestionOption(['id' => 20, 'is_correct' => true]),
            new QuestionOption(['id' => 30, 'is_correct' => false]),
        ]));

        $exactMatch = new Attempt_answer([
            'selected_option_ids' => [20, 10],
        ]);

        $partialMatch = new Attempt_answer([
            'selected_option_ids' => [10],
        ]);

        $wrongExtra = new Attempt_answer([
            'selected_option_ids' => [10, 20, 30],
        ]);

        $this->assertTrue($this->invokeScoreAnswer($question, $exactMatch));
        $this->assertFalse($this->invokeScoreAnswer($question, $partialMatch));
        $this->assertFalse($this->invokeScoreAnswer($question, $wrongExtra));
    }

    public function test_it_scores_short_answers_case_insensitively(): void
    {
        $question = new Question([
            'question_type' => Question::TYPE_SHORT_ANSWER,
            'answer_key' => 'Paris',
        ]);
        $question->setRelation('options', new Collection());

        $answer = new Attempt_answer([
            'text_answer' => 'paris',
        ]);

        $this->assertTrue($this->invokeScoreAnswer($question, $answer));
    }

    private function invokeScoreAnswer(Question $question, Attempt_answer $answer): ?bool
    {
        $scorer = new QuizAttemptScorer();
        $reflection = new ReflectionClass($scorer);
        $method = $reflection->getMethod('scoreAnswer');
        $method->setAccessible(true);

        return $method->invoke($scorer, $question, $answer);
    }
}
