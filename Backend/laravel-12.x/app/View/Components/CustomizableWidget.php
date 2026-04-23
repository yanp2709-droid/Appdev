<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CustomizableWidget extends Component
{
    public function __construct(
        public string $widgetClass,
        public string $widgetName,
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.customizable-widget');
    }
}
