<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

    private array $quizSettings = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->quizSettings = $this->extractQuizSettings($data);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->syncQuizConfiguration($this->quizSettings);
    }

    protected function getRedirectUrl(): string
    {
        // After "Create" button, redirect to Categories list
        return $this->getResource()::getUrl('index');
    }

    private function extractQuizSettings(array &$data): array
    {
        $settings = [];

        foreach (Category::QUIZ_SETTING_FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $settings[$field] = $data[$field];
                unset($data[$field]);
            }
        }

        return $settings;
    }
}
