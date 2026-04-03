<?php

namespace App\Filament\Resources\Questions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QuestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('question_text')
                    ->label('Question')
                    ->limit(50)
                    ->searchable()
                    ->wrap(),

                BadgeColumn::make('question_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'mcq' => 'MCQ',
                        'tf' => 'True/False',
                        'multi_select' => 'Multi-Select',
                        'short_answer' => 'Short Answer',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'mcq' => 'blue',
                        'tf' => 'green',
                        'multi_select' => 'warning',
                        'short_answer' => 'orange',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('points')
                    ->label('Points')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name'),

                SelectFilter::make('question_type')
                    ->label('Question Type')
                    ->options([
                        'mcq' => 'Multiple Choice (MCQ)',
                        'tf' => 'True/False',
                        'multi_select' => 'Multi-Select',
                        'short_answer' => 'Short Answer',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
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
