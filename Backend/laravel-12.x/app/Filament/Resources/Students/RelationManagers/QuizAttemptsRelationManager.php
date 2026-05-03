<?php

namespace App\Filament\Resources\Students\RelationManagers;

use App\Models\Quiz_attempt;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class QuizAttemptsRelationManager extends RelationManager
{
    protected static string $relationship = 'quizAttempts';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $title = 'Attempts by Subject';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

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
                    ->label('Subject')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('quiz.title')
                    ->label('Quiz')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category_attempt_number')
                    ->label('Attempt #')
                    ->state(function (Quiz_attempt $record): int {
                        return $this->getAttemptNumber($record);
                    })
                    ->numeric()
                    ->sortable(),

                TextColumn::make('answers_summary')
                    ->label('Answers')
                    ->state(fn (Quiz_attempt $record): HtmlString => $this->formatAnswers($record))
                    ->html()
                    ->wrap(),

                TextColumn::make('score_percent')
                    ->label('Score')
                    ->state(function (Quiz_attempt $record): string {
                        $score = $record->score ?? 0;
                        $total = $record->total_items ?? 0;
                        $percent = is_numeric($record->score_percent) ? round((float) $record->score_percent, 2) . '%' : 'N/A';

                        return $total > 0
                            ? "{$score}/{$total} ({$percent})"
                            : $percent;
                    })
                    ->color(fn (Quiz_attempt $record) => is_numeric($record->score_percent)
                        ? ($record->score_percent >= 70 ? 'success' : ($record->score_percent >= 50 ? 'warning' : 'danger'))
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
                        return 'Attempt Details - ' . ($record->quiz->category->name ?? 'Unknown Subject');
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

    protected function getAttemptNumber(Quiz_attempt $record): int
    {
        return Quiz_attempt::query()
            ->where('student_id', $record->student_id)
            ->whereHas('quiz', fn ($query) => $query->where('category_id', $record->quiz?->category_id))
            ->where('id', '<=', $record->id)
            ->count();
    }

    protected function formatAnswers(Quiz_attempt $record): HtmlString
    {
        $lines = $record->answers
            ->sortBy('question_id')
            ->values()
            ->map(function ($answer, $index) {
                $value = $answer->questionOption?->option_text
                    ?? $answer->answer?->answer_text
                    ?? $answer->text_answer
                    ?? 'Skipped';

                $label = 'Q' . ($index + 1) . ': ' . $value;

                return e($label);
            });

        if ($lines->isEmpty()) {
            return new HtmlString('<span class="text-gray-500">Skipped</span>');
        }

        return new HtmlString($lines->implode('<br>'));
    }
}
