<?php

namespace App\Services;

use App\Filament\Widgets\AverageScoreCardWidget;
use App\Filament\Widgets\CategoryPerformanceWidget;
use App\Filament\Widgets\StudentInformationWidget;
use App\Filament\Widgets\SubmittedAttemptsWidget;
use App\Filament\Widgets\TotalAttemptsWidget;
use App\Filament\Widgets\TotalStudentsWidget;
use App\Models\DashboardWidget;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;

class DashboardWidgetService
{
    private const TABLE_NAME = 'dashboard_widgets';

    public const AVAILABLE_WIDGETS = [
        'TotalStudentsWidget' => TotalStudentsWidget::class,
        'TotalAttemptsWidget' => TotalAttemptsWidget::class,
        'SubmittedAttemptsWidget' => SubmittedAttemptsWidget::class,
        'AverageScoreCardWidget' => AverageScoreCardWidget::class,
        'StudentInformationWidget' => StudentInformationWidget::class,
        'CategoryPerformanceWidget' => CategoryPerformanceWidget::class,
    ];

    public const DEFAULT_WIDGETS = [
        'TotalStudentsWidget',
    ];

    public function getAvailableWidgets(): array
    {
        return self::AVAILABLE_WIDGETS;
    }

    public function isStorageReady(): bool
    {
        return Schema::hasTable(self::TABLE_NAME);
    }

    public function getUserWidgets(User $user): Collection
    {
        if (! $this->isStorageReady()) {
            return new Collection();
        }

        return DashboardWidget::where('user_id', $user->id)
            ->where('is_visible', true)
            ->orderBy('order')
            ->get();
    }

    public function initializeDefaultWidgets(User $user): void
    {
        if (! $this->isStorageReady()) {
            return;
        }

        if (DashboardWidget::where('user_id', $user->id)->exists()) {
            return;
        }

        foreach (array_keys(self::AVAILABLE_WIDGETS) as $order => $widgetName) {
            DashboardWidget::create([
                'user_id' => $user->id,
                'widget_class' => self::AVAILABLE_WIDGETS[$widgetName],
                'widget_name' => $widgetName,
                'order' => $order,
                'is_visible' => false,
            ]);
        }
    }

    /**
     * Reset all widgets back into the collection sidebar.
     */
    public function resetWidgetCollection(User $user): void
    {
        if (! $this->isStorageReady()) {
            return;
        }

        foreach (array_keys(self::AVAILABLE_WIDGETS) as $order => $widgetName) {
            $existing = DashboardWidget::where('user_id', $user->id)
                ->where('widget_class', self::AVAILABLE_WIDGETS[$widgetName])
                ->first();

            if (! $existing) {
                DashboardWidget::create([
                    'user_id' => $user->id,
                    'widget_class' => self::AVAILABLE_WIDGETS[$widgetName],
                    'widget_name' => $widgetName,
                    'order' => $order,
                    'is_visible' => false,
                ]);
            }
        }
    }

    public function addWidget(User $user, string $widgetName): DashboardWidget
    {
        if (! $this->isStorageReady()) {
            throw new \RuntimeException('Dashboard widget storage is not ready. Run the latest migrations.');
        }

        if (! isset(self::AVAILABLE_WIDGETS[$widgetName])) {
            throw new \InvalidArgumentException("Widget {$widgetName} not found");
        }

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

    public function removeWidget(User $user, string $widgetName): bool
    {
        if (! $this->isStorageReady()) {
            return false;
        }

        return (bool) DashboardWidget::where('user_id', $user->id)
            ->where('widget_class', self::AVAILABLE_WIDGETS[$widgetName] ?? null)
            ->update(['is_visible' => false]);
    }

    public function updateWidgetOrder(User $user, array $widgetNames): void
    {
        if (! $this->isStorageReady()) {
            return;
        }

        foreach ($widgetNames as $index => $widgetName) {
            if (! isset(self::AVAILABLE_WIDGETS[$widgetName])) {
                continue;
            }

            DashboardWidget::where('user_id', $user->id)
                ->where('widget_class', self::AVAILABLE_WIDGETS[$widgetName])
                ->update(['order' => $index]);
        }
    }

    public function getAvailableWidgetsForUser(User $user): array
    {
        if (! $this->isStorageReady()) {
            return self::AVAILABLE_WIDGETS;
        }

        $visibleClasses = DashboardWidget::where('user_id', $user->id)
            ->where('is_visible', true)
            ->pluck('widget_class')
            ->toArray();

        return array_filter(
            self::AVAILABLE_WIDGETS,
            fn (string $widgetClass) => ! in_array($widgetClass, $visibleClasses, true),
        );
    }
}
