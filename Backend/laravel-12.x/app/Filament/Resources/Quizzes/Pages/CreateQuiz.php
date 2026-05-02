<?php

namespace App\Filament\Resources\Quizzes\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Quizzes\QuizResource;
use App\Models\Quiz;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;

class CreateQuiz extends CreateRecord
{
    protected static string $resource = QuizResource::class;

    protected static ?string $breadcrumb = 'Quiz';

    public ?int $categoryId = null;

    public function mount(): void
    {
        $this->categoryId = request()->integer('category_id') ?: null;

        parent::mount();
    }

    public function hasResourceBreadcrumbs(): bool
    {
        return false;
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            CategoryResource::getUrl('index') => 'Subject',
        ];

        if ($this->categoryId) {
            $category = \App\Models\Category::find($this->categoryId);
            if ($category) {
                $breadcrumbs[CategoryResource::getUrl('quizzes', ['record' => $category])] = $category->name;
            }
        }

        $breadcrumbs[] = 'Quiz';

        return $breadcrumbs;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = Quiz::normalizePayload($data);
        if (Schema::hasColumn('quizzes', 'is_active')) {
            $data['is_active'] = $data['is_active'] ?? true;
        }
        $errors = Quiz::validatePayload($data);

        if (!empty($errors)) {
            throw ValidationException::withMessages(['quiz' => $errors]);
        }

        return $data;
    }
}
