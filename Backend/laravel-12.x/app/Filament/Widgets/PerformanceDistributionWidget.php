<?php

namespace App\Filament\Widgets;

use App\Services\QuizStatisticsService;
use Filament\Widgets\ChartWidget;

class PerformanceDistributionWidget extends ChartWidget
{
    protected ?string $heading = 'Performance Distribution';

    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 1;

    protected ?string $maxHeight = '320px';

    protected function getData(): array
    {
        $distribution = app(QuizStatisticsService::class)->getPerformanceDistribution();

        return [
            'datasets' => [
                [
                    'label' => 'Students by Grade Band',
                    'data' => array_map(fn (array $grade) => $grade['count'], $distribution),
                    'backgroundColor' => [
                        '#16a34a',
                        '#65a30d',
                        '#eab308',
                        '#f97316',
                        '#dc2626',
                    ],
                ],
            ],
            'labels' => array_keys($distribution),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
