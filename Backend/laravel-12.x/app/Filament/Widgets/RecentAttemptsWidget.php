<?php

namespace App\Filament\Widgets;

use App\Models\Quiz_attempt;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentAttemptsWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Quiz Attempts';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Quiz_attempt::query()
                    ->with(['student:id,name,email', 'quiz.category'])
                    ->where('status', 'submitted')
                    ->orderByDesc('submitted_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('quiz.category.name')
                    ->label('Category')
                    ->sortable(),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_items')
                    ->label('Total Questions')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('correct_answers')
                    ->label('Correct Answers')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('score_percent')
                    ->label('Score')
                    ->formatStateUsing(fn ($state) => round($state, 2) . '%')
                    ->sortable()
                    ->color(fn ($state) => $state >= 70 ? 'success' : ($state >= 50 ? 'warning' : 'danger')),
            ]);
    }
}
