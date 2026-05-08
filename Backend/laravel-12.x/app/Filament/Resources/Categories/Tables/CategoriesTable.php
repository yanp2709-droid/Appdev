<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Quiz Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->wrap(),

                BooleanColumn::make('is_published')
                    ->label('Published')
                    ->sortable(),

                TextColumn::make('time_limit_minutes')
                    ->label('Time Limit (Min)')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_published')
                    ->label('Published Status')
                    ->placeholder('All quizzes')
                    ->trueLabel('Published only')
                    ->falseLabel('Unpublished only'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('quizzes')
                    ->label('Open')
                    ->url(fn ($record) => CategoryResource::getUrl('quizzes', ['record' => $record])),
                EditAction::make(),
            ]);
    }
}
