<?php

namespace App\Filament\Widgets;

use App\Models\Quiz_attempt;

class TotalAttemptsWidget extends MetricCardWidget
{
    protected string $view = 'filament.widgets.metric-card-widget';

    protected static ?int $sort = 2;

    protected function getMetricTitle(): string
    {
        return 'Total Attempts';
    }

    protected function getMetricValue(): string
    {
        return (string) Quiz_attempt::count();
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
