<?php

namespace App\Filament\Widgets\Traits;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

trait WithWidgetActions
{
    /**
     * Get widget header actions (refresh and delete buttons)
     */
    protected function getWidgetHeaderActions(): array
    {
        return [
            Action::make('refresh-widget')
                ->tooltip('Refresh Widget')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('info')
                ->size('sm')
                ->action(function () {
                    // Dispatch a livewire event to refresh this widget
                    $this->dispatch('refresh-' . static::class);
                })
                ->visible(true),

            Action::make('remove-widget')
                ->tooltip('Remove Widget')
                ->icon(Heroicon::OutlinedXMark)
                ->color('danger')
                ->size('sm')
                ->requiresConfirmation()
                ->modalHeading('Remove Widget')
                ->modalDescription('Are you sure you want to remove this widget from your dashboard?')
                ->modalSubmitActionLabel('Remove')
                ->action(function () {
                    // Remove widget from database
                    $user = auth()->user();
                    if ($user) {
                        $widgetName = class_basename(static::class);
                        $service = app(\App\Services\DashboardWidgetService::class);
                        $service->removeWidget($user, $widgetName);

                        // Dispatch success notification and reload
                        \Filament\Notifications\Notification::make()
                            ->title('Widget Removed')
                            ->body('The widget has been removed from your dashboard.')
                            ->success()
                            ->send();

                        redirect()->to(request()->url());
                    }
                })
                ->visible(true),
        ];
    }

    /**
     * Override getHeaderActions in the widget to include our actions
     * This should be called by the widget's getHeaderActions method
     */
    public function mergeWidgetHeaderActions(): array
    {
        $defaultActions = method_exists($this, 'getHeaderActions') 
            ? $this->getHeaderActions() 
            : [];

        return array_merge($this->getWidgetHeaderActions(), $defaultActions);
    }
}
