<?php

namespace Tests\Unit;

use App\Models\Question;
use App\Services\Validation\QuestionImportValidator;
use App\Services\Validation\QuizAttemptValidator;
use PHPUnit\Framework\TestCase;

class ValidationServiceLogicTest extends TestCase
{
    public function test_quiz_attempt_validator_requires_text_for_short_answer(): void
    {
        $validator = new QuizAttemptValidator();

        $errors = $validator->validateAnswer(Question::TYPE_SHORT_ANSWER, null, '   ');

        $this->assertSame(['Text answer is required for short answer questions.'], $errors);
    }

    public function test_quiz_attempt_validator_requires_option_for_choice_based_questions(): void
    {
        $validator = new QuizAttemptValidator();

        $errors = $validator->validateAnswer('true_false', null, null);

        $this->assertSame(['Option selection is required for choice-based questions.'], $errors);
    }

    public function test_question_import_validator_accepts_supported_question_types(): void
    {
        $validator = new QuestionImportValidator();

        $this->assertSame([], $validator->validateQuestionType('multi_select'));
        $this->assertSame([], $validator->validateQuestionType('true_false'));
    }

    public function test_question_import_validator_rejects_unknown_question_types(): void
    {
        $validator = new QuestionImportValidator();

        $errors = $validator->validateQuestionType('essay');

        $this->assertSame('question_type', $errors['field']);
    }

    public function test_question_import_validator_enforces_option_rules_by_type(): void
    {
        $validator = new QuestionImportValidator();

        $trueFalseErrors = $validator->validateOptions('true_false', ['True']);
        $multiSelectErrors = $validator->validateOptions('multi_select', ['One']);
        $shortAnswerErrors = $validator->validateOptions('short_answer', []);

        $this->assertSame('True/False questions must have exactly 2 options.', $trueFalseErrors[0]['message']);
        $this->assertSame('Questions must have at least 2 options.', $multiSelectErrors[0]['message']);
        $this->assertSame([], $shortAnswerErrors);
    }

    public function test_question_import_validator_validates_points_and_answer_key(): void
    {
        $validator = new QuestionImportValidator();

        $pointErrors = $validator->validatePoints(0);
        $answerKeyErrors = $validator->validateAnswerKey('short_answer', '   ');

        $this->assertSame('Points must be a positive integer.', $pointErrors[0]['message']);
        $this->assertSame('Answer key is required for short answer questions.', $answerKeyErrors[0]['message']);
    }

    public function test_question_import_validator_validates_field_lengths(): void
    {
        $validator = new QuestionImportValidator();

        $errors = $validator->validateFieldLengths([
            'question_text' => str_repeat('a', 1001),
            'answer_key' => str_repeat('b', 501),
        ]);

        $this->assertCount(2, $errors);
        $this->assertSame('question_text', $errors[0]['field']);
        $this->assertSame('answer_key', $errors[1]['field']);
    }
}
