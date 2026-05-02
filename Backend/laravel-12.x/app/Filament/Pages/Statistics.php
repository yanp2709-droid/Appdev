<?php

namespace App\Filament\Pages;

use App\Services\QuizStatisticsService;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Statistics extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Overall Analytics';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Quiz Overall Analytics';

    protected string $view = 'filament.pages.statistics';

    protected $listeners = ['academicYearChanged' => '$refresh'];

    public function getQuizCards(): array
    {
        return app(QuizStatisticsService::class)->getCategoryCardStatistics();
    }

    public function getOverallQuizSummary(): array
    {
        return app(QuizStatisticsService::class)->getOverallStatistics();
    }
}
