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
            ]);
    }
}
