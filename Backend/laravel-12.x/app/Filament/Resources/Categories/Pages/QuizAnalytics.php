<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;

class QuizAnalytics extends \App\Filament\Pages\Statistics
{
    protected static string $resource = CategoryResource::class;
    protected static ?string $title = 'Quiz Analytics';
    protected string $view = 'filament.resources.categories.pages.quiz-analytics';
}
