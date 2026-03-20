<?php

namespace App\Filament\Resources\Students\RelationManagers;

use App\Models\Quiz_attempt;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuizAttemptsRelationManager extends RelationManager
{
    protected static string $relationship = 'quizAttempts';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $title = 'Quiz Attempts';

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
                    ->with(['quiz.category'])
                    ->orderByDesc('started_at')
            )
            ->columns([
                TextColumn::make('quiz.title')
                    ->label('Quiz Title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('quiz.category.name')
                    ->label('Category')
                    ->sortable(),

                TextColumn::make('started_at')
                    ->label('Started')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),

                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),

                TextColumn::make('total_items')
                    ->label('Total Questions')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('answered_count')
                    ->label('Answered')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('correct_answers')
                    ->label('Correct')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('score_percent')
                    ->label('Score')
                    ->formatStateUsing(fn ($state) => is_numeric($state) ? round($state, 2) . '%' : 'N/A')
                    ->sortable()
                    ->color(fn ($state) => is_numeric($state)
                        ? ($state >= 70 ? 'success' : ($state >= 50 ? 'warning' : 'danger'))
                        : 'gray'),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state)))
                    ->colors([
                        'success' => 'submitted',
                        'warning' => 'in_progress',
                        'danger' => 'expired',
                        'gray' => 'other',
                    ])
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'submitted' => 'Submitted',
                        'in_progress' => 'In Progress',
                        'expired' => 'Expired',
                    ]),

                Tables\Filters\SelectFilter::make('quiz.category.id')
                    ->label('Category')
                    ->relationship('quiz.category', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('viewDetails')
                    ->label('View Details')
                    ->icon('heroicon-m-eye')
                    ->modalHeading(function (Quiz_attempt $record) {
                        return 'Quiz Attempt Details - ' . ($record->quiz->title ?? 'Untitled');
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
            ->defaultSort('started_at', 'desc');
    }
}
