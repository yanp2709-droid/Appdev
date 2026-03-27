<?php

namespace App\Filament\Widgets;

use App\Services\QuizStatisticsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatisticsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $statistics = app(QuizStatisticsService::class)->getOverallStatistics();

        return [
            Stat::make('Completion Rate', ($statistics['completion_rate'] ?? 0) . '%')
                ->description('Submitted attempts vs total attempts')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Highest Score', ($statistics['highest_score'] ?? 0) . '%')
                ->description('Best submitted attempt')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary'),

            Stat::make('Lowest Score', ($statistics['lowest_score'] ?? 0) . '%')
                ->description('Lowest submitted attempt')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('In Progress', (string) ($statistics['in_progress_attempts'] ?? 0))
                ->description('Active unfinished attempts')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Expired Attempts', (string) ($statistics['expired_attempts'] ?? 0))
                ->description('Attempts that timed out')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('gray'),
        ];
    }
}
