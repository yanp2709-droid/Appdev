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
        $isCurrentYear = $academicYearService->getSelectedAcademicYear() === $academicYearService->getCurrentAcademicYear();

        return [
            CreateAction::make()
                ->disabled(! $isCurrentYear)
                ->tooltip(fn (): ?string => $isCurrentYear ? null : 'New quizzes can only be created for the current academic year'),
        ];
    }
}
