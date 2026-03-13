<?php

namespace App\Services\Scoring;

use App\Models\Quiz_attempt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuizAttemptScorer
{
    public function score(int $attemptId): array
    {
        return DB::transaction(function () use ($attemptId) {
            /** @var Quiz_attempt $attempt */
            $attempt = Quiz_attempt::with([
                'answers.question.options',
            ])->lockForUpdate()->findOrFail($attemptId);

            $answers = $attempt->answers;
            $answeredCount = $answers->count();

            $totalItems = $attempt->total_items;
            if ($totalItems === null || $totalItems === 0) {
                $totalItems = $answers->pluck('question_id')->unique()->count();
            }

            $correctCount = 0;

            foreach ($answers as $answer) {
                $question = $answer->question;
                $isCorrect = null;

                if ($question && in_array($question->question_type, ['mcq', 'tf'], true)) {
                    if ($answer->question_option_id) {
                        $option = $question->options->firstWhere('id', $answer->question_option_id);
                        $isCorrect = $option ? (bool) $option->is_correct : false;
                    } else {
                        $isCorrect = false;
                    }

                    if ($isCorrect) {
                        $correctCount++;
                    }
                } elseif ($question && $question->question_type === 'short_answer') {
                    $expected = $question->answer_key ?? '';
                    $given = $answer->text_answer ?? '';
                    $isCorrect = trim(mb_strtolower($given)) === trim(mb_strtolower($expected));

                    if ($isCorrect) {
                        $correctCount++;
                    }
                }

                $answer->is_correct = $isCorrect;
                $answer->save();
            }

            $scorePercent = $totalItems > 0
                ? round(($correctCount / $totalItems) * 100, 2)
                : 0;

            $attempt->answered_count = $answeredCount;
            $attempt->correct_answers = $correctCount;
            $attempt->score_percent = $scorePercent;
            $attempt->score = $correctCount;
            $attempt->save();

            return [
                'total_items' => $totalItems,
                'answered_count' => $answeredCount,
                'correct_answers' => $correctCount,
                'score_percent' => $scorePercent,
            ];
        }, 3);
    }

    public function safeScore(int $attemptId): array
    {
        try {
            return $this->score($attemptId);
        } catch (\Throwable $e) {
            Log::error('Quiz attempt scoring failed: ' . $e->getMessage(), [
                'attempt_id' => $attemptId,
            ]);
            throw $e;
        }
    }
}
