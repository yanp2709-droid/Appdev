<?php

namespace App\Http\Resources;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionApiResource extends JsonResource
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
            'type' => Question::toApiQuestionType($this->question_type ?? $this->type),
            'stored_type' => $this->question_type ?? $this->type,
            'prompt' => $this->question_text ?? $this->prompt,
            'points' => $this->points,
            'options' => $this->options->map(function ($option) {
                return [
                    'id' => $option->id,
                    'option_text' => $option->option_text
                ];
            })
        ];
    }
}
