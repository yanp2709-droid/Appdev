<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function latest(Request $request)
    {
        $user = $request->user();

        $latestAttempt = Attempt::where('user_id', $user->id)
            ->where('status', 'completed')
            ->orderByDesc('submitted_at')
            ->first();

        if (!$latestAttempt) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => [
                'score' => $latestAttempt->score,
                'correct_answers' => $latestAttempt->correct_answers,
                'total_items' => $latestAttempt->total_items,
                'submitted_at' => $latestAttempt->submitted_at,
                'quiz_id' => $latestAttempt->quiz_id,
            ],
        ]);
    }

    public function history(Request $request)
    {
        $user = $request->user();

        $history = Attempt::where('user_id', $user->id)
            ->where('status', 'completed')
            ->orderByDesc('submitted_at')
            ->get(['quiz_id', 'score', 'correct_answers', 'total_items', 'submitted_at']);

        return response()->json(['data' => $history]);
    }

    public function attemptDetails($attemptId)
{
    $studentId = auth()->id(); // get logged-in student

    $attempt = Attempt::with([
        'answers.question.options' // eager load to avoid N+1
    ])->where('id', $attemptId)
      ->where('student_id', $studentId) // restrict access
      ->where('status', 'completed')   // only completed attempts
      ->firstOrFail();

    $response = [
        'attempt_id' => $attempt->id,
        'quiz_title' => $attempt->quiz->title,
        'score' => $attempt->score,
        'total_items' => $attempt->total_items,
        'submitted_at' => $attempt->submitted_at,
        'questions' => $attempt->answers->map(function($answer) {
            return [
                'question_text' => $answer->question->text,
                'options' => $answer->question->options->map(function($option) {
                    return [
                        'id' => $option->id,
                        'text' => $option->text,
                    ];
                }),
                'selected_option_id' => $answer->option_id,
                'correct_option_id' => $answer->question->correct_option_id,
                'is_correct' => $answer->is_correct,
                'explanation' => $answer->question->explanation ?? null,
            ];
        }),
    ];

    return response()->json($response);
}
}