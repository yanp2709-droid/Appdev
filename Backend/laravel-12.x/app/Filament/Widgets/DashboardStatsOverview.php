<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Quiz_attempt;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalStudents = User::where('role', 'student')->count();
        $totalAttempts = Quiz_attempt::count();
        $submittedAttempts = Quiz_attempt::where('status', 'submitted')->count();
        $averageScore = Quiz_attempt::where('status', 'submitted')->avg('score_percent') ?? 0;

        return [
            Stat::make('Total Students', $totalStudents)
                ->description('Active student accounts')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Total Attempts', $totalAttempts)
                ->description('All quiz attempts (in progress & completed)')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info'),

            Stat::make('Submitted Attempts', $submittedAttempts)
                ->description('Completed and graded attempts')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary'),

            Stat::make('Average Score', round($averageScore, 2) . '%')
                ->description('Overall class average')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),
        ];
    }
}
