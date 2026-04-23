<?php

namespace App\Filament\Widgets\Traits;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

trait RefreshableWidget
{
    /**
     * Get the actions for the widget header
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->tooltip('Refresh widget')
                ->icon(Heroicon::OutlinedArrowPath)
                ->action(fn() => $this->dispatch('refresh-widget', widget: static::class))
                ->button(),
            Action::make('remove')
                ->tooltip('Remove widget')
                ->icon(Heroicon::OutlinedXMark)
                ->color('danger')
                ->action(fn() => $this->dispatch('remove-widget', widget: static::class))
                ->button(),
        ];
    }
}
