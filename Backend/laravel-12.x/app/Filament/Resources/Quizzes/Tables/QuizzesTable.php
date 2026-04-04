<?php

namespace App\Filament\Resources\Quizzes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QuizzesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),
                TextColumn::make('teacher.name')
                    ->label('Teacher')
                    ->sortable(),
                TextColumn::make('difficulty')
                    ->sortable(),
                TextColumn::make('duration_minutes')
                    ->label('Minutes')
                    ->sortable(),
                TextColumn::make('max_attempts')
                    ->label('Attempt Limit')
                    ->placeholder('Unlimited'),
                IconColumn::make('timer_enabled')
                    ->boolean(),
                IconColumn::make('show_answers_after_submit')
                    ->label('Answer Review')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('difficulty')
                    ->options([
                        'Easy' => 'Easy',
                        'Medium' => 'Medium',
                        'Hard' => 'Hard',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
