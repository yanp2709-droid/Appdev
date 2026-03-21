<?php

namespace App\Filament\Resources\Students\RelationManagers;

use App\Models\Quiz_attempt;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuizAttemptsRelationManager extends RelationManager
{
    protected static string $relationship = 'quizAttempts';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $title = 'Attempts by Category';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // This is read-only, so no form fields needed
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->getRelationship()
                    ->with(['quiz.category', 'answers.question.options', 'answers.questionOption', 'answers.answer'])
                    ->orderByDesc('id')
            )
            ->columns([
                TextColumn::make('quiz.category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category_attempt_number')
                    ->label('Attempt #')
                    ->state(function (Quiz_attempt $record): int {
                        return Quiz_attempt::query()
                            ->where('student_id', $record->student_id)
                            ->whereHas('quiz', fn ($query) => $query->where('category_id', $record->quiz?->category_id))
                            ->where('id', '<=', $record->id)
                            ->count();
                    })
                    ->numeric()
                    ->sortable(),

                TextColumn::make('score_percent')
                    ->label('Category Score')
                    ->formatStateUsing(fn ($state) => is_numeric($state) ? round($state, 2) . '%' : 'N/A')
                    ->numeric()
                    ->color(fn ($state) => is_numeric($state)
                        ? ($state >= 70 ? 'success' : ($state >= 50 ? 'warning' : 'danger'))
                        : 'gray')
                    ->sortable(),

                TextColumn::make('answered_items')
                    ->label('Answered Items')
                    ->state(fn (Quiz_attempt $record): string => ($record->answered_count ?? 0) . '/' . ($record->total_items ?? 0)),
            ])
            ->actions([
                Tables\Actions\Action::make('viewDetails')
                    ->label('View Details')
                    ->icon('heroicon-m-eye')
                    ->modalHeading(function (Quiz_attempt $record) {
                        return 'Attempt Details - ' . ($record->quiz->category->name ?? 'Unknown Category');
                    })
                    ->modalContent(function (Quiz_attempt $record) {
                        return view('filament.resources.students.quiz-attempt-details', [
                            'attempt' => $record,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalSize('lg')
                    ->modalCancelActionLabel('Close'),
            ])
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc');
    }
}
