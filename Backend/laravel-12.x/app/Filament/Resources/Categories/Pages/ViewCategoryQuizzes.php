<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Quizzes\QuizResource;
use App\Models\Quiz;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCategoryQuizzes extends ViewRecord
{
    protected static string $resource = CategoryResource::class;

    protected string $view = 'filament.resources.categories.pages.view-category-quizzes';

    public function getTitle(): string
    {
        return $this->getRecord()->name . ' Quizzes';
    }

    public function hasResourceBreadcrumbs(): bool
    {
        return false;
    }

    public function getBreadcrumbs(): array
    {
        return [
            CategoryResource::getUrl('index') => 'Subject',
            $this->getRecord()->name,
        ];
    }

    public function getBreadcrumb(): string
    {
        return '';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make('newQuiz')
                ->label('New Quiz')
                ->url(fn () => QuizResource::getUrl('create', ['category_id' => $this->getRecord()->id])),
        ];
    }

    public function getQuizzes(): \Illuminate\Support\Collection
    {
        return $this->getRecord()
            ->quizzes()
            ->withCount('questions')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getQuizQuestionsUrl(Quiz $quiz): string
    {
        return QuizResource::getUrl('questions', ['record' => $quiz->id]);
    }

    public function disableQuiz(int $quizId): void
    {
        $quiz = $this->getRecord()->quizzes()->whereKey($quizId)->first();

        if (! $quiz) {
            return;
        }

        $quiz->update(['is_active' => false]);

        Notification::make()
            ->title('Quiz disabled')
            ->body($quiz->title . ' is now hidden from students.')
            ->success()
            ->send();
    }

    public function enableQuiz(int $quizId): void
    {
        $quiz = $this->getRecord()->quizzes()->whereKey($quizId)->first();

        if (! $quiz) {
            return;
        }

        $quiz->update(['is_active' => true]);

        Notification::make()
            ->title('Quiz enabled')
            ->body($quiz->title . ' is visible to students again.')
            ->success()
            ->send();
    }
}
