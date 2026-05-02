<?php

namespace App\Filament\Resources\Quizzes\Pages;

use App\Filament\Resources\Quizzes\QuizResource;
use App\Filament\Widgets\AcademicYearSelectorWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQuizzes extends ListRecords
{
    protected static string $resource = QuizResource::class;

    protected static ?string $breadcrumb = 'List';

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
        $academicYearService = app(\App\Services\AcademicYearService::class);

        if ($academicYearService->getSelectedAcademicYear() !== $academicYearService->getCurrentAcademicYear()) {
            return [];
        }

        return [
            CreateAction::make(),
        ];
    }
}
