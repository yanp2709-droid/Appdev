<?php

namespace App\Filament\Widgets;

use App\Services\QuizStatisticsService;
use Filament\Widgets\ChartWidget;

class DifficultyAnalysisWidget extends ChartWidget
{
    protected ?string $heading = 'Difficulty Analysis';

    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 1;

    protected ?string $maxHeight = '320px';

    protected function getData(): array
    {
        $analysis = app(QuizStatisticsService::class)->getDifficultyAnalysis();

        $labels = array_map('ucfirst', array_keys($analysis));
        $scores = array_map(fn (array $item) => $item['average_score'], $analysis);

        return [
            'datasets' => [
                [
                    'label' => 'Average Score %',
                    'data' => $scores,
                    'backgroundColor' => [
                        '#38bdf8',
                        '#f59e0b',
                        '#ef4444',
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
