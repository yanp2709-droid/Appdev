<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AverageScoreCardWidget;
use App\Filament\Widgets\CategoryPerformanceWidget;
use App\Filament\Widgets\StudentInformationWidget;
use App\Filament\Widgets\SubmittedAttemptsWidget;
use App\Filament\Widgets\TotalAttemptsWidget;
use App\Filament\Widgets\TotalStudentsWidget;
use App\Models\DashboardWidget;
use App\Models\User;
use App\Services\DashboardWidgetService;
use BackedEnum;
use Filament\Pages\Dashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class AdminDashboard extends Dashboard
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $title = 'Dashboard';

    protected string $view = 'filament.pages.admin-dashboard';

    public bool $widgetDrawerOpen = false;

    public function mount(): void
    {
        if ($user = auth()->user()) {
            $this->widgetService()->initializeDefaultWidgets($user);

            if (! session()->has('dashboard_widget_collection_seeded')) {
                $this->widgetService()->resetWidgetCollection($user);
                session()->put('dashboard_widget_collection_seeded', true);
            }
        }
    }

    public function getDashboardWidgets(): Collection
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return collect();
        }

        return DashboardWidget::query()
            ->where('user_id', $user->id)
            ->where('is_visible', true)
            ->whereIn('widget_class', array_values($this->getWidgetClasses()))
            ->orderBy('order')
            ->get();
    }

    public function getActiveWidgetClasses(): array
    {
        return $this->getDashboardWidgets()
            ->pluck('widget_class')
            ->all();
    }

    public function getWidgetCatalog(): array
    {
        return $this->getDashboardWidgetCatalog();
    }

    /**
     * @return array<int, class-string>
     */
    public function getWidgetClasses(): array
    {
        return array_map(
            fn (array $widget) => $widget['class'],
            $this->getDashboardWidgetCatalog(),
        );
    }

    protected function getWidgetNameForClass(string $widgetClass): ?string
    {
        foreach ($this->getDashboardWidgetCatalog() as $widgetName => $widget) {
            if ($widget['class'] === $widgetClass) {
                return $widgetName;
            }
        }

        return null;
    }

    public function addWidget(string $widgetName): void
    {
        $this->placeWidget($widgetName);
    }

    public function removeWidget(string $widgetName): void
    {
        $user = auth()->user();

        if (! $user instanceof User || ! array_key_exists($widgetName, $this->getDashboardWidgetCatalog())) {
            return;
        }

        $this->widgetService()->removeWidget($user, $widgetName);
    }

    public function placeWidget(string $widgetName, ?string $targetWidgetName = null, string $position = 'after'): void
    {
        $user = auth()->user();

        if (! $user instanceof User || ! array_key_exists($widgetName, $this->getDashboardWidgetCatalog())) {
            return;
        }

        $service = $this->widgetService();
        $service->addWidget($user, $widgetName);

        $widgetOrder = $this->getDashboardWidgets()
            ->map(fn (DashboardWidget $widget) => $this->getWidgetNameForClass($widget->widget_class))
            ->filter()
            ->values()
            ->all();

        $widgetOrder = array_values(array_filter(
            $widgetOrder,
            fn (string $name) => $name !== $widgetName,
        ));

        if ($targetWidgetName && in_array($targetWidgetName, $widgetOrder, true)) {
            $targetIndex = array_search($targetWidgetName, $widgetOrder, true);

            if ($targetIndex !== false) {
                $insertIndex = $position === 'before' ? $targetIndex : $targetIndex + 1;
                array_splice($widgetOrder, $insertIndex, 0, [$widgetName]);
            } else {
                $widgetOrder[] = $widgetName;
            }
        } else {
            $widgetOrder[] = $widgetName;
        }

        $service->updateWidgetOrder($user, $widgetOrder);
    }

    public function refreshWidget(string $widgetName): void
    {
        if (! array_key_exists($widgetName, $this->getDashboardWidgetCatalog())) {
            return;
        }

        $this->dispatch('refresh-page');
    }

    /**
     * @param array<int, string> $widgetNames
     */
    public function reorderWidgets(array $widgetNames): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $filteredWidgetNames = array_values(array_filter(
            $widgetNames,
            fn (string $widgetName) => array_key_exists($widgetName, $this->getDashboardWidgetCatalog()),
        ));

        $this->widgetService()->updateWidgetOrder($user, $filteredWidgetNames);
    }

    public function toggleWidgetDrawer(): void
    {
        $this->widgetDrawerOpen = ! $this->widgetDrawerOpen;
    }

    public function getWidgetSpanClass(string $widgetName): string
    {
        return match ($widgetName) {
            'TotalStudentsWidget' => 'xl:col-span-6',
            'TotalAttemptsWidget' => 'xl:col-span-6',
            'SubmittedAttemptsWidget' => 'xl:col-span-6',
            'AverageScoreCardWidget' => 'xl:col-span-6',
            'StudentInformationWidget' => 'xl:col-span-6',
            'CategoryPerformanceWidget' => 'xl:col-span-6',
            default => 'xl:col-span-12',
        };
    }

    /**
     * @return array<string, array{label: string, description: string, class: class-string}>
     */
    protected function getDashboardWidgetCatalog(): array
    {
        return [
            'TotalStudentsWidget' => [
                'label' => 'Total Students',
                'description' => 'Active student accounts.',
                'class' => TotalStudentsWidget::class,
            ],
            'TotalAttemptsWidget' => [
                'label' => 'Total Attempts',
                'description' => 'All quiz attempts.',
                'class' => TotalAttemptsWidget::class,
            ],
            'SubmittedAttemptsWidget' => [
                'label' => 'Submitted Attempts',
                'description' => 'Completed and graded attempts.',
                'class' => SubmittedAttemptsWidget::class,
            ],
            'AverageScoreCardWidget' => [
                'label' => 'Average Score',
                'description' => 'Academic year score average.',
                'class' => AverageScoreCardWidget::class,
            ],
            'StudentInformationWidget' => [
                'label' => 'Recent Registered Students',
                'description' => 'Recently registered students and their account status.',
                'class' => StudentInformationWidget::class,
            ],
            'CategoryPerformanceWidget' => [
                'label' => 'Quiz Performance',
                'description' => 'Quiz performance chart with date filters.',
                'class' => CategoryPerformanceWidget::class,
            ],
        ];
    }

    protected function widgetService(): DashboardWidgetService
    {
        return app(DashboardWidgetService::class);
    }
}
