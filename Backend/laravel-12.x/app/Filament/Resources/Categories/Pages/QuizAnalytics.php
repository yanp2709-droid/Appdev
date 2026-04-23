<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\Page;

class QuizAnalytics extends Page
{
    protected static string $resource = CategoryResource::class;
    protected static ?string $title = 'Quiz Analytics';
    protected string $view = 'filament.resources.categories.pages.quiz-analytics';
}
