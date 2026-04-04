<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Questions\QuestionResource;
use App\Models\Question;
use Illuminate\Validation\ValidationException;
use Filament\Resources\Pages\EditRecord;

class EditQuestion extends EditRecord
{
    protected static string $resource = QuestionResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $errors = Question::validatePayload($data, $this->record);

        if (!empty($errors)) {
            throw ValidationException::withMessages(['question' => $errors]);
        }

        return Question::normalizeQuestionPayload($data);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
