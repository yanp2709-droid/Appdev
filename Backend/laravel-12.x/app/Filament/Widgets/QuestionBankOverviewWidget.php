<?php

namespace App\Filament\Widgets;

use App\Models\Question;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QuestionBankOverviewWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Questions', Question::query()->count())
                ->description('Entire question bank')
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('primary'),

            Stat::make('MCQ', Question::query()->where('question_type', 'mcq')->count())
                ->description('Multiple choice questions')
                ->descriptionIcon('heroicon-m-list-bullet')
                ->color('success'),

            Stat::make('True / False', Question::query()->where('question_type', 'tf')->count())
                ->description('Binary response questions')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('info'),

            Stat::make('Multi-Select', Question::query()->where('question_type', 'multi_select')->count())
                ->description('Multiple correct answers')
                ->descriptionIcon('heroicon-m-list-bullet')
                ->color('warning'),

            Stat::make('Short Answer', Question::query()->where('question_type', 'short_answer')->count())
                ->description('Open-ended responses')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color('gray'),
        ];
    }
}
