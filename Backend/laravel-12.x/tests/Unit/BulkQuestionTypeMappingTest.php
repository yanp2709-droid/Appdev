<?php

namespace Tests\Unit;

use App\Models\Question;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BulkQuestionTypeMappingTest extends TestCase
{
    #[DataProvider('normalizeQuestionTypeCases')]
    public function test_normalize_question_type_bulk(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, Question::normalizeQuestionType($input));
    }

    #[DataProvider('apiQuestionTypeCases')]
    public function test_api_question_type_mapping_bulk(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, Question::toApiQuestionType($input));
    }

    public static function normalizeQuestionTypeCases(): array
    {
        return [
            'n01' => ['mcq', Question::TYPE_MCQ],
            'n02' => ['MCQ', Question::TYPE_MCQ],
            'n03' => [' mcq ', Question::TYPE_MCQ],
            'n04' => ['multiple_choice', Question::TYPE_MCQ],
            'n05' => ['MULTIPLE_CHOICE', Question::TYPE_MCQ],
            'n06' => [' multiple_choice ', Question::TYPE_MCQ],
            'n07' => ['tf', Question::TYPE_TRUE_FALSE],
            'n08' => ['TF', Question::TYPE_TRUE_FALSE],
            'n09' => [' tf ', Question::TYPE_TRUE_FALSE],
            'n10' => ['true_false', Question::TYPE_TRUE_FALSE],
            'n11' => ['TRUE_FALSE', Question::TYPE_TRUE_FALSE],
            'n12' => [' true_false ', Question::TYPE_TRUE_FALSE],
            'n13' => ['multi_select', Question::TYPE_MULTI_SELECT],
            'n14' => ['MULTI_SELECT', Question::TYPE_MULTI_SELECT],
            'n15' => [' multi_select ', Question::TYPE_MULTI_SELECT],
            'n16' => ['short_answer', Question::TYPE_SHORT_ANSWER],
            'n17' => ['SHORT_ANSWER', Question::TYPE_SHORT_ANSWER],
            'n18' => [' short_answer ', Question::TYPE_SHORT_ANSWER],
            'n19' => ['essay', null],
            'n20' => ['TRUEFALSE', null],
            'n21' => ['', null],
            'n22' => [' ', null],
            'n23' => [null, null],
            'n24' => ['multi select', null],
            'n25' => ['short answer', null],
            'n26' => ['mcq ', Question::TYPE_MCQ],
            'n27' => ["\tmcq\t", Question::TYPE_MCQ],
            'n28' => ["\nmcq\n", Question::TYPE_MCQ],
            'n29' => ['multiple_choice ', Question::TYPE_MCQ],
            'n30' => [' tf', Question::TYPE_TRUE_FALSE],
            'n31' => ['true_false ', Question::TYPE_TRUE_FALSE],
            'n32' => [' multi_select', Question::TYPE_MULTI_SELECT],
            'n33' => ['short_answer ', Question::TYPE_SHORT_ANSWER],
            'n34' => ['mCQ', Question::TYPE_MCQ],
            'n35' => ['tF', Question::TYPE_TRUE_FALSE],
            'n36' => ['TrUe_FaLsE', Question::TYPE_TRUE_FALSE],
            'n37' => ['MuLtI_SeLeCt', Question::TYPE_MULTI_SELECT],
            'n38' => ['ShOrT_AnSwEr', Question::TYPE_SHORT_ANSWER],
            'n39' => ['multiple-choice', null],
            'n40' => ['true/false', null],
            'n41' => ['multi-select', null],
            'n42' => ['short-answer', null],
            'n43' => ['0', null],
            'n44' => ['1', null],
            'n45' => ['null', null],
            'n46' => ['undefined', null],
            'n47' => ['mcqx', null],
            'n48' => ['tfx', null],
            'n49' => ['multi_selectx', null],
            'n50' => ['short_answerx', null],
        ];
    }

    public static function apiQuestionTypeCases(): array
    {
        return [
            'a01' => ['mcq', 'multiple_choice'],
            'a02' => ['MCQ', 'multiple_choice'],
            'a03' => [' mcq ', 'multiple_choice'],
            'a04' => ['multiple_choice', 'multiple_choice'],
            'a05' => ['MULTIPLE_CHOICE', 'multiple_choice'],
            'a06' => [' tf ', 'true_false'],
            'a07' => ['tf', 'true_false'],
            'a08' => ['TF', 'true_false'],
            'a09' => ['true_false', 'true_false'],
            'a10' => ['TRUE_FALSE', 'true_false'],
            'a11' => [' multi_select ', 'multi_select'],
            'a12' => ['multi_select', 'multi_select'],
            'a13' => ['MULTI_SELECT', 'multi_select'],
            'a14' => [' short_answer ', 'short_answer'],
            'a15' => ['short_answer', 'short_answer'],
            'a16' => ['SHORT_ANSWER', 'short_answer'],
            'a17' => [null, null],
            'a18' => ['', null],
            'a19' => [' ', null],
            'a20' => ['essay', null],
            'a21' => ['multi select', null],
            'a22' => ['short answer', null],
            'a23' => ['TRUEFALSE', null],
            'a24' => ['multiple-choice', null],
            'a25' => ['true/false', null],
            'a26' => ['multi-select', null],
            'a27' => ['short-answer', null],
            'a28' => ['mcq ', 'multiple_choice'],
            'a29' => ["\tmcq\t", 'multiple_choice'],
            'a30' => ["\nmcq\n", 'multiple_choice'],
            'a31' => [' tf', 'true_false'],
            'a32' => ['true_false ', 'true_false'],
            'a33' => [' multi_select', 'multi_select'],
            'a34' => ['short_answer ', 'short_answer'],
            'a35' => ['mCQ', 'multiple_choice'],
            'a36' => ['tF', 'true_false'],
            'a37' => ['TrUe_FaLsE', 'true_false'],
            'a38' => ['MuLtI_SeLeCt', 'multi_select'],
            'a39' => ['ShOrT_AnSwEr', 'short_answer'],
            'a40' => ['0', null],
            'a41' => ['1', null],
            'a42' => ['null', null],
            'a43' => ['undefined', null],
            'a44' => ['mcqx', null],
            'a45' => ['tfx', null],
            'a46' => ['multi_selectx', null],
            'a47' => ['short_answerx', null],
            'a48' => ['mc q', null],
            'a49' => ['tf ', 'true_false'],
            'a50' => ['multiple_choice ', 'multiple_choice'],
        ];
    }
}

