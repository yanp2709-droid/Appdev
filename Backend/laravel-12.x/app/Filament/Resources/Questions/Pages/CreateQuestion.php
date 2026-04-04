<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Questions\QuestionResource;
use App\Models\Question;
use Illuminate\Validation\ValidationException;
use Filament\Resources\Pages\CreateRecord;

class CreateQuestion extends CreateRecord
{
    protected static string $resource = QuestionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $errors = Question::validatePayload($data);

        if (!empty($errors)) {
            throw ValidationException::withMessages(['question' => $errors]);
        }

        return Question::normalizeQuestionPayload($data);
    }

    protected function getRedirectUrl(): string
    {
        // After "Create" button, redirect to Questions list
        return $this->getResource()::getUrl('index');
    }
}

