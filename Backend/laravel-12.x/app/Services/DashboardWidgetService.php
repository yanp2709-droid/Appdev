<?php

namespace App\Services;

use App\Filament\Widgets\AverageScoreCardWidget;
use App\Filament\Widgets\CategoryPerformanceWidget;
use App\Filament\Widgets\CategoryStatisticsTableWidget;
use App\Filament\Widgets\DashboardStatsOverview;
use App\Filament\Widgets\DifficultyAnalysisWidget;
use App\Filament\Widgets\PerformanceDistributionWidget;
use App\Filament\Widgets\QuestionBankOverviewWidget;
use App\Filament\Widgets\QuestionTypeDistributionWidget;
use App\Filament\Widgets\RecentAttemptsWidget;
use App\Filament\Widgets\StatisticsOverviewWidget;
use App\Filament\Widgets\StudentAttemptHistoryWidget;
use App\Filament\Widgets\StudentInformationWidget;
use App\Filament\Widgets\StudentPerformanceAnalyticsWidget;
use App\Filament\Widgets\StudentQuizAttemptsTableWidget;
use App\Filament\Widgets\StudentScoreStatsWidget;
use App\Filament\Widgets\StudentStatsWidget;
use App\Models\DashboardWidget;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class DashboardWidgetService
{
    /**
     * All available widgets in the system
     */
    public const AVAILABLE_WIDGETS = [
        'DashboardStatsOverview' => 'App\Filament\Widgets\DashboardStatsOverview',
        'StudentInformationWidget' => 'App\Filament\Widgets\StudentInformationWidget',
        'CategoryPerformanceWidget' => 'App\Filament\Widgets\CategoryPerformanceWidget',
        'RecentAttemptsWidget' => 'App\Filament\Widgets\RecentAttemptsWidget',
        'StudentAttemptHistoryWidget' => 'App\Filament\Widgets\StudentAttemptHistoryWidget',
        'StudentPerformanceAnalyticsWidget' => 'App\Filament\Widgets\StudentPerformanceAnalyticsWidget',
        'StudentStatsWidget' => 'App\Filament\Widgets\StudentStatsWidget',
        'AverageScoreCardWidget' => 'App\Filament\Widgets\AverageScoreCardWidget',
        'CategoryStatisticsTableWidget' => 'App\Filament\Widgets\CategoryStatisticsTableWidget',
        'DifficultyAnalysisWidget' => 'App\Filament\Widgets\DifficultyAnalysisWidget',
        'PerformanceDistributionWidget' => 'App\Filament\Widgets\PerformanceDistributionWidget',
        'QuestionBankOverviewWidget' => 'App\Filament\Widgets\QuestionBankOverviewWidget',
        'QuestionTypeDistributionWidget' => 'App\Filament\Widgets\QuestionTypeDistributionWidget',
        'StatisticsOverviewWidget' => 'App\Filament\Widgets\StatisticsOverviewWidget',
        'StudentQuizAttemptsTableWidget' => 'App\Filament\Widgets\StudentQuizAttemptsTableWidget',
        'StudentScoreStatsWidget' => 'App\Filament\Widgets\StudentScoreStatsWidget',
    ];

    /**
     * Default widgets for new users
     */
    public const DEFAULT_WIDGETS = [
        'DashboardStatsOverview',
        'StudentInformationWidget',
        'CategoryPerformanceWidget',
    ];

    /**
     * Get all available widgets
     */
    public function getAvailableWidgets(): array
    {
        return self::AVAILABLE_WIDGETS;
    }

    /**
     * Get user's dashboard widgets (ordered and visible)
     */
    public function getUserWidgets(User $user): Collection
    {
        return DashboardWidget::where('user_id', $user->id)
            ->where('is_visible', true)
            ->orderBy('order')
            ->get();
    }

    /**
     * Initialize default widgets for user
     */
    public function initializeDefaultWidgets(User $user): void
    {
        $existingWidgets = DashboardWidget::where('user_id', $user->id)->exists();

        if ($existingWidgets) {
            return;
        }

        $order = 0;
        foreach (self::DEFAULT_WIDGETS as $widgetName) {
            DashboardWidget::create([
                'user_id' => $user->id,
                'widget_class' => self::AVAILABLE_WIDGETS[$widgetName],
                'widget_name' => $widgetName,
                'order' => $order++,
                'is_visible' => true,
            ]);
        }
    }

    /**
     * Add a widget to user's dashboard
     */
    public function addWidget(User $user, string $widgetName): DashboardWidget
    {
        if (!isset(self::AVAILABLE_WIDGETS[$widgetName])) {
            throw new \InvalidArgumentException("Widget {$widgetName} not found");
        }

        // Check if widget already exists
        $existing = DashboardWidget::where('user_id', $user->id)
            ->where('widget_class', self::AVAILABLE_WIDGETS[$widgetName])
            ->first();

        if ($existing) {
            $existing->update(['is_visible' => true]);
            return $existing;
        }

        $maxOrder = DashboardWidget::where('user_id', $user->id)->max('order') ?? -1;

        return DashboardWidget::create([
            'user_id' => $user->id,
            'widget_class' => self::AVAILABLE_WIDGETS[$widgetName],
            'widget_name' => $widgetName,
            'order' => $maxOrder + 1,
            'is_visible' => true,
        ]);
    }

    /**
     * Remove a widget from user's dashboard
     */
    public function removeWidget(User $user, string $widgetName): bool
    {
        return (bool) DashboardWidget::where('user_id', $user->id)
            ->where('widget_class', self::AVAILABLE_WIDGETS[$widgetName])
            ->update(['is_visible' => false]);
    }

    /**
     * Delete a widget permanently
     */
    public function deleteWidget(User $user, string $widgetName): bool
    {
        if (!isset(self::AVAILABLE_WIDGETS[$widgetName])) {
            return false;
        }

        return (bool) DashboardWidget::where('user_id', $user->id)
            ->where('widget_class', self::AVAILABLE_WIDGETS[$widgetName])
            ->delete();
    }

    /**
     * Update widget order
     */
    public function updateWidgetOrder(User $user, array $widgetNames): void
    {
        foreach ($widgetNames as $index => $widgetName) {
            if (isset(self::AVAILABLE_WIDGETS[$widgetName])) {
                DashboardWidget::where('user_id', $user->id)
                    ->where('widget_class', self::AVAILABLE_WIDGETS[$widgetName])
                    ->update(['order' => $index]);
            }
        }
    }

    /**
     * Get available widgets that user hasn't added yet
     */
    public function getAvailableWidgetsForUser(User $user): array
    {
        $userWidgets = DashboardWidget::where('user_id', $user->id)
            ->pluck('widget_class')
            ->toArray();

        return array_filter(
            self::AVAILABLE_WIDGETS,
            fn($widgetClass) => !in_array($widgetClass, $userWidgets)
        );
    }
}
