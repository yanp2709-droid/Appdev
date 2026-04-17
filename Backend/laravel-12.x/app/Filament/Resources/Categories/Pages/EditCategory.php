<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    private array $quizSettings = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return array_merge($data, $this->record->loadMissing('quiz')->getQuizFormState());
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->quizSettings = $this->extractQuizSettings($data);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->syncQuizConfiguration($this->quizSettings);
    }

    protected function getHeaderActions(): array
    {
        return [];
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
