<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\AdminDashboard;
use App\Models\User;
use App\Services\AcademicYearService;
use Illuminate\Support\Facades\Schema;

class TotalStudentsWidget extends MetricCardWidget
{
    protected string $view = 'filament.widgets.metric-card-widget';

    protected static ?int $sort = 1;

    protected $listeners = ['academicYearChanged' => '$refresh'];

    protected function getMetricTitle(): string
    {
        return 'Registered Students';
    }

    protected function getMetricValue(): string
    {
        return (string) $this->getStudentsInAcademicYear()->count();
    }

    private function getStudentsInAcademicYear()
    {
        $academicYear = AdminDashboard::getSelectedAcademicYear();

        return User::query()
            ->where('role', 'student')
            ->when(
                Schema::hasColumn('users', 'academic_year'),
                fn ($query) => $query->where('academic_year', $academicYear),
                function ($query) use ($academicYear) {
                    [$startDate, $endDate] = app(AcademicYearService::class)->getDateRange($academicYear);

                    return $query->whereBetween('created_at', [$startDate, $endDate]);
                },
            );
    }

    protected function getMetricDescription(): string
    {
        return 'Active student accounts';
    }

    protected function getAccentColor(): string
    {
        return '#16a34a';
    }

    protected function getAccentSurface(): string
    {
        return '#dcfce7';
    }
}
