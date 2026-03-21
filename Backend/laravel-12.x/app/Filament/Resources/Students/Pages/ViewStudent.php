<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Models\Quiz_attempt;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\HtmlString;

class ViewStudent extends ViewRecord
{
    protected static string $resource = StudentResource::class;

    public function infolist(Schema $schema): Schema
    {
        $student = $this->record;

        $attempts = Quiz_attempt::query()
            ->where('student_id', $student->id)
            ->with(['quiz.category', 'answers.question.options', 'answers.questionOption', 'answers.answer'])
            ->orderByDesc('id')
            ->get();

        $attemptSummaryHtml = $attempts
            ->groupBy(fn (Quiz_attempt $attempt) => $attempt->quiz?->category?->name ?? 'Uncategorized')
            ->map(function ($attempts, string $category): string {
                return '<div><strong>' . e($category) . ':</strong> ' . $attempts->count() . ' attempt(s)</div>';
            })
            ->implode('');

        $attemptNumbers = [];
        $attempts
            ->groupBy(fn (Quiz_attempt $attempt) => $attempt->quiz?->category_id ?? 'uncategorized')
            ->each(function ($group) use (&$attemptNumbers): void {
                $group
                    ->sortBy('id')
                    ->values()
                    ->each(function (Quiz_attempt $attempt, int $index) use (&$attemptNumbers): void {
                        $attemptNumbers[$attempt->id] = $index + 1;
                    });
            });

        $attemptDetailsHtml = $attempts->map(function (Quiz_attempt $attempt) use ($attemptNumbers): string {
            $categoryName = $attempt->quiz?->category?->name ?? 'Uncategorized';
            $attemptNo = $attemptNumbers[$attempt->id] ?? 1;
            $answeredItems = (int) ($attempt->answered_count ?? 0);
            $totalItems = (int) ($attempt->total_items ?? 0);
            $score = is_numeric($attempt->score_percent) ? round((float) $attempt->score_percent, 2) . '%' : 'N/A';

            $answerRows = $attempt->answers
                ->sortBy('question_id')
                ->values()
                ->map(function ($answer, $index): string {
                    $studentAnswer = $answer->questionOption?->option_text
                        ?? $answer->answer?->answer_text
                        ?? $answer->text_answer
                        ?? 'No answer';

                    $correctOptionTexts = $answer->question?->options
                        ?->where('is_correct', true)
                        ->pluck('option_text')
                        ->filter()
                        ->values();

                    $correctAnswer = ($correctOptionTexts && $correctOptionTexts->isNotEmpty())
                        ? $correctOptionTexts->implode(', ')
                        : ($answer->question?->answer_key ?? 'N/A');

                    return '<tr>'
                        . '<td style="padding:6px;border:1px solid #e5e7eb;">' . ($index + 1) . '</td>'
                        . '<td style="padding:6px;border:1px solid #e5e7eb;">' . e((string) $answer->question_id) . '</td>'
                        . '<td style="padding:6px;border:1px solid #e5e7eb;">' . e((string) $studentAnswer) . '</td>'
                        . '<td style="padding:6px;border:1px solid #e5e7eb;">' . e((string) $correctAnswer) . '</td>'
                        . '</tr>';
                })
                ->implode('');

            if ($answerRows === '') {
                $answerRows = '<tr><td colspan="4" style="padding:8px;border:1px solid #e5e7eb;">No answers submitted.</td></tr>';
            }

            return '<div style="margin-bottom:16px;padding:12px;border:1px solid #e5e7eb;border-radius:8px;">'
                . '<div style="margin-bottom:8px;"><strong>Category:</strong> ' . e($categoryName) . '</div>'
                . '<div style="margin-bottom:8px;"><strong>Attempt #:</strong> ' . $attemptNo . '</div>'
                . '<div style="margin-bottom:8px;"><strong>Answered Items:</strong> ' . $answeredItems . '/' . $totalItems . '</div>'
                . '<div style="margin-bottom:10px;"><strong>Score:</strong> ' . e($score) . '</div>'
                . '<table style="width:100%;border-collapse:collapse;">'
                . '<thead><tr>'
                . '<th style="padding:6px;border:1px solid #e5e7eb;text-align:left;">Item #</th>'
                . '<th style="padding:6px;border:1px solid #e5e7eb;text-align:left;">Question ID</th>'
                . '<th style="padding:6px;border:1px solid #e5e7eb;text-align:left;">Student Answer</th>'
                . '<th style="padding:6px;border:1px solid #e5e7eb;text-align:left;">Correct Answer</th>'
                . '</tr></thead>'
                . '<tbody>' . $answerRows . '</tbody>'
                . '</table>'
                . '</div>';
        })->implode('');

        return $schema
            ->schema([
                Section::make('Student Information')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Full Name')
                            ->weight(FontWeight::Bold)
                            ->columnSpanFull(),

                        TextEntry::make('email')
                            ->label('Email Address')
                            ->copyable()
                            ->icon('heroicon-m-envelope'),

                        TextEntry::make('student_id')
                            ->label('Student ID')
                            ->icon('heroicon-m-identification')
                            ->default('N/A'),

                        TextEntry::make('section')
                            ->label('Section')
                            ->icon('heroicon-m-squares-2x2')
                            ->default('N/A'),

                        TextEntry::make('year_level')
                            ->label('Year Level')
                            ->icon('heroicon-m-academic-cap')
                            ->default('N/A'),

                        TextEntry::make('course')
                            ->label('Course')
                            ->icon('heroicon-m-book-open')
                            ->default('N/A')
                            ->columnSpanFull(),
                    ]),

                Section::make('Attempt Count Per Category')
                    ->columns(1)
                    ->schema([
                        TextEntry::make('attempts_per_category')
                            ->label('Attempts')
                            ->state(new HtmlString($attemptSummaryHtml !== '' ? $attemptSummaryHtml : '<div>No attempts yet.</div>'))
                            ->html()
                            ->columnSpanFull(),
                    ]),

                Section::make('Attempts and Answers')
                    ->columns(1)
                    ->schema([
                        TextEntry::make('attempt_details')
                            ->label('Attempt Records')
                            ->state(new HtmlString($attemptDetailsHtml !== '' ? $attemptDetailsHtml : '<div>No attempts yet.</div>'))
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
