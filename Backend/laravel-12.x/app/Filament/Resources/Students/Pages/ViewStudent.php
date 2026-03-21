<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Models\Quiz_attempt;
use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class ViewStudent extends ViewRecord
{
    protected static string $resource = StudentResource::class;

    public function infolist(Schema $schema): Schema
    {
        $student = $this->record;

        $attempts = Quiz_attempt::where('student_id', $student->id)
            ->where('status', 'submitted')
            ->get();

        $totalAttempts = Quiz_attempt::where('student_id', $student->id)->count();
        $submittedAttempts = $attempts->count();
        $avgScore = $attempts->avg('score_percent') ?? 0;
        $highestScore = $attempts->max('score_percent') ?? 0;
        $lowestScore = $attempts->min('score_percent') ?? 0;

        // Group attempts by category
        $categoryAttempts = $attempts->groupBy(function ($attempt) {
            return $attempt->quiz->category->name ?? 'N/A';
        });

        return $schema
            ->schema([
                Section::make('Student Information')
                    ->description('Basic account and profile details')
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
                            ->default('N/A'),

                        TextEntry::make('email_verified_at')
                            ->label('Email Status')
                            ->badge()
                            ->state(function (User $record): string {
                                return $record->email_verified_at ? 'Verified' : 'Unverified';
                            })
                            ->color(fn (User $record): string => $record->email_verified_at ? 'success' : 'warning')
                            ->columnSpanFull(),

                        TextEntry::make('created_at')
                            ->label('Account Created')
                            ->dateTime()
                            ->columnSpanFull(),
                    ]),

                Section::make('Quiz Attempt Summary')
                    ->description('Overview of student quiz performance')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('total_attempts')
                            ->label('Total Attempts')
                            ->state(function () use ($totalAttempts) {
                                return $totalAttempts;
                            })
                            ->badge()
                            ->color('info'),

                        TextEntry::make('submitted_attempts')
                            ->label('Submitted')
                            ->state(function () use ($submittedAttempts) {
                                return $submittedAttempts;
                            })
                            ->badge()
                            ->color('success'),

                        TextEntry::make('pending_attempts')
                            ->label('In Progress')
                            ->state(function () use ($totalAttempts, $submittedAttempts) {
                                return $totalAttempts - $submittedAttempts;
                            })
                            ->badge()
                            ->color('warning'),
                    ]),

                Section::make('Performance Metrics')
                    ->description('Quiz scores and performance statistics')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('average_score')
                            ->label('Average Score')
                            ->state(function () use ($avgScore) {
                                return round($avgScore, 2) . '%';
                            })
                            ->weight(FontWeight::Bold)
                            ->color(fn () => $avgScore >= 70 ? 'success' : ($avgScore >= 50 ? 'warning' : 'danger'))
                            ->columnSpanFull(),

                        TextEntry::make('highest_score')
                            ->label('Highest Score')
                            ->state(function () use ($highestScore) {
                                return round($highestScore, 2) . '%';
                            })
                            ->color('success'),

                        TextEntry::make('lowest_score')
                            ->label('Lowest Score')
                            ->state(function () use ($lowestScore) {
                                return round($lowestScore, 2) . '%';
                            })
                            ->color('danger'),

                        TextEntry::make('median_score')
                            ->label('Median Score')
                            ->state(function () use ($attempts) {
                                if ($attempts->isEmpty()) {
                                    return 'N/A';
                                }
                                $scores = $attempts->pluck('score_percent')->sort()->values()->toArray();
                                $count = count($scores);
                                $median = $count % 2 !== 0
                                    ? $scores[intval($count / 2)]
                                    : ($scores[$count / 2 - 1] + $scores[$count / 2]) / 2;
                                return round($median, 2) . '%';
                            })
                            ->color('info'),
                    ]),

                // --- Attempted Category History Section ---
                Section::make('Attempted Category History')
                    ->description('Categories attempted by this student, with answers and scores per category')
                    ->columns(1)
                    ->schema([
                        TextEntry::make('attempted_category_history')
                            ->label('')
                            ->state(function () use ($categoryAttempts) {
                                if ($categoryAttempts->isEmpty()) {
                                    return '<div class="text-gray-500">No attempted categories found.</div>';
                                }
                                $html = '<div style="overflow-x:auto">';
                                foreach ($categoryAttempts as $category => $catAttempts) {
                                    $html .= '<div class="mb-6 p-3 border rounded-lg bg-gray-50">';
                                    $html .= '<div class="font-bold text-base mb-2">Category: ' . htmlspecialchars($category) . ' (Attempts: ' . $catAttempts->count() . ')</div>';
                                    foreach ($catAttempts as $attempt) {
                                        $html .= '<div class="mb-2 pl-2">';
                                        $html .= '<div class="font-semibold">Quiz: ' . htmlspecialchars($attempt->quiz->title ?? 'N/A') . ' (Attempt #' . $attempt->id . ')</div>';
                                        $answers = $attempt->answers()->with(["question", "questionOption", "answer"])->orderBy("question_id")->get();
                                        if ($answers->isEmpty()) {
                                            $html .= '<div class="text-gray-500 ml-2">No answers found for this attempt.</div>';
                                        } else {
                                            $total = $answers->count();
                                            $correct = 0;
                                            $html .= '<table class="min-w-full text-xs text-left text-gray-700 border mb-2 ml-2"><thead><tr>';
                                            $html .= '<th class="border px-2 py-1">Number of Item</th>';
                                            $html .= '<th class="border px-2 py-1">Answer</th>';
                                            $html .= '<th class="border px-2 py-1">Correct Answer</th>';
                                            $html .= '</tr></thead><tbody>';
                                            $itemNum = 1;
                                            foreach ($answers as $ans) {
                                                $answerText = $ans->questionOption->option_text ?? $ans->text_answer ?? $ans->answer->answer_text ?? 'N/A';
                                                $correctAnswer = $ans->question->options->where('is_correct', true)->first()->option_text ?? $ans->question->answer_key ?? 'N/A';
                                                if ($ans->is_correct) $correct++;
                                                $html .= '<tr>';
                                                $html .= '<td class="border px-2 py-1">' . $itemNum . '</td>';
                                                $html .= '<td class="border px-2 py-1">' . htmlspecialchars($answerText) . '</td>';
                                                $html .= '<td class="border px-2 py-1">' . htmlspecialchars($correctAnswer) . '</td>';
                                                $html .= '</tr>';
                                                $itemNum++;
                                            }
                                            $html .= '</tbody></table>';
                                            $html .= '<div class="font-semibold ml-2">Score: ' . $correct . ' / ' . $total . '</div>';
                                        }
                                        $html .= '</div>';
                                    }
                                    $html .= '</div>';
                                }
                                $html .= '</div>';
                                return $html;
                            })
                            ->html(),
                    ]),

                Section::make('Quiz Attempt History')
                    ->description('Detailed list of all quiz attempts by this student')
                    ->columns(1)
                    ->schema([
                        TextEntry::make('quiz_attempt_history_table')
                            ->label('')
                            ->state(function () use ($attempts) {
                                if ($attempts->isEmpty()) {
                                    return '<div class="text-gray-500">No quiz attempts found.</div>';
                                }
                                $html = '<div style="overflow-x:auto"><table class="min-w-full text-xs text-left text-gray-700 border"><thead><tr>';
                                $html .= '<th class="border px-2 py-1">#</th>';
                                $html .= '<th class="border px-2 py-1">Category</th>';
                                $html .= '<th class="border px-2 py-1">Quiz Title</th>';
                                $html .= '<th class="border px-2 py-1">Questions</th>';
                                $html .= '<th class="border px-2 py-1">Answers</th>';
                                $html .= '<th class="border px-2 py-1">Attempt #</th>';
                                $html .= '</tr></thead><tbody>';
                                foreach ($attempts as $i => $attempt) {
                                    $category = $attempt->quiz->category->name ?? 'N/A';
                                    $quizTitle = $attempt->quiz->title ?? 'N/A';
                                    $questions = $attempt->questions ?? [];
                                    $answers = $attempt->answers ?? [];
                                    $qList = is_array($questions) ? implode('<br>', array_map('htmlspecialchars', $questions)) : htmlspecialchars((string)$questions);
                                    $aList = is_array($answers) ? implode('<br>', array_map('htmlspecialchars', $answers)) : htmlspecialchars((string)$answers);
                                    $html .= '<tr>';
                                    $html .= '<td class="border px-2 py-1">' . ($i+1) . '</td>';
                                    $html .= '<td class="border px-2 py-1">' . htmlspecialchars($category) . '</td>';
                                    $html .= '<td class="border px-2 py-1">' . htmlspecialchars($quizTitle) . '</td>';
                                    $html .= '<td class="border px-2 py-1">' . $qList . '</td>';
                                    $html .= '<td class="border px-2 py-1">' . $aList . '</td>';
                                    $html .= '<td class="border px-2 py-1">' . ($attempt->id ?? '-') . '</td>';
                                    $html .= '</tr>';
                                }
                                $html .= '</tbody></table></div>';
                                return $html;
                            })
                            ->html(),
                    ]),
            ]);
        }
    }
