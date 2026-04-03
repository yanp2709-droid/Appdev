<?php

namespace App\Services\Scoring;

use App\Models\Attempt_answer;
use App\Models\Question;
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
                $isCorrect = $question ? $this->scoreAnswer($question, $answer) : null;

                if ($isCorrect) {
                    $correctCount++;
                }

                $answer->is_correct = $isCorrect;
                $answer->save();
            }

            $scorePercent = ($totalItems > 0
                ? round(($correctCount / $totalItems) * 100, 2)
                : 0) * 1.0;

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

    private function scoreAnswer(Question $question, Attempt_answer $answer): ?bool
    {
        $questionType = Question::normalizeQuestionType($question->question_type) ?? $question->question_type;

        if (in_array($questionType, [Question::TYPE_MCQ, Question::TYPE_TRUE_FALSE], true)) {
            $selectedOptionId = $answer->question_option_id;
            if (!$selectedOptionId && !empty($answer->selected_option_ids)) {
                $selectedOptionId = $answer->selected_option_ids[0] ?? null;
            }

            if (!$selectedOptionId) {
                return false;
            }

            $option = $question->options->firstWhere('id', $selectedOptionId);

            return $option ? (bool) $option->is_correct : false;
        }

        if ($questionType === Question::TYPE_MULTI_SELECT) {
            $selectedOptionIds = collect($answer->selected_option_ids ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->sort()
                ->values();

            $correctOptionIds = $question->options
                ->where('is_correct', true)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values();

            return $selectedOptionIds->isNotEmpty()
                && $selectedOptionIds->count() === $correctOptionIds->count()
                && $selectedOptionIds->values()->all() === $correctOptionIds->values()->all();
        }

        if ($questionType === Question::TYPE_SHORT_ANSWER) {
            $expected = $question->answer_key ?? '';
            $given = $answer->text_answer ?? '';

            return trim(mb_strtolower($given)) === trim(mb_strtolower($expected));
        }

        return null;
    }
}
