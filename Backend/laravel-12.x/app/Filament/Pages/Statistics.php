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

    public ?int $selectedCategoryId = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    public function mount(): void
    {
        // Set default date range to last 30 days if not set
        if (!$this->dateFrom) {
            $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        }
        if (!$this->dateTo) {
            $this->dateTo = now()->format('Y-m-d');
        }

        $cards = $this->getCategoryCards();

        if (($this->selectedCategoryId === null) && filled($cards)) {
            $this->selectedCategoryId = $cards[0]['category_id'];
        }
    }

    public function selectCategory(int $categoryId): void
    {
        $this->selectedCategoryId = $categoryId;
    }

    public function updateFilters(): void
    {
        // Validate date range
        $this->validate([
            'dateFrom' => 'nullable|date',
            'dateTo' => 'nullable|date|after_or_equal:dateFrom',
        ], [
            'dateTo.after_or_equal' => 'The end date must be after or equal to the start date.',
        ]);

        // Reset category selection when filters change
        $cards = $this->getCategoryCards();
        if (($this->selectedCategoryId === null || !collect($cards)->pluck('category_id')->contains($this->selectedCategoryId)) && filled($cards)) {
            $this->selectedCategoryId = $cards[0]['category_id'];
        }
    }

    public function updatedDateFrom(): void
    {
        $this->updateFilters();
    }

    public function updatedDateTo(): void
    {
        $this->updateFilters();
    }

    public function getCategoryCards(): array
    {
        return app(QuizStatisticsService::class)->getCategoryCardStatistics($this->dateFrom, $this->dateTo);
    }

    public function getSelectedCategoryDetail(): array
    {
        if (! $this->selectedCategoryId) {
            return [];
        }

        return app(QuizStatisticsService::class)->getCategoryDetailStatistics($this->selectedCategoryId, $this->dateFrom, $this->dateTo);
    }
}
