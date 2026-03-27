<?php

namespace App\Filament\Pages;

use App\Services\QuizStatisticsService;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Statistics extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Statistics';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Quiz Statistics & Analytics';

    protected string $view = 'filament.pages.statistics';

    public ?int $selectedCategoryId = null;

    public function mount(): void
    {
        $cards = $this->getCategoryCards();

        if (($this->selectedCategoryId === null) && filled($cards)) {
            $this->selectedCategoryId = $cards[0]['category_id'];
        }
    }

    public function selectCategory(int $categoryId): void
    {
        $this->selectedCategoryId = $categoryId;
    }

    public function getCategoryCards(): array
    {
        return app(QuizStatisticsService::class)->getCategoryCardStatistics();
    }

    public function getSelectedCategoryDetail(): array
    {
        if (! $this->selectedCategoryId) {
            return [];
        }

        return app(QuizStatisticsService::class)->getCategoryDetailStatistics($this->selectedCategoryId);
    }
}
