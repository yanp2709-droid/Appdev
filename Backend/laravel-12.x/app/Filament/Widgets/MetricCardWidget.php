<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithMetricCard;
use Filament\Widgets\Widget;

abstract class MetricCardWidget extends Widget
{
    use InteractsWithMetricCard;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 3;
}
