<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuestionFetchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
        'quiz_id' => 'nullable|exists:quizzes,id|required_without:category_id',
        'category_id' => 'nullable|exists:categories,id|required_without:quiz_id',
        'limit' => 'nullable|integer|min:1|max:50',
        'random' => 'nullable|boolean',
        ];
    }
}
