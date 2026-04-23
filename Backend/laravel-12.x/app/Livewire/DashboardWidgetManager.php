<?php

namespace App\Livewire;

use App\Services\DashboardWidgetService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class DashboardWidgetManager extends Component
{
    public array $widgets = [];

    public bool $showAddModal = false;

    protected DashboardWidgetService $widgetService;

    public function mount(): void
    {
        $this->widgetService = app(DashboardWidgetService::class);
        $this->loadWidgets();
    }

    public function loadWidgets(): void
    {
        $user = auth()->user();
        if (!$user) {
            return;
        }

        $userWidgets = $this->widgetService->getUserWidgets($user);
        $this->widgets = $userWidgets->toArray();
    }

    public function removeWidget(string $widgetName): void
    {
        $user = auth()->user();
        if (!$user) {
            return;
        }

        try {
            $this->widgetService->removeWidget($user, $widgetName);

            Notification::make()
                ->title('Widget Removed')
                ->body('The widget has been removed from your dashboard.')
                ->success()
                ->send();

            $this->loadWidgets();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Failed to remove widget: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function refreshWidget(string $widgetClass): void
    {
        // Dispatch event to refresh the specific widget
        $this->dispatch('refresh-widget-' . $widgetClass);

        Notification::make()
            ->title('Widget Refreshed')
            ->body('The widget data has been refreshed.')
            ->success()
            ->send();
    }

    public function render(): View
    {
        return view('livewire.dashboard-widget-manager');
    }
}
