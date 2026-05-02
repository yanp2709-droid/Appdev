<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Filament\Widgets\AcademicYearSelectorWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

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
            Actions\CreateAction::make()
                ->disabled(! $isCurrentYear)
                ->tooltip(fn (): ?string => $isCurrentYear ? null : 'New students can only be created for the current academic year'),
        ];
    }
}
