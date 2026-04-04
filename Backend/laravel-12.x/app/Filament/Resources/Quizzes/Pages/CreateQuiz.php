<?php

namespace App\Filament\Resources\Quizzes\Pages;

use App\Filament\Resources\Quizzes\QuizResource;
use App\Models\Quiz;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateQuiz extends CreateRecord
{
    protected static string $resource = QuizResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = Quiz::normalizePayload($data);
        $errors = Quiz::validatePayload($data);

        if (!empty($errors)) {
            throw ValidationException::withMessages(['quiz' => $errors]);
        }

        return $data;
    }
}
