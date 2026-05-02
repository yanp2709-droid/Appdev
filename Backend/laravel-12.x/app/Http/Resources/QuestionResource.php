<?php

namespace App\Http\Resources;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question_text' => $this->question_text,
            'category_id' => $this->category_id,
            'quiz_id' => $this->quiz_id,
            'question_type' => Question::toApiQuestionType($this->question_type),
            'stored_question_type' => $this->question_type,
            'points' => $this->points,
            'options' => $this->whenLoaded('options', function () {
                return $this->options->map(function ($option) {
                    return [
                        'id' => $option->id,
                        'option_text' => $option->option_text,
                        'is_correct' => $option->is_correct ?? false,
                    ];
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
