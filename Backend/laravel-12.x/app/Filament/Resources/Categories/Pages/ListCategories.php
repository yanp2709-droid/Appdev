<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Widgets\AcademicYearSelectorWidget;
use App\Services\AcademicYearService;
use Illuminate\Support\Facades\DB;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected static ?string $breadcrumb = 'List';

    protected string $view = 'filament.resources.categories.pages.list-categories';

    protected $listeners = ['academicYearChanged' => '$refresh'];

    protected function getHeaderWidgets(): array
    {
        return [
            AcademicYearSelectorWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getCategories(): \Illuminate\Support\Collection
    {
        $academicYearService = app(AcademicYearService::class);
        [$startDate, $endDate] = $academicYearService->getDateRange($academicYearService->getSelectedAcademicYear());

        return Category::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select('categories.*')
            ->withCount('questions')
            ->selectSub(
                DB::table('quiz_attempts')
                    ->join('quizzes', 'quiz_attempts.quiz_id', '=', 'quizzes.id')
                    ->whereColumn('quizzes.category_id', 'categories.id')
                    ->where('quiz_attempts.status', 'submitted')
                    ->where('quiz_attempts.attempt_type', 'graded')
                    ->selectRaw('MAX(quiz_attempts.score_percent)'),
                'highest_score'
            )
            ->selectSub(
                DB::table('quiz_attempts')
                    ->join('quizzes', 'quiz_attempts.quiz_id', '=', 'quizzes.id')
                    ->whereColumn('quizzes.category_id', 'categories.id')
                    ->where('quiz_attempts.status', 'submitted')
                    ->where('quiz_attempts.attempt_type', 'graded')
                    ->selectRaw('MIN(quiz_attempts.score_percent)'),
                'lowest_score'
            )
            ->orderBy('name')
            ->get();
    }

    public function disableCategory(int $categoryId): void
    {
        $category = Category::query()->find($categoryId);

        if (! $category) {
            return;
        }

        $categoryName = $category->name;

        $category->update([
            'is_published' => false,
        ]);

        Notification::make()
            ->title('Quiz disabled')
            ->body("{$categoryName} is now hidden from students taking quizzes.")
            ->success()
            ->send();
    }

    public function enableCategory(int $categoryId): void
    {
        $category = Category::query()->find($categoryId);

        if (! $category) {
            return;
        }

        $categoryName = $category->name;

        $category->update([
            'is_published' => true,
        ]);

        Notification::make()
            ->title('Quiz enabled')
            ->body("{$categoryName} is visible to students taking quizzes again.")
            ->success()
            ->send();
    }
}
