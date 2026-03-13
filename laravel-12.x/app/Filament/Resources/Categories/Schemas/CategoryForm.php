<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Category Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g., Programming Basics'),

                Textarea::make('description')
                    ->label('Description')
                    ->nullable()
                    ->rows(4)
                    ->placeholder('Describe the category...'),

                TextInput::make('time_limit_minutes')
                    ->label('Time Limit (Minutes)')
                    ->numeric()
                    ->minValue(1)
                    ->default(15)
                    ->helperText('Quiz duration for this category'),

                Toggle::make('is_published')
                    ->label('Published')
                    ->default(true)
                    ->helperText('Make this category visible to students'),
            ]);
    }
}
