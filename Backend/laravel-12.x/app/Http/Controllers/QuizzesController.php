<?php

namespace App\Http\Controllers;

use App\Http\Traits\ApiResponse;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class QuizzesController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $payload = $this->validatedPayload($request);
            $payload['teacher_id'] = $payload['teacher_id'] ?? $request->user()->id;

            $quiz = Quiz::create($payload);

            return $this->success([
                'quiz' => $this->quizPayload($quiz->fresh('category')),
            ], 'Quiz created successfully.', 201);
        } catch (ValidationException $e) {
            return $this->validationError($e, 'Invalid quiz configuration.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Quiz $quiz)
    {
        return $this->success([
            'quiz' => $this->quizPayload($quiz->load('category')),
        ], 'Quiz retrieved.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Quiz $quiz)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Quiz $quiz)
    {
        try {
            $payload = $this->validatedPayload($request, false, $quiz);
            $quiz->update($payload);

            return $this->success([
                'quiz' => $this->quizPayload($quiz->fresh('category')),
            ], 'Quiz updated successfully.');
        } catch (ValidationException $e) {
            return $this->validationError($e, 'Invalid quiz configuration.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Quiz $quiz)
    {
        $quiz->delete();

        return $this->success([], 'Quiz deleted successfully.');
    }

    private function validatedPayload(Request $request, bool $requireAll = true, ?Quiz $existingQuiz = null): array
    {
        $rules = [
            'title' => [$requireAll ? 'required' : 'sometimes', 'string', 'max:255'],
            'category_id' => [$requireAll ? 'required' : 'sometimes', 'integer', 'exists:categories,id'],
            'teacher_id' => ['sometimes', 'integer', 'exists:users,id'],
            'difficulty' => [$requireAll ? 'required' : 'sometimes', 'string', 'in:Easy,Medium,Hard'],
            'timer_enabled' => ['sometimes', 'boolean'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'shuffle_questions' => ['sometimes', 'boolean'],
            'shuffle_options' => ['sometimes', 'boolean'],
            'max_attempts' => ['nullable', 'integer', 'min:1'],
            'attempt_limit' => ['nullable', 'integer', 'min:1'],
            'allow_review_before_submit' => ['sometimes', 'boolean'],
            'show_score_immediately' => ['sometimes', 'boolean'],
            'show_answers_after_submit' => ['sometimes', 'boolean'],
            'show_correct_answers_after_submit' => ['sometimes', 'boolean'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $payload = Quiz::normalizePayload($validator->validated());

        if ($existingQuiz) {
            $payload = array_merge(
                $existingQuiz->only([
                    'title',
                    'category_id',
                    'teacher_id',
                    'difficulty',
                    'duration_minutes',
                    'timer_enabled',
                    'shuffle_questions',
                    'shuffle_options',
                    'max_attempts',
                    'allow_review_before_submit',
                    'show_score_immediately',
                    'show_answers_after_submit',
                    'show_correct_answers_after_submit',
                ]),
                $payload
            );
        }

        $logicErrors = Quiz::validatePayload($payload);

        if (!empty($logicErrors)) {
            throw ValidationException::withMessages([
                'quiz' => $logicErrors,
            ]);
        }

        return $payload;
    }

    private function quizPayload(Quiz $quiz): array
    {
        return [
            'id' => $quiz->id,
            'title' => $quiz->title,
            'category_id' => $quiz->category_id,
            'teacher_id' => $quiz->teacher_id,
            'difficulty' => $quiz->difficulty,
            'duration_minutes' => $quiz->duration_minutes,
            'timer_enabled' => (bool) $quiz->timer_enabled,
            'shuffle_questions' => (bool) $quiz->shuffle_questions,
            'shuffle_options' => (bool) $quiz->shuffle_options,
            'max_attempts' => $quiz->max_attempts,
            'attempt_limit' => $quiz->max_attempts,
            'allow_review_before_submit' => (bool) $quiz->allow_review_before_submit,
            'show_score_immediately' => (bool) $quiz->show_score_immediately,
            'show_answers_after_submit' => (bool) $quiz->show_answers_after_submit,
            'show_correct_answers_after_submit' => (bool) $quiz->show_correct_answers_after_submit,
        ];
    }
}
