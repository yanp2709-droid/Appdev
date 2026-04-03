<?php

namespace App\Http\Controllers;

use App\Http\Traits\ApiResponse;
use App\Models\Attempt_answer;
use App\Models\Category;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Quiz_attempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
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
            return $this->formatAttemptQuestion($question);
        });

        $attempt->total_items = $questions->count();
        $attempt->save();

        return $this->success([
            'attempt' => $this->attemptMeta($attempt),
            'questions' => $questions,
        ], 'Attempt started.');
    }

    /**
     * Get the authenticated student's attempt history.
     */
    public function history(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);
        $status = $request->query('status', self::STATUS_SUBMITTED);

        $query = Quiz_attempt::with(['quiz.category'])
            ->where('student_id', $request->user()->id);

        if (!empty($status)) {
            $query->where('status', $status);
        }

        $attempts = $query->orderByDesc('submitted_at')
            ->orderByDesc('started_at')
            ->paginate($perPage);

        $attemptsData = $attempts->getCollection()->map(function (Quiz_attempt $attempt) {
            $durationMinutes = $attempt->getDurationMinutes();
            if ($durationMinutes === null) {
                $durationMinutes = (int) ($attempt->quiz->duration_minutes ?? self::DEFAULT_DURATION_MINUTES);
            }

            return [
                'id' => $attempt->id,
                'quiz_id' => $attempt->quiz_id,
                'category_id' => $attempt->quiz->category_id ?? 0,
                'category_name' => $attempt->quiz->category->name ?? 'Unknown',
                'status' => $attempt->status,
                'started_at' => $attempt->started_at,
                'submitted_at' => $attempt->submitted_at,
                'duration_minutes' => $durationMinutes,
                'total_items' => (int) ($attempt->total_items ?? 0),
                'answered_count' => (int) ($attempt->answered_count ?? 0),
                'correct_answers' => (int) ($attempt->correct_answers ?? 0),
                'score_percent' => (float) ($attempt->score_percent ?? 0),
            ];
        });

        return $this->success([
            'attempts' => $attemptsData,
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
     * Get detailed review data for a specific attempt.
     */
    public function detail(Request $request, int $attemptId)
    {
        $attempt = Quiz_attempt::with([
            'quiz.category',
            'answers.question.options',
            'answers.questionOption',
        ])
            ->where('id', $attemptId)
            ->where('student_id', $request->user()->id)
            ->first();

        if (!$attempt) {
            return $this->error('not_found', 'Attempt not found.', 404);
        }

        $questions = $attempt->answers
            ->sortBy('id')
            ->map(function (Attempt_answer $answer) {
                $question = $answer->question;
                if (!$question) {
                    return null;
                }

                $selectedOptionIds = $this->selectedOptionIdsFromAnswer($answer);
                $selectedOptionId = count($selectedOptionIds) === 1 ? $selectedOptionIds[0] : null;
                $correctOptionIds = $question->options
                    ->where('is_correct', true)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all();
                $correctOptionId = count($correctOptionIds) === 1 ? $correctOptionIds[0] : null;

                return [
                    'question_id' => $question->id,
                    'question_text' => $question->question_text,
                    'question_type' => Question::toApiQuestionType($question->question_type),
                    'stored_question_type' => $question->question_type,
                    'points' => (int) ($question->points ?? 0),
                    'options' => $question->options->map(function ($option) use ($selectedOptionIds, $correctOptionIds) {
                        return [
                            'id' => $option->id,
                            'text' => $option->option_text,
                            'is_selected' => in_array((int) $option->id, $selectedOptionIds, true),
                            'is_correct' => in_array((int) $option->id, $correctOptionIds, true),
                            'order_index' => $option->order_index,
                        ];
                    })->values(),
                    'selected_option_id' => $selectedOptionId ? (int) $selectedOptionId : null,
                    'selected_option_ids' => $selectedOptionIds,
                    'correct_option_id' => $correctOptionId,
                    'correct_option_ids' => $correctOptionIds,
                    'text_answer' => $answer->text_answer,
                    'is_answered' => !empty($selectedOptionIds) || !empty($answer->text_answer),
                    'is_correct' => (bool) ($answer->is_correct ?? false),
                    'score_impact' => (bool) ($answer->is_correct ?? false) ? (int) ($question->points ?? 0) : 0,
                    'answer_id' => (int) $answer->id,
                ];
            })
            ->filter()
            ->values();

        return $this->success([
            'attempt' => [
                'id' => $attempt->id,
                'quiz_id' => $attempt->quiz_id,
                'category_id' => $attempt->quiz->category_id ?? 0,
                'category_name' => $attempt->quiz->category->name ?? 'Unknown',
                'status' => $attempt->status,
                'started_at' => $attempt->started_at,
                'submitted_at' => $attempt->submitted_at,
                'total_items' => (int) ($attempt->total_items ?? 0),
                'answered_count' => (int) ($attempt->answered_count ?? 0),
                'correct_answers' => (int) ($attempt->correct_answers ?? 0),
                'score_percent' => (float) ($attempt->score_percent ?? 0),
            ],
            'questions' => $questions,
        ], 'Attempt detail retrieved.');
    }

    public function saveAnswer(Request $request, int $attemptId)
    {
        try {
            $baseValidator = Validator::make($request->all(), [
                'question_id' => 'required|integer|exists:questions,id',
                'option_id' => 'nullable|integer|exists:question_options,id',
                'option_ids' => 'nullable|array',
                'option_ids.*' => 'integer|exists:question_options,id|distinct',
                'answer' => 'nullable',
                'text_answer' => 'nullable|string|max:5000',
            ]);

            if ($baseValidator->fails()) {
                return $this->error(
                    'validation_error',
                    'Invalid request.',
                    422,
                    $baseValidator->errors()
                );
            }

            $payload = $baseValidator->validated();

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

            $question = Question::with('options')
                ->where('id', $payload['question_id'])
                ->where('category_id', $attempt->quiz->category_id)
                ->first();

            if (!$question) {
                return $this->error('invalid_question', 'Question does not belong to this quiz.', 422);
            }

            $normalizedAnswer = $this->normalizeSubmittedAnswer($question, $payload);

            $answer = Attempt_answer::updateOrCreate(
                [
                    'quiz_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                ],
                [
                    'question_option_id' => $normalizedAnswer['question_option_id'],
                    'selected_option_ids' => $normalizedAnswer['selected_option_ids'],
                    'text_answer' => $normalizedAnswer['text_answer'],
                    'answer_id' => null,
                    'is_correct' => null,
                ]
            );

            return $this->success([
                'answer_id' => $answer->id,
                'attempt' => $this->attemptMeta($attempt),
                'question_type' => Question::toApiQuestionType($question->question_type),
                'selected_option_id' => $normalizedAnswer['question_option_id'],
                'selected_option_ids' => $normalizedAnswer['selected_option_ids'] ?? [],
                'text_answer' => $normalizedAnswer['text_answer'],
            ], 'Answer saved.');
        } catch (ValidationException $e) {
            return $this->validationError($e, 'Invalid request.');
        }
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
        $durationMinutes = null;
        if ($attempt->started_at && $attempt->expires_at) {
            $durationMinutes = $attempt->started_at->diffInMinutes($attempt->expires_at);
        }

        return [
            'id' => $attempt->id,
            'quiz_id' => $attempt->quiz_id,
            'status' => $attempt->status,
            'started_at' => $attempt->started_at,
            'expires_at' => $attempt->expires_at,
            'submitted_at' => $attempt->submitted_at,
            'duration_minutes' => $durationMinutes,
            'remaining_seconds' => $this->remainingSeconds($attempt),
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

    private function formatAttemptQuestion(Question $question): array
    {
        return [
            'id' => $question->id,
            'question_text' => $question->question_text,
            'question_type' => Question::toApiQuestionType($question->question_type),
            'stored_question_type' => $question->question_type,
            'points' => $question->points,
            'options' => $question->options->map(function ($option) {
                return [
                    'id' => $option->id,
                    'option_text' => $option->option_text,
                    'order_index' => $option->order_index,
                ];
            })->values(),
        ];
    }

    private function normalizeSubmittedAnswer(Question $question, array $payload): array
    {
        $questionType = Question::normalizeQuestionType($question->question_type) ?? $question->question_type;
        $submitted = $payload['answer'] ?? null;

        if ($questionType === Question::TYPE_SHORT_ANSWER) {
            $textAnswer = array_key_exists('text_answer', $payload)
                ? trim((string) $payload['text_answer'])
                : trim((string) $submitted);

            if ($textAnswer === '') {
                throw ValidationException::withMessages([
                    'text_answer' => 'Text answer is required for short answer questions.',
                ]);
            }

            return [
                'question_option_id' => null,
                'selected_option_ids' => null,
                'text_answer' => $textAnswer,
            ];
        }

        $questionOptionIds = $question->options->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $selectedOptionIds = [];

        if ($questionType === Question::TYPE_MULTI_SELECT) {
            if (array_key_exists('option_ids', $payload)) {
                $selectedOptionIds = (array) $payload['option_ids'];
            } elseif (is_array($submitted)) {
                $selectedOptionIds = $submitted;
            } elseif ($submitted !== null) {
                $selectedOptionIds = [$submitted];
            }

            $selectedOptionIds = array_values(array_map('intval', $selectedOptionIds));

            if (empty($selectedOptionIds)) {
                throw ValidationException::withMessages([
                    'answer' => 'At least one option must be selected for multi-select questions.',
                ]);
            }

            if (count($selectedOptionIds) !== count(array_unique($selectedOptionIds))) {
                throw ValidationException::withMessages([
                    'option_ids' => 'Duplicate option selections are not allowed.',
                ]);
            }

            foreach ($selectedOptionIds as $optionId) {
                if (!in_array($optionId, $questionOptionIds, true)) {
                    throw ValidationException::withMessages([
                        'option_ids' => 'All selected options must belong to the current question.',
                    ]);
                }
            }

            sort($selectedOptionIds);

            return [
                'question_option_id' => null,
                'selected_option_ids' => $selectedOptionIds,
                'text_answer' => null,
            ];
        }

        if (is_bool($submitted)) {
            $matchingOption = $question->options->first(function ($option) use ($submitted) {
                return mb_strtolower(trim((string) $option->option_text)) === ($submitted ? 'true' : 'false');
            });

            if (!$matchingOption) {
                throw ValidationException::withMessages([
                    'answer' => 'Boolean answers require True/False options on the question.',
                ]);
            }

            $selectedOptionId = (int) $matchingOption->id;
        } elseif (array_key_exists('option_id', $payload) && $payload['option_id'] !== null) {
            $selectedOptionId = (int) $payload['option_id'];
        } elseif (is_array($submitted)) {
            throw ValidationException::withMessages([
                'answer' => 'Only one option may be selected for this question type.',
            ]);
        } elseif ($submitted !== null && $submitted !== '') {
            $selectedOptionId = (int) $submitted;
        } else {
            $selectedOptionId = null;
        }

        if ($selectedOptionId === null) {
            throw ValidationException::withMessages([
                'answer' => 'A single option selection is required for this question type.',
            ]);
        }

        if (!in_array($selectedOptionId, $questionOptionIds, true)) {
            throw ValidationException::withMessages([
                'option_id' => 'Option does not belong to this question.',
            ]);
        }

        return [
            'question_option_id' => $selectedOptionId,
            'selected_option_ids' => [$selectedOptionId],
            'text_answer' => null,
        ];
    }

    private function selectedOptionIdsFromAnswer(Attempt_answer $answer): array
    {
        $selectedOptionIds = $answer->selected_option_ids ?? [];

        if (empty($selectedOptionIds) && !empty($answer->question_option_id)) {
            $selectedOptionIds = [(int) $answer->question_option_id];
        }

        if (empty($selectedOptionIds) && !empty($answer->answer_id)) {
            $selectedOptionIds = [(int) $answer->answer_id];
        }

        return array_values(array_unique(array_map('intval', $selectedOptionIds)));
    }
}
