<?php

namespace Tests\Unit;

use App\Models\Attempt_answer;
use PHPUnit\Framework\TestCase;

class AttemptAnswerLogicTest extends TestCase
{
    public function test_it_returns_selected_option_ids_from_json_payload(): void
    {
        $answer = new Attempt_answer([
            'selected_option_ids' => '[3,5,7]',
        ]);

        $this->assertSame([3, 5, 7], $answer->selected_option_ids);
    }

    public function test_it_falls_back_to_single_question_option_id(): void
    {
        $answer = new Attempt_answer([
            'question_option_id' => 11,
        ]);

        $this->assertSame([11], $answer->selected_option_ids);
    }

    public function test_it_returns_empty_array_when_no_option_is_selected(): void
    {
        $answer = new Attempt_answer();

        $this->assertSame([], $answer->selected_option_ids);
    }
}
