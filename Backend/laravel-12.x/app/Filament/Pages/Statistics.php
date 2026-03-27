<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardStatsOverview;
use App\Filament\Widgets\StudentPerformanceAnalyticsWidget;
use App\Filament\Widgets\CategoryPerformanceWidget;
use App\Models\Quiz_attempt;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;

class Statistics extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Statistics';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Quiz Statistics & Analytics';

    public function getWidgets(): array
    {
        return [
            DashboardStatsOverview::class,
            CategoryPerformanceWidget::class,
            StudentPerformanceAnalyticsWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return [
            'md' => 1,
            'lg' => [
                'DashboardStatsOverview' => 1,
                'CategoryPerformanceWidget' => 1,
                'StudentPerformanceAnalyticsWidget' => 'full',
            ],
        ];
    }
}
