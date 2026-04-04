<?php

namespace App\Filament\Resources\Quizzes\Pages;

use App\Filament\Resources\Quizzes\QuizResource;
use App\Models\Quiz;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditQuiz extends EditRecord
{
    protected static string $resource = QuizResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = Quiz::normalizePayload($data);
        $errors = Quiz::validatePayload($data);

        if (!empty($errors)) {
            throw ValidationException::withMessages(['quiz' => $errors]);
        }

        return $data;
    }
}
