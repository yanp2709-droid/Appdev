<?php

namespace App\Filament\Widgets\Concerns;

trait InteractsWithMetricCard
{
    protected function getViewData(): array
    {
        return [
            'metricTitle' => $this->getMetricTitle(),
            'metricValue' => $this->getMetricValue(),
            'metricDescription' => $this->getMetricDescription(),
            'accentColor' => $this->getAccentColor(),
            'accentSurface' => $this->getAccentSurface(),
        ];
    }

    abstract protected function getMetricTitle(): string;

    abstract protected function getMetricValue(): string;

    abstract protected function getMetricDescription(): string;

    protected function getAccentColor(): string
    {
        return '#0f172a';
    }

    protected function getAccentSurface(): string
    {
        return '#eef2ff';
    }
}
