<?php

namespace Tests\Unit;

use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

class QuestionLogicTest extends TestCase
{
    public function test_it_normalizes_supported_question_types(): void
    {
        $this->assertSame(Question::TYPE_MCQ, Question::normalizeQuestionType('mcq'));
        $this->assertSame(Question::TYPE_MCQ, Question::normalizeQuestionType('multiple_choice'));
        $this->assertSame(Question::TYPE_TRUE_FALSE, Question::normalizeQuestionType('true_false'));
        $this->assertSame(Question::TYPE_MULTI_SELECT, Question::normalizeQuestionType('multi_select'));
        $this->assertSame(Question::TYPE_SHORT_ANSWER, Question::normalizeQuestionType('short_answer'));
        $this->assertNull(Question::normalizeQuestionType('essay'));
    }

    public function test_it_maps_internal_types_to_api_types(): void
    {
        $this->assertSame('multiple_choice', Question::toApiQuestionType('mcq'));
        $this->assertSame('true_false', Question::toApiQuestionType('tf'));
        $this->assertSame('multi_select', Question::toApiQuestionType('multi_select'));
        $this->assertSame('short_answer', Question::toApiQuestionType('short_answer'));
        $this->assertNull(Question::toApiQuestionType('essay'));
    }

    public function test_mcq_requires_exactly_one_correct_option(): void
    {
        $question = new Question(['question_type' => Question::TYPE_MCQ]);
        $question->setRelation('options', new Collection([
            new QuestionOption(['is_correct' => true]),
            new QuestionOption(['is_correct' => true]),
        ]));

        $errors = $question->getValidationErrors();

        $this->assertContains('Multiple choice questions must have exactly 1 correct option', $errors);
    }

    public function test_true_false_requires_exactly_two_options_and_one_correct_answer(): void
    {
        $question = new Question(['question_type' => Question::TYPE_TRUE_FALSE]);
        $question->setRelation('options', new Collection([
            new QuestionOption(['is_correct' => true]),
        ]));

        $errors = $question->getValidationErrors();

        $this->assertContains('True/False questions must have exactly 2 options', $errors);
    }

    public function test_multi_select_requires_at_least_one_correct_option(): void
    {
        $question = new Question(['question_type' => Question::TYPE_MULTI_SELECT]);
        $question->setRelation('options', new Collection([
            new QuestionOption(['is_correct' => false]),
            new QuestionOption(['is_correct' => false]),
        ]));

        $errors = $question->getValidationErrors();

        $this->assertContains('Multi-select questions must have at least 1 correct option', $errors);
    }

    public function test_short_answer_requires_answer_key(): void
    {
        $question = new Question([
            'question_type' => Question::TYPE_SHORT_ANSWER,
            'answer_key' => '',
        ]);
        $question->setRelation('options', new Collection());

        $errors = $question->getValidationErrors();

        $this->assertContains('Short answer questions must have an answer key or rubric', $errors);
    }
}
