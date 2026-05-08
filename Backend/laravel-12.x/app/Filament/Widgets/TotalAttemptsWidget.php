<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\AdminDashboard;
use App\Models\Quiz_attempt;
use App\Services\AcademicYearService;
use Illuminate\Support\Facades\Schema;

class TotalAttemptsWidget extends MetricCardWidget
{
    protected string $view = 'filament.widgets.metric-card-widget';

    protected static ?int $sort = 2;

    protected $listeners = ['academicYearChanged' => '$refresh'];

    protected function getMetricTitle(): string
    {
        return 'Total Attempts';
    }

    protected function getMetricValue(): string
    {
        return (string) $this->getAttemptsForAcademicYear()->count();
    }

    private function getAttemptsForAcademicYear()
    {
        $academicYear = AdminDashboard::getSelectedAcademicYear();
        [$startDate, $endDate] = app(AcademicYearService::class)->getDateRange($academicYear);

        $query = Quiz_attempt::query();

        if (Schema::hasColumn('quiz_attempts', 'school_year')) {
            $query->where('school_year', $academicYear);
        } else {
            $query->whereBetween('submitted_at', [$startDate, $endDate]);
        }

        return $query;
    }

    protected function getMetricDescription(): string
    {
        return 'All quiz attempts';
    }

    protected function getAccentColor(): string
    {
        return '#2563eb';
    }

    protected function getAccentSurface(): string
    {
        return '#dbeafe';
    }
}
