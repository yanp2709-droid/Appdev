<?php

namespace App\Filament\Resources\Teachers\Pages;

use App\Filament\Resources\Teachers\TeacherResource;
use App\Filament\Widgets\AcademicYearSelectorWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTeachers extends ListRecords
{
    protected static string $resource = TeacherResource::class;

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
                ->tooltip(fn (): ?string => $isCurrentYear ? null : 'New teachers can only be created for the current academic year'),
        ];
    }
}
