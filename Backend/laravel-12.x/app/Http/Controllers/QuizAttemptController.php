<?php

namespace App\Http\Controllers;

use App\Http\Traits\ApiResponse;
use App\Models\Attempt_answer;
use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\Quiz_attempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\Scoring\QuizAttemptScorer;

class QuizAttemptController extends Controller
{
    use ApiResponse;

    private const STATUS_IN_PROGRESS = 'in_progress';
    private const STATUS_SUBMITTED = 'submitted';
    private const STATUS_EXPIRED = 'expired';
    private const DEFAULT_DURATION_MINUTES = 10;

    public function attempt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'quiz_id' => 'nullable|integer',
            'category_id' => 'nullable|exists:categories,id',
            'limit' => 'nullable|integer|min:1|max:200',
            'random' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error(
                'validation_error',
                'Invalid request.',
                422,
                $validator->errors()
            );
        }

        $payload = $validator->validated();

        if (empty($payload['quiz_id']) && empty($payload['category_id'])) {
            return $this->error(
                'validation_error',
                'quiz_id or category_id is required.',
                422
            );
        }

        $user = $request->user();
        $quiz = null;
        $category = null;

        if (!empty($payload['quiz_id'])) {
            $quiz = Quiz::find($payload['quiz_id']);
            if (!$quiz) {
                return $this->error('quiz_not_found', 'Quiz not found.', 404);
            }
        } else {
            $category = Category::find($payload['category_id']);
            $quiz = Quiz::where('category_id', $category->id)->first();
        }

        if (!$quiz) {
            return $this->error('quiz_not_found', 'No quiz found for this category.', 404);
        }
        $now = now();

        $activeAttempt = Quiz_attempt::where('student_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->where('status', self::STATUS_IN_PROGRESS)
            ->where(function ($query) use ($now) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->first();

        if ($activeAttempt) {
            return $this->error(
                'active_attempt_exists',
                'An active attempt already exists for this quiz.',
                409,
                $this->attemptMeta($activeAttempt)
            );
        }

        $durationMinutes = (int) ($quiz->duration_minutes ?? self::DEFAULT_DURATION_MINUTES);
        if ($durationMinutes <= 0) {
            if (empty($category)) {
                $category = Category::find($quiz->category_id);
            }
            $durationMinutes = (int) ($category->time_limit_minutes ?? self::DEFAULT_DURATION_MINUTES);
        }
        if ($durationMinutes <= 0) {
            $durationMinutes = self::DEFAULT_DURATION_MINUTES;
        }

        $startedAt = $now;
        $expiresAt = $startedAt->copy()->addMinutes($durationMinutes);

        $attempt = Quiz_attempt::create([
            'student_id' => $user->id,
            'quiz_id' => $quiz->id,
            'score' => 0,
            'status' => self::STATUS_IN_PROGRESS,
            'started_at' => $startedAt,
            'expires_at' => $expiresAt,
        ]);

        $query = Question::with('options')
            ->where('category_id', $quiz->category_id);

        if (!empty($payload['random'])) {
            $query->inRandomOrder();
        }

        if (!empty($payload['limit'])) {
            $query->limit($payload['limit']);
        }

        $questions = $query->get()->map(function ($question) {
            return [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'question_type' => $question->question_type,
                'points' => $question->points,
                'options' => $question->options->map(function ($option) {
                    return [
                        'id' => $option->id,
                        'option_text' => $option->option_text,
                        'order_index' => $option->order_index,
                    ];
                }),
            ];
        });

        $attempt->total_items = $questions->count();
        $attempt->save();

        return $this->success([
            'attempt' => $this->attemptMeta($attempt),
            'questions' => $questions,
        ], 'Attempt started.');
    }

    public function saveAnswer(Request $request, int $attemptId)
    {
        $validator = Validator::make($request->all(), [
            'question_id' => 'required|exists:questions,id',
            'option_id' => 'nullable|exists:question_options,id',
            'text_answer' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error(
                'validation_error',
                'Invalid request.',
                422,
                $validator->errors()
            );
        }

        $payload = $validator->validated();

        if (empty($payload['option_id']) && empty($payload['text_answer'])) {
            return $this->error(
                'validation_error',
                'Either option_id or text_answer is required.',
                422
            );
        }

        $attempt = $this->findStudentAttempt($request, $attemptId);
        if (!$attempt) {
            return $this->error('not_found', 'Attempt not found.', 404);
        }

        if ($attempt->status === self::STATUS_SUBMITTED) {
            return $this->error('attempt_submitted', 'Attempt already submitted.', 409);
        }

        if ($this->expireIfNeeded($attempt)) {
            return $this->error('attempt_expired', 'Attempt has expired.', 410);
        }

        $question = Question::where('id', $payload['question_id'])
            ->where('category_id', $attempt->quiz->category_id)
            ->first();

        if (!$question) {
            return $this->error('invalid_question', 'Question does not belong to this quiz.', 422);
        }

        if (!empty($payload['option_id'])) {
            $optionExists = QuestionOption::where('id', $payload['option_id'])
                ->where('question_id', $question->id)
                ->exists();

            if (!$optionExists) {
                return $this->error('invalid_option', 'Option does not belong to this question.', 422);
            }
        }

        $answer = Attempt_answer::updateOrCreate(
            [
                'quiz_attempt_id' => $attempt->id,
                'question_id' => $question->id,
            ],
            [
                'question_option_id' => $payload['option_id'] ?? null,
                'text_answer' => $payload['text_answer'] ?? null,
                'answer_id' => null,
            ]
        );

        return $this->success([
            'answer_id' => $answer->id,
            'attempt' => $this->attemptMeta($attempt),
        ], 'Answer saved.');
    }

    public function submit(Request $request, int $attemptId, QuizAttemptScorer $scorer)
    {
        $attempt = $this->findStudentAttempt($request, $attemptId);
        if (!$attempt) {
            return $this->error('not_found', 'Attempt not found.', 404);
        }

        if ($attempt->status === self::STATUS_SUBMITTED) {
            return $this->error('attempt_submitted', 'Attempt already submitted.', 409);
        }

        if ($this->expireIfNeeded($attempt)) {
            return $this->error('attempt_expired', 'Attempt has expired.', 410);
        }

        try {
            DB::transaction(function () use ($attempt, $scorer) {
                $attempt->status = self::STATUS_SUBMITTED;
                $attempt->submitted_at = now();
                $attempt->completed_at = $attempt->submitted_at;
                $attempt->save();

                $scorer->safeScore($attempt->id);
            }, 3);

            $attempt = $attempt->fresh();

            return $this->success([
                'attempt' => $this->attemptMeta($attempt),
                'score' => [
                    'total_items' => $attempt->total_items,
                    'answered_count' => $attempt->answered_count,
                    'correct_answers' => $attempt->correct_answers,
                    'score_percent' => $attempt->score_percent, // Use model's float-casted value
                ],
            ], 'Attempt submitted.');
        } catch (\Throwable $e) {
            return $this->error('scoring_failed', 'Scoring failed. Please retry.', 500);
        }
    }

    public function status(Request $request, int $attemptId)
    {
        $attempt = $this->findStudentAttempt($request, $attemptId);
        if (!$attempt) {
            return $this->error('not_found', 'Attempt not found.', 404);
        }

        $this->expireIfNeeded($attempt);

        $totalItems = $attempt->total_items ?? 0;
        if ($totalItems === 0) {
            $totalItems = Question::where('category_id', $attempt->quiz->category_id)->count();
        }
        $answeredCount = Attempt_answer::where('quiz_attempt_id', $attempt->id)->count();

        return $this->success([
            'attempt' => $this->attemptMeta($attempt),
            'answered_count' => $answeredCount,
            'total_items' => $totalItems,
        ], 'Attempt status.');
    }

    private function findStudentAttempt(Request $request, int $attemptId): ?Quiz_attempt
    {
        return Quiz_attempt::with('quiz')
            ->where('id', $attemptId)
            ->where('student_id', $request->user()->id)
            ->first();
    }

    private function attemptMeta(Quiz_attempt $attempt): array
    {
        $attempt->loadMissing('quiz.category');

        $durationMinutes = null;
        if ($attempt->started_at && $attempt->expires_at) {
            $durationMinutes = $attempt->started_at->diffInMinutes($attempt->expires_at);
        }

        return [
            'id' => $attempt->id,
            'quiz_id' => $attempt->quiz_id,
            'category_id' => $attempt->quiz->category_id ?? 0,
            'category_name' => $attempt->quiz->category->name ?? '',
            'status' => $attempt->status,
            'started_at' => $attempt->started_at,
            'expires_at' => $attempt->expires_at,
            'submitted_at' => $attempt->submitted_at,
            'duration_minutes' => $durationMinutes,
            'remaining_seconds' => $this->remainingSeconds($attempt),
            'total_items' => $attempt->total_items ?? 0,
            'answered_count' => $attempt->answered_count ?? 0,
            'correct_answers' => $attempt->correct_answers ?? 0,
            'score_percent' => (float) ($attempt->score_percent ?? 0),
        ];
    }

    private function remainingSeconds(Quiz_attempt $attempt): int
    {
        if (!$attempt->expires_at) {
            return 0;
        }

        $seconds = now()->diffInSeconds($attempt->expires_at, false);
        return max($seconds, 0);
    }

    private function expireIfNeeded(Quiz_attempt $attempt): bool
    {
        if ($attempt->status === self::STATUS_SUBMITTED) {
            return false;
        }

        if ($attempt->expires_at && now()->greaterThan($attempt->expires_at)) {
            if ($attempt->status !== self::STATUS_EXPIRED) {
                $attempt->status = self::STATUS_EXPIRED;
                $attempt->save();
            }
            return true;
        }

        return false;
    }

    /**
     * Get all completed attempts for the student
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $perPage = $request->query('per_page', 15); // Default 15 items per page

        $attempts = Quiz_attempt::with('quiz.category')
            ->where('student_id', $user->id)
            ->where('status', self::STATUS_SUBMITTED)
            ->orderByDesc('submitted_at')
            ->paginate($perPage);

        $history = $attempts->getCollection()->map(function ($attempt) {
            return $this->attemptMeta($attempt);
        });

        return $this->success([
            'attempts' => $history,
            'pagination' => [
                'total' => $attempts->total(),
                'per_page' => $attempts->perPage(),
                'current_page' => $attempts->currentPage(),
                'last_page' => $attempts->lastPage(),
                'from' => $attempts->firstItem(),
                'to' => $attempts->lastItem(),
            ],
        ], 'Attempt history retrieved.');
    }

    /**
     * Get detailed review of a specific submitted attempt
     * Includes per-question breakdown with selected and correct answers
     */
    public function detail(Request $request, int $attemptId)
    {
        $attempt = $this->findStudentAttempt($request, $attemptId);
        if (!$attempt) {
            return $this->error('not_found', 'Attempt not found.', 404);
        }

        if ($attempt->status !== self::STATUS_SUBMITTED) {
            return $this->error('attempt_not_submitted', 'Only submitted attempts can be reviewed.', 422);
        }

        // Load all data needed for the review WITH eager loading
        $attempt = $attempt->load([
            'answers.question.options',
            'quiz.category',
            'quiz.questions.options',
        ]);

        // Get only questions in THIS quiz (not all category questions)
        $questions = $attempt->quiz->questions()->with('options')->orderBy('id')->get();

        // Map answers by question ID for quick lookup
        $answersMap = $attempt->answers->keyBy('question_id')->toArray();

        // Build per-question review
        $review = $questions->map(function ($question) use ($answersMap) {
            $answer = $answersMap[$question->id] ?? null;
            $selectedOptionId = $answer['question_option_id'] ?? null;
            $textAnswer = $answer['text_answer'] ?? null;

            // Find correct answer
            $correctOption = $question->options->firstWhere('is_correct', true);

            // Build options array
            $options = $question->options->map(function ($option) use ($selectedOptionId) {
                return [
                    'id' => $option->id,
                    'text' => $option->option_text,
                    'is_selected' => $selectedOptionId === $option->id,
                    'is_correct' => (bool) $option->is_correct,
                    'order_index' => $option->order_index,
                ];
            });

            return [
                'question_id' => $question->id,
                'question_text' => $question->question_text,
                'question_type' => $question->question_type,
                'points' => $question->points,
                'options' => $options->sortBy('order_index')->values(),
                'selected_option_id' => $selectedOptionId,
                'correct_option_id' => $correctOption?->id,
                'text_answer' => $textAnswer,
                'is_answered' => $answer !== null,
                'is_correct' => $answer ? ($answer['is_correct'] ?? false) : false,
                'answer_id' => $answer['id'] ?? null,
            ];
        });

        return $this->success([
            'attempt' => $this->attemptMeta($attempt),
            'questions' => $review,
        ], 'Attempt details retrieved.');
    }
}
