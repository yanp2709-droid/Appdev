<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected string $view = 'filament.resources.categories.pages.list-categories';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getCategories(): \Illuminate\Support\Collection
    {
        return Category::query()
            ->withCount('questions')
            ->orderBy('name')
            ->get();
    }

    public function deleteCategory(int $categoryId): void
    {
        $category = Category::query()->find($categoryId);

        if (! $category) {
            return;
        }

        $categoryName = $category->name;

        $category->delete();

        Notification::make()
            ->title('Category deleted')
            ->body("{$categoryName} was deleted successfully.")
            ->success()
            ->send();
    }
}
