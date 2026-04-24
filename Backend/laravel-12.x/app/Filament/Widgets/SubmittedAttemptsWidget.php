<?php

namespace App\Filament\Widgets;

use App\Models\Quiz_attempt;

class SubmittedAttemptsWidget extends MetricCardWidget
{
    protected string $view = 'filament.widgets.metric-card-widget';

    protected static ?int $sort = 3;

    protected function getMetricTitle(): string
    {
        return 'Submitted Attempts';
    }

    protected function getMetricValue(): string
    {
        return (string) Quiz_attempt::where('status', 'submitted')->count();
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
