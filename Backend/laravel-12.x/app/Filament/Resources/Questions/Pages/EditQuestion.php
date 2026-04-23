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

    protected function getRedirectUrl(): string
    {
        $categoryId = $this->data['category_id'] ?? $this->record->category_id ?? null;
        if ($categoryId) {
            // Redirect to the category's question list
            return \App\Filament\Resources\Categories\CategoryResource::getUrl('questions', ['record' => $categoryId]);
        }
        // Fallback to categories index if category_id is missing
        return \App\Filament\Resources\Categories\CategoryResource::getUrl('index');
    }
}
