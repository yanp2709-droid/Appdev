<?php

namespace App\Filament\Widgets;

use App\Models\Quiz_attempt;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class StudentPerformanceAnalyticsWidget extends BaseWidget
{
    protected static ?string $heading = 'Student Performance Analytics';

    protected static ?int $sort = 5;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->where('role', 'student')
                    ->withCount('quizAttempts')
                    ->with(['quizAttempts'])
                    ->latest('created_at')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Student Name')
                    ->searchable(['name', 'first_name', 'last_name'])
                    ->sortable(),

                TextColumn::make('student_id')
                    ->label('Student ID')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('quiz_attempts_count')
                    ->label('Total Attempts')
                    ->counts('quizAttempts')
                    ->sortable(),

                TextColumn::make('avg_score')
                    ->label('Average Score')
                    ->formatStateUsing(function (User $record): string {
                        $attempts = $record->quizAttempts()->get();

                        if ($attempts->isEmpty()) {
                            return 'No Data';
                        }

                        $avgScore = $attempts->avg('score_percent') ?? 0;
                        return round($avgScore, 2) . '%';
                    })
                    ->sortable(),

                TextColumn::make('highest_score')
                    ->label('Highest Score')
                    ->formatStateUsing(function (User $record): string {
                        $attempts = $record->quizAttempts()->get();

                        if ($attempts->isEmpty()) {
                            return 'N/A';
                        }

                        $highest = $attempts->max('score_percent') ?? 0;
                        return round($highest, 2) . '%';
                    })
                    ->color('success'),

                TextColumn::make('last_attempt')
                    ->label('Last Attempt')
                    ->formatStateUsing(function (User $record): string {
                        $lastAttempt = $record->quizAttempts()
                            ->orderByDesc('started_at')
                            ->first();

                        if (!$lastAttempt) {
                            return 'N/A';
                        }

                        return $lastAttempt->started_at?->format('M d, Y H:i') ?? 'N/A';
                    })
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('verification_status')
                    ->label('Email Status')
                    ->options([
                        'verified' => 'Verified',
                        'unverified' => 'Unverified',
                    ])
                    ->query(function (Tables\Filters\BaseFilter $filter, $value) {
                        if ($value === 'verified') {
                            return $filter->getQuery()->whereNotNull('email_verified_at');
                        }
                        return $filter->getQuery()->whereNull('email_verified_at');
                    }),
            ])
            ->paginated([10, 25, 50])
            ->defaultSort('quiz_attempts_count', 'desc');
    }
}
