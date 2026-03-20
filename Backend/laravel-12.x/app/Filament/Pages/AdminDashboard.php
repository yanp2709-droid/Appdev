<?php

namespace App\Filament\Pages;

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
            DashboardStatsOverview::class,
            StudentInformationWidget::class,
            StudentStatsWidget::class,
            RecentAttemptsWidget::class,
            StudentAttemptHistoryWidget::class,
            StudentPerformanceAnalyticsWidget::class,
            CategoryPerformanceWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return [
            'md' => 2,
            'lg' => 3,
        ];
    }
}
