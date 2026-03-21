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

                Section::make('Attempted Category History')
                    ->description('Categories attempted by this student with quiz scores')
                    ->columns(1)
                    ->schema([
                        TextEntry::make('attempted_category_history')
                            ->label('')
                            ->state(function () use ($attempts) {
                                if ($attempts->isEmpty()) {
                                    return '<div class="text-gray-500">No attempted categories found.</div>';
                                }
                                
                                $html = '<div style="overflow-x:auto"><table class="min-w-full text-sm text-left text-gray-700 border"><thead><tr class="bg-gray-100">';
                                $html .= '<th class="border px-4 py-2">Category</th>';
                                $html .= '<th class="border px-4 py-2">Quiz Title</th>';
                                $html .= '<th class="border px-4 py-2">Score</th>';
                                $html .= '<th class="border px-4 py-2">Correct Answers</th>';
                                $html .= '</tr></thead><tbody>';
                                
                                foreach ($attempts as $attempt) {
                                    $category = $attempt->quiz->category->name ?? 'N/A';
                                    $quizTitle = $attempt->quiz->title ?? 'N/A';
                                    $score = $attempt->score_percent ?? 0;
                                    $answers = $attempt->answers()->where('is_correct', true)->get();
                                    $totalAnswers = $attempt->answers()->count();
                                    $correctCount = $answers->count();
                                    
                                    $html .= '<tr>';
                                    $html .= '<td class="border px-4 py-2">' . htmlspecialchars($category) . '</td>';
                                    $html .= '<td class="border px-4 py-2">' . htmlspecialchars($quizTitle) . '</td>';
                                    $html .= '<td class="border px-4 py-2"><strong>' . round($score, 2) . '%</strong></td>';
                                    $html .= '<td class="border px-4 py-2">' . $correctCount . ' / ' . $totalAnswers . '</td>';
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
