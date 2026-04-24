<?php

namespace App\Filament\Widgets;

use App\Models\User;

class TotalStudentsWidget extends MetricCardWidget
{
    protected string $view = 'filament.widgets.metric-card-widget';

    protected static ?int $sort = 1;

    protected function getMetricTitle(): string
    {
        return 'Total Students';
    }

    protected function getMetricValue(): string
    {
        return (string) User::where('role', 'student')->count();
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
