<?php

namespace App\Http\Controllers;

use App\Http\Traits\ApiResponse;
use App\Models\Attempt_answer;
use App\Models\Category;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Quiz_attempt;
use App\Models\QuizRetakeAllowance;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
    private const TYPE_GRADED = Quiz_attempt::TYPE_GRADED;
    private const TYPE_PRACTICE = Quiz_attempt::TYPE_PRACTICE;
    private const DEFAULT_DURATION_MINUTES = Quiz::DEFAULT_DURATION_MINUTES;

    public function attempt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'quiz_id' => 'nullable|integer',
            'category_id' => 'nullable|exists:categories,id',
            'attempt_type' => 'nullable|string|in:' . self::TYPE_GRADED . ',' . self::TYPE_PRACTICE,
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
        $attemptType = $payload['attempt_type'] ?? self::TYPE_GRADED;

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
            $quiz = $this->resolveQuizForCategory($category);
        }

        if (!$quiz) {
            return $this->error('quiz_not_found', 'No quiz found for this category.', 404);
        }
        $now = now();

        Quiz_attempt::where('student_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->where('attempt_type', $attemptType)
            ->where('status', self::STATUS_IN_PROGRESS)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now)
            ->update([
                'status' => self::STATUS_EXPIRED,
                'last_activity_at' => $now,
                'updated_at' => $now,
            ]);

        $activeAttempt = Quiz_attempt::where('student_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->where('attempt_type', $attemptType)
            ->where('status', self::STATUS_IN_PROGRESS)
            ->where(function ($query) use ($now) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->first();

        if ($activeAttempt) {
            return $this->success(
                $this->buildAttemptPayload($activeAttempt->fresh(['quiz', 'answers']), null, true),
                'Active attempt resumed.'
            );
        }

        if ($attemptType === self::TYPE_GRADED && !empty($quiz->max_attempts)) {
            $attemptCount = Quiz_attempt::where('student_id', $user->id)
                ->where('quiz_id', $quiz->id)
                ->where('attempt_type', self::TYPE_GRADED)
                ->count();

            if ($attemptCount >= $quiz->max_attempts) {
                return $this->error(
                    'attempt_limit_reached',
                    'Maximum attempt limit reached for this quiz.',
                    403
                );
            }
        }

        if ($attemptType === self::TYPE_GRADED && !$this->hasAvailableGradedAttempt($user->id, $quiz->id)) {
            return $this->error(
                'graded_attempt_already_used',
                'You have already used your graded attempt for this quiz. You may still continue in practice mode.',
                403,
                $this->attemptAvailabilityPayload($user->id, $quiz->id)
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
        $expiresAt = null;

        // Only set expiration if timer is explicitly enabled and duration is positive
        if ((bool) $quiz->timer_enabled && $durationMinutes > 0) {
            $expiresAt = $startedAt->copy()->addMinutes($durationMinutes);
        }

        $attempt = Quiz_attempt::create([
            'student_id' => $user->id,
            'quiz_id' => $quiz->id,
            'attempt_type' => $attemptType,
            'score' => 0,
            'status' => self::STATUS_IN_PROGRESS,
            'started_at' => $startedAt,
            'expires_at' => $expiresAt,
        ]);

        $query = Question::with('options')
            ->where('category_id', $quiz->category_id);

        $shuffleQuestions = !empty($payload['random']) || $quiz->shuffle_questions;
        if ($shuffleQuestions) {
            $query->inRandomOrder();
        }

        if (!empty($payload['limit'])) {
            $query->limit($payload['limit']);
        }

        $questions = $query->get();

        $attempt->total_items = $questions->count();
        $attempt->question_sequence = $questions->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $attempt->last_activity_at = $startedAt;
        $attempt->save();

        return $this->success(
            $this->buildAttemptPayload($attempt->fresh(['quiz', 'answers']), $questions, false),
            $attemptType === self::TYPE_GRADED ? 'Graded attempt started.' : 'Practice attempt started.'
        );
    }

    public function availability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'quiz_id' => 'nullable|integer|exists:quizzes,id',
            'category_id' => 'nullable|integer|exists:categories,id',
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

        $quiz = !empty($payload['quiz_id'])
            ? Quiz::find($payload['quiz_id'])
            : $this->resolveQuizForCategory(Category::find($payload['category_id']));

        if (!$quiz) {
            return $this->error('quiz_not_found', 'Quiz not found.', 404);
        }

        return $this->success([
            'quiz_id' => $quiz->id,
            'attempt_availability' => $this->attemptAvailabilityPayload($request->user()->id, $quiz->id),
        ], 'Attempt availability retrieved.');
    }

    private function resolveQuizForCategory(Category $category): ?Quiz
    {
        $hasQuestions = Question::where('category_id', $category->id)->exists();
        if (!$hasQuestions) {
            return null;
        }

        $categoryName = trim((string) $category->name);
        $durationMinutes = (int) ($category->time_limit_minutes ?? self::DEFAULT_DURATION_MINUTES);
        if ($durationMinutes <= 0) {
            $durationMinutes = self::DEFAULT_DURATION_MINUTES;
        }

        return Quiz::firstOrCreate(
            ['category_id' => $category->id],
            [
                'title' => $categoryName !== '' ? $categoryName . ' Quiz' : 'Category Quiz',
                'teacher_id' => null,
                'difficulty' => 'Easy',
                'duration_minutes' => $durationMinutes,
                'timer_enabled' => true,
                'shuffle_questions' => false,
                'shuffle_options' => false,
                'max_attempts' => null,
                'allow_review_before_submit' => false,
                'show_score_immediately' => true,
                'show_answers_after_submit' => false,
                'show_correct_answers_after_submit' => false,
            ]
        );
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
            
            // If no duration calculated from attempt times, use quiz configuration with fallbacks
            if ($durationMinutes === null) {
                $durationMinutes = (int) ($attempt->quiz->duration_minutes ?? self::DEFAULT_DURATION_MINUTES);
                if ($durationMinutes <= 0) {
                    $category = $attempt->quiz->category;
                    $durationMinutes = (int) ($category->time_limit_minutes ?? self::DEFAULT_DURATION_MINUTES);
                }
                if ($durationMinutes <= 0) {
                    $durationMinutes = self::DEFAULT_DURATION_MINUTES;
                }
            }

            $isOfficialGradedAttempt = $this->isOfficialGradedAttempt($attempt);
            $canExposeScore = $this->canExposeAttemptScore($attempt);

            return [
                'id' => $attempt->id,
                'quiz_id' => $attempt->quiz_id,
                'category_id' => $attempt->quiz->category_id ?? 0,
                'category_name' => $attempt->quiz->category->name ?? 'Unknown',
                'attempt_type' => $attempt->attempt_type ?? self::TYPE_GRADED,
                'is_scored_attempt' => $isOfficialGradedAttempt,
                'is_official_graded_attempt' => $isOfficialGradedAttempt,
                'is_practice_attempt' => $attempt->isPracticeAttempt(),
                'can_review_answers' => $this->canReviewAttemptDetails($attempt),
                'show_correct_answers' => $this->shouldShowCorrectAnswers($attempt),
                'status' => $attempt->status,
                'started_at' => $attempt->started_at,
                'submitted_at' => $attempt->submitted_at,
                'duration_minutes' => $durationMinutes,
                'total_items' => (int) ($attempt->total_items ?? 0),
                'answered_count' => (int) ($attempt->answered_count ?? 0),
                'correct_answers' => $canExposeScore ? (int) ($attempt->correct_answers ?? 0) : null,
                'score_percent' => $canExposeScore ? (float) ($attempt->score_percent ?? 0) : null,
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

        $canReview = $this->canReviewAttemptDetails($attempt);
        $showCorrectAnswers = $this->shouldShowCorrectAnswers($attempt);

        $questions = $attempt->answers
            ->when($canReview, function ($answers) use ($canReview, $showCorrectAnswers) {
                return $answers
                    ->sortBy('id')
                    ->map(function (Attempt_answer $answer) use ($canReview, $showCorrectAnswers) {
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
                            'options' => $question->options->map(function ($option) use ($selectedOptionIds, $correctOptionIds, $canReview, $showCorrectAnswers) {
                                return [
                                    'id' => $option->id,
                                    'text' => $option->option_text,
                                    'is_selected' => in_array((int) $option->id, $selectedOptionIds, true),
                                    'is_correct' => $canReview && $showCorrectAnswers && in_array((int) $option->id, $correctOptionIds, true),
                                    'order_index' => $option->order_index,
                                ];
                            })->values(),
                            'selected_option_id' => $selectedOptionId ? (int) $selectedOptionId : null,
                            'selected_option_ids' => $selectedOptionIds,
                            'correct_option_id' => $canReview && $showCorrectAnswers ? $correctOptionId : null,
                            'correct_option_ids' => !($canReview && $showCorrectAnswers) ? [] : (array) $correctOptionIds,
                            'text_answer' => $answer->text_answer,
                            'is_answered' => !empty($selectedOptionIds) || !empty($answer->text_answer),
                            'is_correct' => $canReview && $showCorrectAnswers ? (bool) ($answer->is_correct ?? false) : null,
                            'score_impact' => $canReview && $showCorrectAnswers ? ((bool) ($answer->is_correct ?? false) ? (int) ($question->points ?? 0) : 0) : 0,
                            'answer_id' => (int) $answer->id,
                        ];
                    })
                    ->filter()
                    ->values();
            }, fn () => collect());

        return $this->success([
            'attempt' => [
                'id' => $attempt->id,
                'quiz_id' => $attempt->quiz_id,
                'category_id' => $attempt->quiz->category_id ?? 0,
                'category_name' => $attempt->quiz->category->name ?? 'Unknown',
                'attempt_type' => $attempt->attempt_type ?? self::TYPE_GRADED,
                'is_scored_attempt' => $this->isOfficialGradedAttempt($attempt),
                'is_official_graded_attempt' => $this->isOfficialGradedAttempt($attempt),
                'is_practice_attempt' => $attempt->isPracticeAttempt(),
                'can_review_answers' => $canReview,
                'show_correct_answers' => $showCorrectAnswers,
                'status' => $attempt->status,
                'started_at' => $attempt->started_at,
                'submitted_at' => $attempt->submitted_at,
                'total_items' => (int) ($attempt->total_items ?? 0),
                'answered_count' => (int) ($attempt->answered_count ?? 0),
                'correct_answers' => $this->canExposeAttemptScore($attempt) ? (int) ($attempt->correct_answers ?? 0) : null,
                'score_percent' => $this->canExposeAttemptScore($attempt) ? (float) ($attempt->score_percent ?? 0) : null,
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
                'is_bookmarked' => 'nullable|boolean',
                'last_viewed_question_id' => 'nullable|integer|exists:questions,id',
                'last_viewed_question_index' => 'nullable|integer|min:0',
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

            if (!empty($payload['last_viewed_question_id'])) {
                $lastViewedQuestionBelongsToQuiz = Question::query()
                    ->where('id', $payload['last_viewed_question_id'])
                    ->where('category_id', $attempt->quiz->category_id)
                    ->exists();

                if (!$lastViewedQuestionBelongsToQuiz) {
                    return $this->error('invalid_question', 'Question does not belong to this quiz.', 422);
                }
            }

            $this->validateAutosavePayload($payload);

            $hasAnswerPayload = $this->hasAnswerPayload($payload);
            $normalizedAnswer = $hasAnswerPayload
                ? $this->normalizeSubmittedAnswer($question, $payload)
                : null;

            $existingAnswer = Attempt_answer::query()
                ->where('quiz_attempt_id', $attempt->id)
                ->where('question_id', $question->id)
                ->first();

            $answerAttributes = [
                'is_bookmarked' => (bool) ($payload['is_bookmarked'] ?? ($existingAnswer?->is_bookmarked ?? false)),
            ];

            if ($normalizedAnswer !== null) {
                $answerAttributes = array_merge($answerAttributes, [
                    'question_option_id' => $normalizedAnswer['question_option_id'],
                    'selected_option_ids' => $normalizedAnswer['selected_option_ids'],
                    'text_answer' => $normalizedAnswer['text_answer'],
                    'answer_id' => null,
                    'is_correct' => null,
                ]);
            }

            $answer = Attempt_answer::updateOrCreate(
                [
                    'quiz_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                ],
                $answerAttributes
            );

            $now = now();
            $attempt->last_activity_at = $now;

            if (!empty($payload['last_viewed_question_id'])) {
                $attempt->last_viewed_question_id = (int) $payload['last_viewed_question_id'];
            } else {
                $attempt->last_viewed_question_id = $question->id;
            }

            if (array_key_exists('last_viewed_question_index', $payload)) {
                $attempt->last_viewed_question_index = $payload['last_viewed_question_index'];
            } else {
                $attempt->last_viewed_question_index = $this->findQuestionIndex($attempt, $question->id);
            }

            $attempt->answered_count = $this->countAnsweredQuestions($attempt->id);
            $attempt->save();
            $attempt->refresh();

            return $this->success([
                'answer_id' => $answer->id,
                'attempt' => $this->attemptMeta($attempt),
                'question_type' => Question::toApiQuestionType($question->question_type),
                'selected_option_id' => count($this->selectedOptionIdsFromAnswer($answer)) === 1
                    ? $this->selectedOptionIdsFromAnswer($answer)[0]
                    : null,
                'selected_option_ids' => $this->selectedOptionIdsFromAnswer($answer),
                'text_answer' => $answer->text_answer,
                'is_bookmarked' => (bool) $answer->is_bookmarked,
            ], 'Answer saved.');
        } catch (ValidationException $e) {
            return $this->validationError($e, 'Invalid request.');
        }
    }

    public function quit(Request $request, int $attemptId)
    {
        $attempt = $this->findStudentAttempt($request, $attemptId);
        if (!$attempt) {
            return $this->error('attempt_not_found', 'Attempt not found.', 404);
        }

        if ($attempt->status === self::STATUS_IN_PROGRESS) {
            $now = now();
            $attempt->status = self::STATUS_EXPIRED;
            $attempt->expires_at = $now;
            $attempt->last_activity_at = $now;
            $attempt->save();
            $attempt->refresh();

            return $this->success([
                'attempt' => $this->attemptMeta($attempt),
            ], 'Attempt ended.');
        }

        return $this->success([
            'attempt' => $this->attemptMeta($attempt),
        ], 'Attempt already closed.');
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
                $attempt->last_activity_at = $attempt->submitted_at;
                $attempt->save();

                $scorer->safeScore($attempt->id);
            }, 3);

            $attempt = $attempt->fresh();

            $isOfficialGradedAttempt = $this->isOfficialGradedAttempt($attempt);
            $scorePayload = null;
            if ($this->shouldShowAttemptScore($attempt)) {
                $scorePayload = [
                    'total_items' => $attempt->total_items,
                    'answered_count' => $attempt->answered_count,
                    'correct_answers' => $attempt->correct_answers,
                    'score_percent' => $attempt->score_percent, // Use model's float-casted value
                ];
            }

            return $this->success([
                'attempt' => $this->attemptMeta($attempt),
                'is_scored_attempt' => $isOfficialGradedAttempt,
                'is_official_graded_attempt' => $isOfficialGradedAttempt,
                'is_practice_attempt' => $attempt->isPracticeAttempt(),
                'score' => $scorePayload,
                'attempt_availability' => $this->attemptAvailabilityPayload($attempt->student_id, $attempt->quiz_id),
            ], $attempt->isPracticeAttempt() ? 'Practice attempt submitted.' : 'Graded attempt submitted.');
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

        // Load relationships for building the full response
        $attempt->load(['quiz.category', 'answers.question.options', 'answers.questionOption']);

        $totalItems = $attempt->total_items ?? 0;
        if ($totalItems === 0) {
            $totalItems = Question::where('category_id', $attempt->quiz->category_id)->count();
        }
        $answeredCount = $this->countAnsweredQuestions($attempt->id);

        $canReview = $this->canReviewAttemptDetails($attempt);
        $showCorrectAnswers = $this->shouldShowCorrectAnswers($attempt);

        $questions = $attempt->answers
            ->sortBy('id')
            ->map(function (Attempt_answer $answer) use ($canReview, $showCorrectAnswers) {
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
                    'options' => $question->options->map(function ($option) use ($selectedOptionIds, $correctOptionIds, $canReview, $showCorrectAnswers) {
                        return [
                            'id' => $option->id,
                            'text' => $option->option_text,
                            'is_selected' => in_array((int) $option->id, $selectedOptionIds, true),
                            'is_correct' => $canReview && $showCorrectAnswers && in_array((int) $option->id, $correctOptionIds, true),
                            'order_index' => $option->order_index,
                        ];
                    })->values(),
                    'selected_option_id' => $selectedOptionId ? (int) $selectedOptionId : null,
                    'selected_option_ids' => $selectedOptionIds,
                    'correct_option_id' => $canReview && $showCorrectAnswers ? $correctOptionId : null,
                    'correct_option_ids' => !($canReview && $showCorrectAnswers) ? [] : (array)$correctOptionIds,
                    'text_answer' => $answer->text_answer,
                    'is_answered' => !empty($selectedOptionIds) || !empty($answer->text_answer),
                    'is_correct' => $canReview && $showCorrectAnswers ? (bool) ($answer->is_correct ?? false) : null,
                    'score_impact' => $canReview && $showCorrectAnswers ? ((bool) ($answer->is_correct ?? false) ? (int) ($question->points ?? 0) : 0) : 0,
                    'answer_id' => (int) $answer->id,
                ];
            })
            ->filter()
            ->values();

        return $this->success([
            'attempt' => $this->attemptMeta($attempt),
            'answered_count' => $answeredCount,
            'total_items' => $totalItems,
            'saved_answers' => $this->savedAnswersPayload($attempt),
            'progress' => $this->progressPayload($attempt),
            'questions' => $questions,
            'attempt_availability' => $this->attemptAvailabilityPayload($attempt->student_id, $attempt->quiz_id),
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
            'attempt_type' => $attempt->attempt_type ?? self::TYPE_GRADED,
            'is_graded_attempt' => $attempt->isGradedAttempt(),
            'is_practice_attempt' => $attempt->isPracticeAttempt(),
            'status' => $attempt->status,
            'started_at' => $attempt->started_at,
            'expires_at' => $attempt->expires_at,
            'submitted_at' => $attempt->submitted_at,
            'duration_minutes' => $durationMinutes,
            'remaining_seconds' => $this->remainingSeconds($attempt),
            'last_activity_at' => $attempt->last_activity_at,
            'last_viewed_question_id' => $attempt->last_viewed_question_id ? (int) $attempt->last_viewed_question_id : null,
            'last_viewed_question_index' => $attempt->last_viewed_question_index,
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
                $attempt->last_activity_at = now();
                $attempt->save();
            }
            return true;
        }

        return false;
    }

    private function formatAttemptQuestion(Question $question, Quiz_attempt $attempt): array
    {
        $options = $question->options;
        if ($attempt->quiz->shuffle_options) {
            $options = $options->shuffle();
        }

        return [
            'id' => $question->id,
            'question_text' => $question->question_text,
            'question_type' => Question::toApiQuestionType($question->question_type),
            'stored_question_type' => $question->question_type,
            'points' => $question->points,
            'saved_answer' => null,
            'options' => $options->values()->map(function ($option, int $index) {
                return [
                    'id' => $option->id,
                    'option_text' => $option->option_text,
                    'order_index' => $index,
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

    private function validateAutosavePayload(array $payload): void
    {
        if (
            !$this->hasAnswerPayload($payload)
            && !array_key_exists('is_bookmarked', $payload)
            && !array_key_exists('last_viewed_question_id', $payload)
            && !array_key_exists('last_viewed_question_index', $payload)
        ) {
            throw ValidationException::withMessages([
                'answer' => 'Answer or progress data is required.',
            ]);
        }
    }

    private function hasAnswerPayload(array $payload): bool
    {
        return array_key_exists('option_id', $payload)
            || array_key_exists('option_ids', $payload)
            || array_key_exists('text_answer', $payload)
            || array_key_exists('answer', $payload);
    }

    private function buildAttemptPayload(Quiz_attempt $attempt, ?Collection $questions = null, bool $resumed = false): array
    {
        $questions = $questions ?? $this->resolveAttemptQuestions($attempt);

        return [
            'attempt' => $this->attemptMeta($attempt),
            'quiz_settings' => $this->quizSettingsPayload($attempt->quiz),
            'questions' => $this->questionsPayload($questions, $attempt),
            'saved_answers' => $this->savedAnswersPayload($attempt),
            'progress' => $this->progressPayload($attempt),
            'attempt_availability' => $this->attemptAvailabilityPayload($attempt->student_id, $attempt->quiz_id),
            'resumed' => $resumed,
        ];
    }

    private function resolveAttemptQuestions(Quiz_attempt $attempt): Collection
    {
        $questionIds = collect($attempt->question_sequence ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter();

        if ($questionIds->isEmpty()) {
            $questionIds = Question::where('category_id', $attempt->quiz->category_id)
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id) => (int) $id);
        }

        $questions = Question::with('options')
            ->whereIn('id', $questionIds->all())
            ->get()
            ->keyBy('id');

        return $questionIds
            ->map(fn (int $id) => $questions->get($id))
            ->filter()
            ->values();
    }

    private function questionsPayload(Collection $questions, Quiz_attempt $attempt): Collection
    {
        $answersByQuestionId = $attempt->relationLoaded('answers')
            ? $attempt->answers->keyBy('question_id')
            : Attempt_answer::where('quiz_attempt_id', $attempt->id)->get()->keyBy('question_id');

        return $questions->values()->map(function (Question $question, int $index) use ($answersByQuestionId, $attempt) {
            $formatted = $this->formatAttemptQuestion($question, $attempt);
            $formatted['index'] = $index;
            $formatted['saved_answer'] = $this->savedAnswerPayload($answersByQuestionId->get($question->id));

            return $formatted;
        });
    }

    private function savedAnswersPayload(Quiz_attempt $attempt): array
    {
        $answers = $attempt->relationLoaded('answers')
            ? $attempt->answers
            : Attempt_answer::where('quiz_attempt_id', $attempt->id)->get();

        return $answers
            ->mapWithKeys(function (Attempt_answer $answer) {
                return [(string) $answer->question_id => $this->savedAnswerPayload($answer)];
            })
            ->all();
    }

    private function savedAnswerPayload(?Attempt_answer $answer): ?array
    {
        if (!$answer) {
            return null;
        }

        $selectedOptionIds = $this->selectedOptionIdsFromAnswer($answer);

        return [
            'answer_id' => (int) $answer->id,
            'question_id' => (int) $answer->question_id,
            'selected_option_id' => count($selectedOptionIds) === 1 ? $selectedOptionIds[0] : null,
            'selected_option_ids' => $selectedOptionIds,
            'text_answer' => $answer->text_answer,
            'is_bookmarked' => (bool) $answer->is_bookmarked,
            'updated_at' => $answer->updated_at,
        ];
    }

    private function progressPayload(Quiz_attempt $attempt): array
    {
        return [
            'answered_count' => (int) ($attempt->answered_count ?? 0),
            'last_viewed_question_id' => $attempt->last_viewed_question_id ? (int) $attempt->last_viewed_question_id : null,
            'last_viewed_question_index' => $attempt->last_viewed_question_index,
            'last_activity_at' => $attempt->last_activity_at,
        ];
    }

    private function quizSettingsPayload(Quiz $quiz): array
    {
        // Calculate the actual duration that will be used for the quiz attempt
        // This ensures the frontend sees the same duration that will actually be used
        $durationMinutes = (int) ($quiz->duration_minutes ?? self::DEFAULT_DURATION_MINUTES);
        if ($durationMinutes <= 0) {
            $category = Category::find($quiz->category_id);
            $durationMinutes = (int) ($category->time_limit_minutes ?? self::DEFAULT_DURATION_MINUTES);
        }
        if ($durationMinutes <= 0) {
            $durationMinutes = self::DEFAULT_DURATION_MINUTES;
        }

        return [
            'shuffle_questions' => (bool) $quiz->shuffle_questions,
            'shuffle_options' => (bool) $quiz->shuffle_options,
            'max_attempts' => $quiz->max_attempts,
            'attempt_limit' => $quiz->max_attempts,
            'timer_enabled' => (bool) $quiz->timer_enabled,
            'duration_minutes' => $durationMinutes,
            'allow_review_before_submit' => (bool) $quiz->allow_review_before_submit,
            'show_score_immediately' => (bool) $quiz->show_score_immediately,
            'show_answers_after_submit' => (bool) $quiz->show_answers_after_submit,
            'show_correct_answers_after_submit' => (bool) $quiz->show_correct_answers_after_submit,
        ];
    }

    private function canReviewAttemptDetails(Quiz_attempt $attempt): bool
    {
        if ($attempt->status === self::STATUS_SUBMITTED) {
            return (bool) $attempt->quiz->show_answers_after_submit;
        }

        return (bool) $attempt->quiz->allow_review_before_submit;
    }

    private function shouldShowCorrectAnswers(Quiz_attempt $attempt): bool
    {
        if ($attempt->status !== self::STATUS_SUBMITTED) {
            return false;
        }

        return (bool) $attempt->quiz->show_correct_answers_after_submit;
    }

    private function shouldShowAttemptScore(Quiz_attempt $attempt): bool
    {
        if ($attempt->status !== self::STATUS_SUBMITTED) {
            return false;
        }

        return (bool) $attempt->quiz->show_score_immediately;
    }

    private function canExposeAttemptScore(Quiz_attempt $attempt): bool
    {
        return $attempt->status === self::STATUS_SUBMITTED && $this->shouldShowAttemptScore($attempt);
    }

    private function isScoredAttempt(Quiz_attempt $attempt): bool
    {
        return $this->isOfficialGradedAttempt($attempt);
    }

    private function isOfficialGradedAttempt(Quiz_attempt $attempt): bool
    {
        return $attempt->status === self::STATUS_SUBMITTED && $attempt->isGradedAttempt();
    }

    private function hasSubmittedGradedAttempt(int $studentId, int $quizId): bool
    {
        return $this->submittedGradedAttemptCount($studentId, $quizId) > 0;
    }

    private function hasAvailableGradedAttempt(int $studentId, int $quizId): bool
    {
        return $this->submittedGradedAttemptCount($studentId, $quizId) < $this->allowedGradedAttemptCount($studentId, $quizId);
    }

    private function submittedGradedAttemptCount(int $studentId, int $quizId): int
    {
        return Quiz_attempt::query()
            ->where('student_id', $studentId)
            ->where('quiz_id', $quizId)
            ->where('attempt_type', self::TYPE_GRADED)
            ->where('status', self::STATUS_SUBMITTED)
            ->count();
    }

    private function allowedGradedAttemptCount(int $studentId, int $quizId): int
    {
        $additionalAttempts = (int) QuizRetakeAllowance::query()
            ->where('student_id', $studentId)
            ->where('quiz_id', $quizId)
            ->value('additional_graded_attempts');

        return 1 + max($additionalAttempts, 0);
    }

    private function attemptAvailabilityPayload(int $studentId, int $quizId): array
    {
        $submittedGradedAttempts = $this->submittedGradedAttemptCount($studentId, $quizId);
        $allowedGradedAttempts = $this->allowedGradedAttemptCount($studentId, $quizId);
        $remainingGradedAttempts = max($allowedGradedAttempts - $submittedGradedAttempts, 0);
        $gradedAttemptUsed = $remainingGradedAttempts === 0;

        return [
            'graded_attempt_available' => $remainingGradedAttempts > 0,
            'practice_attempt_available' => true,
            'graded_attempt_used' => $gradedAttemptUsed,
            'remaining_graded_attempts' => $remainingGradedAttempts,
            'allowed_graded_attempts' => $allowedGradedAttempts,
            'submitted_graded_attempts' => $submittedGradedAttempts,
        ];
    }

    private function countAnsweredQuestions(int $attemptId): int
    {
        return Attempt_answer::query()
            ->where('quiz_attempt_id', $attemptId)
            ->where(function ($query) {
                $query->whereNotNull('question_option_id')
                    ->orWhereNotNull('selected_option_ids')
                    ->orWhere(function ($nested) {
                        $nested->whereNotNull('text_answer')
                            ->where('text_answer', '<>', '');
                    });
            })
            ->count();
    }

    private function findQuestionIndex(Quiz_attempt $attempt, int $questionId): ?int
    {
        $questionSequence = collect($attempt->question_sequence ?? [])
            ->map(fn ($id) => (int) $id)
            ->values();

        $index = $questionSequence->search($questionId);

        return $index === false ? null : (int) $index;
    }
}
