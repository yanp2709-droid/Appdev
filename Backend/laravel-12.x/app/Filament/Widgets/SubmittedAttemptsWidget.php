<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\AdminDashboard;
use App\Models\Quiz_attempt;
use App\Services\AcademicYearService;
use Illuminate\Support\Facades\Schema;

class SubmittedAttemptsWidget extends MetricCardWidget
{
    protected string $view = 'filament.widgets.metric-card-widget';

    protected static ?int $sort = 3;

    protected $listeners = ['academicYearChanged' => '$refresh'];

    protected function getMetricTitle(): string
    {
        return 'Submitted Attempts';
    }

    protected function getMetricValue(): string
    {
        return (string) $this->getSubmittedAttemptsForAcademicYear()->count();
    }

    private function getSubmittedAttemptsForAcademicYear()
    {
        $academicYear = AdminDashboard::getSelectedAcademicYear();
        [$startDate, $endDate] = app(AcademicYearService::class)->getDateRange($academicYear);

        $query = Quiz_attempt::query()
            ->where('status', 'submitted');

        if (Schema::hasColumn('quiz_attempts', 'school_year')) {
            $query->where('school_year', $academicYear);
        } else {
            $query->whereBetween('submitted_at', [$startDate, $endDate]);
        }

        return $query;
    }

    protected function getMetricDescription(): string
    {
        return 'Completed and graded attempts';
    }

    protected function getAccentColor(): string
    {
        return '#f59e0b';
    }

    protected function getAccentSurface(): string
    {
        return '#fef3c7';
    }
}
