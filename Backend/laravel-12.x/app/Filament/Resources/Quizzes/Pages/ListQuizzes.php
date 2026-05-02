<?php

namespace App\Filament\Resources\Quizzes\Pages;

use App\Filament\Resources\Quizzes\QuizResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQuizzes extends ListRecords
{
    protected static string $resource = QuizResource::class;

    protected static ?string $breadcrumb = 'List';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
