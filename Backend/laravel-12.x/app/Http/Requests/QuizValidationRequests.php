<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for quiz attempt start request
 */
class StartQuizAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('student');
    }

    public function rules(): array
    {
        return [
            'quiz_id' => 'nullable|integer|exists:quizzes,id|required_without:category_id',
            'category_id' => 'nullable|integer|exists:categories,id|required_without:quiz_id',
            'limit' => 'nullable|integer|min:1|max:200',
            'random' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'quiz_id.required_without' => 'Either quiz_id or category_id is required.',
            'category_id.required_without' => 'Either quiz_id or category_id is required.',
            'quiz_id.exists' => 'The selected quiz does not exist.',
            'category_id.exists' => 'The selected category does not exist.',
            'limit.min' => 'Limit must be at least 1.',
            'limit.max' => 'Limit cannot exceed 200.',
        ];
    }
}

/**
 * Validation for save answer request
 */
class SaveAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('student');
    }

    public function rules(): array
    {
        return [
            'question_id' => 'required|integer|exists:questions,id',
            'option_id' => 'nullable|integer|exists:question_options,id',
            'option_ids' => 'nullable|array',
            'option_ids.*' => 'integer|exists:question_options,id|distinct',
            'answer' => 'nullable',
            'text_answer' => 'nullable|string|max:5000',
        ];
    }

    public function messages(): array
    {
        return [
            'question_id.required' => 'Question ID is required.',
            'question_id.exists' => 'The selected question does not exist.',
            'option_id.exists' => 'The selected option does not exist.',
            'option_ids.array' => 'Selected answers must be an array.',
            'option_ids.*.exists' => 'One or more selected options do not exist.',
            'option_ids.*.distinct' => 'Duplicate option selections are not allowed.',
            'text_answer.max' => 'Text answer cannot exceed 5000 characters.',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if (!array_key_exists('option_ids', $data) && $this->has('option_ids')) {
            $data['option_ids'] = [];
        }

        $hasAnswer = array_key_exists('answer', $data);
        $hasOptionId = !empty($data['option_id']);
        $hasOptionIds = !empty($data['option_ids']);
        $hasTextAnswer = !empty($data['text_answer']);

        if (!$hasAnswer && !$hasOptionId && !$hasOptionIds && !$hasTextAnswer) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'answer' => 'An answer payload is required.',
            ]);
        }

        return $data;
    }
}

/**
 * Validation for question import (JSON)
 */
class ImportQuestionsJsonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'teacher']);
    }

    public function rules(): array
    {
        return [
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'required|string|max:1000',
            'questions.*.category' => 'required|string|max:100',
            'questions.*.question_type' => 'required|string|in:mcq,multiple_choice,tf,true_false,multi_select,short_answer',
            'questions.*.options' => 'nullable|array',
            'questions.*.options.*' => 'nullable|string|max:500',
            'questions.*.correct_answer' => 'nullable',
            'questions.*.points' => 'nullable|integer|min:1|max:1000',
            'questions.*.answer_key' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'questions.required' => 'Questions array is required.',
            'questions.array' => 'Questions must be an array.',
            'questions.min' => 'At least one question is required.',
            'questions.*.question_text.required' => 'Question text is required for each question.',
            'questions.*.question_type.in' => 'Question type must be one of: multiple_choice, true_false, multi_select, short_answer.',
            'questions.*.category.required' => 'Category is required for each question.',
            'questions.*.points.min' => 'Points must be at least 1.',
        ];
    }
}

/**
 * Validation for CSV import
 */
class ImportQuestionsFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'teacher']);
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimetypes:text/csv,text/plain,application/vnd.ms-excel',
                'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'A CSV file is required.',
            'file.file' => 'The selected file is not a valid file.',
            'file.mimetypes' => 'The file must be a CSV file.',
            'file.max' => 'The file size must not exceed 5MB.',
        ];
    }
}
