<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class CategoryPerformanceWidget extends ChartWidget
{
    protected ?string $heading = 'Category Performance';

    protected static ?int $sort = 4;

    protected static bool $isLazy = false;

    protected ?string $maxHeight = '300px';

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $stats = DB::table('quiz_attempts')
            ->join('quizzes', 'quiz_attempts.quiz_id', '=', 'quizzes.id')
            ->join('categories', 'quizzes.category_id', '=', 'categories.id')
            ->where('quiz_attempts.status', 'submitted')
            ->groupBy('categories.id', 'categories.name')
            ->select('categories.name', DB::raw('AVG(quiz_attempts.score_percent) as average_score'))
            ->orderByDesc('average_score')
            ->limit(5)
            ->get();

        $labels = $stats->pluck('name')->toArray();
        $scores = $stats->pluck('average_score')->map(fn ($score) => round($score, 2))->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Average Score %',
                    'data' => $scores,
                    'backgroundColor' => [
                        '#f87171',
                        '#fb923c',
                        '#fbbf24',
                        '#a3e635',
                        '#4ade80',
                    ],
                    'borderColor' => '#ffffff',
                    'borderWidth' => 1,
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
