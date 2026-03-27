<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CategoryStatisticsTableWidget extends BaseWidget
{
    protected static ?string $heading = 'Category Statistics';

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('name')
                    ->label('Quiz Category')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('highest_score')
                    ->label('High Score')
                    ->formatStateUsing(fn ($state): string => number_format((float) ($state ?? 0), 2) . '%')
                    ->sortable(),

                TextColumn::make('lowest_score')
                    ->label('Lowest Score')
                    ->formatStateUsing(fn ($state): string => number_format((float) ($state ?? 0), 2) . '%')
                    ->sortable(),

                TextColumn::make('completion_rate')
                    ->label('Completion Rate')
                    ->formatStateUsing(fn ($state): string => number_format((float) ($state ?? 0), 2) . '%')
                    ->sortable(),

                TextColumn::make('in_progress_attempts')
                    ->label('In Progress')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('expired_attempts')
                    ->label('Expired Attempts')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->paginated([10, 25, 50]);
    }

    protected function getTableQuery(): Builder
    {
        return Category::query()
            ->join('quizzes', 'quizzes.category_id', '=', 'categories.id')
            ->join('quiz_attempts', 'quiz_attempts.quiz_id', '=', 'quizzes.id')
            ->select([
                'categories.id',
                'categories.name',
                DB::raw('MAX(CASE WHEN quiz_attempts.status = "submitted" THEN quiz_attempts.score_percent ELSE NULL END) as highest_score'),
                DB::raw('MIN(CASE WHEN quiz_attempts.status = "submitted" THEN quiz_attempts.score_percent ELSE NULL END) as lowest_score'),
                DB::raw('ROUND((SUM(CASE WHEN quiz_attempts.status = "submitted" THEN 1 ELSE 0 END) / COUNT(quiz_attempts.id)) * 100, 2) as completion_rate'),
                DB::raw('SUM(CASE WHEN quiz_attempts.status = "in_progress" THEN 1 ELSE 0 END) as in_progress_attempts'),
                DB::raw('SUM(CASE WHEN quiz_attempts.status = "expired" THEN 1 ELSE 0 END) as expired_attempts'),
            ])
            ->groupBy('categories.id', 'categories.name')
            ->havingRaw('COUNT(quiz_attempts.id) > 0');
    }
}
