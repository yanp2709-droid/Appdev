<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\AdminDashboard;
use App\Models\Quiz_attempt;
use App\Models\User;
use App\Services\AcademicYearService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class StudentQuizAttemptsTableWidget extends BaseWidget
{
    protected static ?string $heading = 'Quiz Attempts';

    protected int | string | array $columnSpan = 'full';

    public ?User $record = null;

    protected $listeners = ['academicYearChanged' => '$refresh'];

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('quiz.category.name')
                    ->label('Subject')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('quiz.title')
                    ->label('Quiz')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('answers_summary')
                    ->label('Answers')
                    ->state(fn (Quiz_attempt $record): string => $this->formatAnswers($record))
                    ->wrap(),

                TextColumn::make('attempt_number')
                    ->label('Attempt #')
                    ->state(fn (Quiz_attempt $record): int => $this->getAttemptNumber($record))
                    ->sortable(false),

                TextColumn::make('score_summary')
                    ->label('Score')
                    ->state(fn (Quiz_attempt $record): string => $this->formatScore($record)),

                TextColumn::make('answered_items')
                    ->label('Answered Items')
                    ->state(fn (Quiz_attempt $record): string => ($record->answered_count ?? 0) . '/' . ($record->total_items ?? 0)),
            ])
            ->defaultSort('id', 'desc')
            ->paginated([5, 10, 25]);
    }

    protected function getTableQuery(): Builder
    {
        $studentId = $this->record?->id ?? 0;
        $academicYear = AdminDashboard::getSelectedAcademicYear();
        [$startDate, $endDate] = app(AcademicYearService::class)->getDateRange($academicYear);

        return Quiz_attempt::query()
            ->with(['quiz.category', 'answers.questionOption', 'answers.answer'])
            ->where('student_id', $studentId)
            ->when(
                Schema::hasColumn('quiz_attempts', 'school_year'),
                fn ($query) => $query->where('school_year', $academicYear),
                fn ($query) => $query->whereBetween('submitted_at', [$startDate, $endDate]),
            )
            ->orderByDesc('id');
    }

    protected function formatAnswers(Quiz_attempt $record): string
    {
        $answers = $record->answers
            ->sortBy('question_id')
            ->values()
            ->map(function ($answer, $index) {
                $value = $answer->questionOption?->option_text
                    ?? $answer->answer?->answer_text
                    ?? $answer->text_answer
                    ?? 'Skipped';

                return 'Q' . ($index + 1) . ': ' . $value;
            })
            ->implode(', ');

        return $answers !== '' ? $answers : 'Skipped';
    }

    protected function getAttemptNumber(Quiz_attempt $record): int
    {
        $academicYear = AdminDashboard::getSelectedAcademicYear();
        [$startDate, $endDate] = app(AcademicYearService::class)->getDateRange($academicYear);

        return Quiz_attempt::query()
            ->where('student_id', $record->student_id)
            ->whereHas('quiz', fn ($query) => $query->where('category_id', $record->quiz?->category_id))
            ->when(
                Schema::hasColumn('quiz_attempts', 'school_year'),
                fn ($query) => $query->where('school_year', $academicYear),
                fn ($query) => $query->whereBetween('submitted_at', [$startDate, $endDate]),
            )
            ->where('id', '<=', $record->id)
            ->count();
    }

    protected function formatScore(Quiz_attempt $record): string
    {
        $score = $record->score ?? 0;
        $total = $record->total_items ?? 0;
        $percent = is_numeric($record->score_percent) ? number_format((float) $record->score_percent, 2) . '%' : 'N/A';

        return $total > 0
            ? "{$score}/{$total} ({$percent})"
            : $percent;
    }
}
