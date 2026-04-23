<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Traits\WithWidgetActions;
use Filament\Widgets\Widget;

/**
 * Base class for all customizable dashboard widgets
 * Automatically includes refresh and remove actions
 */
abstract class CustomizableWidget extends Widget
{
    use WithWidgetActions;

    protected static ?int $sort = null;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 1;

    /**
     * Get widget actions including refresh and remove buttons
     */
    protected function getHeaderActions(): array
    {
        return $this->mergeWidgetHeaderActions();
    }
}
