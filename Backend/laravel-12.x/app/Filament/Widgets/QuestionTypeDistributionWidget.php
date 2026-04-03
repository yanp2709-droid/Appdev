<?php

namespace App\Filament\Widgets;

use App\Models\Question;
use Filament\Widgets\ChartWidget;

class QuestionTypeDistributionWidget extends ChartWidget
{
    protected ?string $heading = 'Question Type Distribution';

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 1;

    protected ?string $maxHeight = '320px';

    protected function getData(): array
    {
        $types = [
            'MCQ' => Question::query()->where('question_type', 'mcq')->count(),
            'True / False' => Question::query()->where('question_type', 'tf')->count(),
            'Multi-Select' => Question::query()->where('question_type', 'multi_select')->count(),
            'Short Answer' => Question::query()->where('question_type', 'short_answer')->count(),
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Questions',
                    'data' => array_values($types),
                    'backgroundColor' => [
                        '#111827',
                        '#2563eb',
                        '#f59e0b',
                        '#14b8a6',
                    ],
                ],
            ],
            'labels' => array_keys($types),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
