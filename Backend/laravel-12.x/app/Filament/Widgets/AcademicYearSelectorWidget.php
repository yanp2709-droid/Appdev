<?php

namespace App\Filament\Widgets;

use App\Services\AcademicYearService;
use Filament\Widgets\Widget;

class AcademicYearSelectorWidget extends Widget
{
    protected string $view = 'filament.widgets.academic-year-selector-widget';

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    public string $selectedAcademicYear = '';

    protected $listeners = ['academicYearChanged' => 'syncAcademicYear'];

    public function mount(): void
    {
        $this->selectedAcademicYear = app(AcademicYearService::class)->getSelectedAcademicYear();
    }

    public function changeAcademicYear(): void
    {
        $this->selectedAcademicYear = app(AcademicYearService::class)->setSelectedAcademicYear($this->selectedAcademicYear);

        $this->dispatch('academicYearChanged');
    }

    public function syncAcademicYear(): void
    {
        $this->selectedAcademicYear = app(AcademicYearService::class)->getSelectedAcademicYear();
    }

    /**
     * @return array<string, string>
     */
    public function getAcademicYearOptions(): array
    {
        return app(AcademicYearService::class)->getOptions();
    }
}
