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

        $this->assertContains('Multiple choice questions must have exactly 1 correct option.', $errors);
    }

    public function test_true_false_requires_exactly_two_options_and_one_correct_answer(): void
    {
        $question = new Question(['question_type' => Question::TYPE_TRUE_FALSE]);
        $question->setRelation('options', new Collection([
            new QuestionOption(['is_correct' => true]),
        ]));

        $errors = $question->getValidationErrors();

        $this->assertContains('True/False questions must have exactly 2 options.', $errors);
    }

    public function test_multi_select_requires_at_least_one_correct_option(): void
    {
        $question = new Question(['question_type' => Question::TYPE_MULTI_SELECT]);
        $question->setRelation('options', new Collection([
            new QuestionOption(['is_correct' => false]),
            new QuestionOption(['is_correct' => false]),
        ]));

        $errors = $question->getValidationErrors();

        $this->assertContains('Multi-select questions must have at least 1 correct option.', $errors);
    }

    public function test_short_answer_requires_answer_key(): void
    {
        $question = new Question([
            'question_type' => Question::TYPE_SHORT_ANSWER,
            'answer_key' => '',
        ]);
        $question->setRelation('options', new Collection());

        $errors = $question->getValidationErrors();

        $this->assertContains('Short answer questions must have an answer key or rubric.', $errors);
    }

    public function test_validate_payload_accepts_valid_true_false_question(): void
    {
        $payload = [
            'question_text' => 'The sky is blue.',
            'question_type' => 'tf',
            'options' => [
                ['option_text' => 'True', 'is_correct' => true],
                ['option_text' => 'False', 'is_correct' => false],
            ],
        ];

        $errors = Question::validatePayload($payload);

        $this->assertEmpty($errors);
    }

    public function test_validate_payload_rejects_true_false_with_three_options(): void
    {
        $payload = [
            'question_text' => 'The sky is blue.',
            'question_type' => 'true_false',
            'options' => [
                ['option_text' => 'True', 'is_correct' => true],
                ['option_text' => 'False', 'is_correct' => false],
                ['option_text' => 'Maybe', 'is_correct' => false],
            ],
        ];

        $errors = Question::validatePayload($payload);

        $this->assertContains('True/False questions must have exactly 2 options.', $errors);
    }

    public function test_validate_payload_rejects_true_false_with_two_correct_answers(): void
    {
        $payload = [
            'question_text' => 'The sky is blue.',
            'question_type' => 'tf',
            'options' => [
                ['option_text' => 'True', 'is_correct' => true],
                ['option_text' => 'False', 'is_correct' => true],
            ],
        ];

        $errors = Question::validatePayload($payload);

        $this->assertContains('True/False questions must have exactly 1 correct option.', $errors);
    }

    public function test_validate_payload_accepts_valid_multi_select_question(): void
    {
        $payload = [
            'question_text' => 'Select fruits.',
            'question_type' => 'multi_select',
            'options' => [
                ['option_text' => 'Apple', 'is_correct' => true],
                ['option_text' => 'Banana', 'is_correct' => true],
                ['option_text' => 'Car', 'is_correct' => false],
            ],
        ];

        $errors = Question::validatePayload($payload);

        $this->assertEmpty($errors);
    }

    public function test_validate_payload_rejects_multi_select_without_any_correct_option(): void
    {
        $payload = [
            'question_text' => 'Select fruits.',
            'question_type' => 'multi_select',
            'options' => [
                ['option_text' => 'Apple', 'is_correct' => false],
                ['option_text' => 'Banana', 'is_correct' => false],
            ],
        ];

        $errors = Question::validatePayload($payload);

        $this->assertContains('Multi-select questions must have at least 1 correct option.', $errors);
    }

    public function test_revalidate_when_changing_question_type(): void
    {
        $payload = [
            'question_text' => 'Select the right statements.',
            'question_type' => 'tf',
            'options' => [
                ['option_text' => 'True', 'is_correct' => true],
                ['option_text' => 'False', 'is_correct' => false],
            ],
        ];

        $this->assertEmpty(Question::validatePayload($payload));

        $payload['question_type'] = 'multi_select';
        $payload['options'][1]['is_correct'] = true;

        $this->assertEmpty(Question::validatePayload($payload));
    }

    public function test_preview_invalid_question_should_be_blocked(): void
    {
        $question = new Question([
            'question_type' => Question::TYPE_TRUE_FALSE,
            'question_text' => '',
        ]);
        $question->setRelation('options', new Collection([
            new QuestionOption(['option_text' => 'True', 'is_correct' => true]),
            new QuestionOption(['option_text' => 'False', 'is_correct' => false]),
        ]));

        $this->assertFalse($question->isPreviewReady());
    }
}
