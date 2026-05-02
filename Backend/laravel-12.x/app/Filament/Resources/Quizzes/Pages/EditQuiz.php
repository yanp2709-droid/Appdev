<?php

namespace App\Filament\Resources\Quizzes\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Quizzes\QuizResource;
use App\Models\Quiz;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;

class EditQuiz extends EditRecord
{
    protected static string $resource = QuizResource::class;

    protected static ?string $breadcrumb = 'Edit';

    public function hasResourceBreadcrumbs(): bool
    {
        return false;
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            CategoryResource::getUrl('index') => 'Subject',
        ];

        $category = $this->record->category ?? null;
        if ($category) {
            $breadcrumbs[CategoryResource::getUrl('quizzes', ['record' => $category])] = $category->name;
        }

        $breadcrumbs[QuizResource::getUrl('questions', ['record' => $this->record])] = $this->getRecordTitle();
        $breadcrumbs[] = 'Edit';

        return $breadcrumbs;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = Quiz::normalizePayload($data);
        if (Schema::hasColumn('quizzes', 'is_active')) {
            $data['is_active'] = $data['is_active'] ?? $this->record->is_active;
        }
        $errors = Quiz::validatePayload($data);

        if (!empty($errors)) {
            throw ValidationException::withMessages(['quiz' => $errors]);
        }

        return $data;
    }
}
