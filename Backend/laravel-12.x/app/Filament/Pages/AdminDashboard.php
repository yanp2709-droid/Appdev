<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AverageScoreCardWidget;
use App\Filament\Widgets\CategoryPerformanceWidget;
use App\Filament\Widgets\DashboardStatsOverview;
use App\Filament\Widgets\RecentAttemptsWidget;
use App\Filament\Widgets\StudentAttemptHistoryWidget;
use App\Filament\Widgets\StudentInformationWidget;
use App\Filament\Widgets\StudentPerformanceAnalyticsWidget;
use App\Filament\Widgets\StudentStatsWidget;
use Filament\Pages\Dashboard;
use Filament\Support\Icons\Heroicon;

class AdminDashboard extends Dashboard
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $title = 'Dashboard';

    public function getWidgets(): array
    {
        return [
            DashboardStatsOverview::class, // total students, total attempts
            AverageScoreCardWidget::class, // school-year average score card
            StudentInformationWidget::class, // recent students
            CategoryPerformanceWidget::class, // category performance
        ];
    }

    public function getColumns(): int | array
    {
        return [
            'md' => 1,
            'lg' => [
                'DashboardStatsOverview' => 1,
                'StudentInformationWidget' => 2, // make recent students widget larger
                'CategoryPerformanceWidget' => 1,
            ],
        ];
    }
}
