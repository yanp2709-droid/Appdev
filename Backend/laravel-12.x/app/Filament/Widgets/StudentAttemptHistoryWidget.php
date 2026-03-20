<?php

namespace App\Filament\Widgets;

use App\Models\Quiz_attempt;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class StudentAttemptHistoryWidget extends BaseWidget
{
    protected static ?string $heading = 'Student Quiz Attempt History';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->where('role', 'student')
                    ->withCount('quizAttempts')
                    ->latest('created_at')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Full Name')
                    ->formatStateUsing(function (User $record): string {
                        $name = trim((string) $record->name);
                        if ($name !== '') {
                            return $name;
                        }
                        return trim((string) $record->first_name . ' ' . (string) $record->last_name);
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('student_id')
                    ->label('Student ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(fn (User $record): string => $record->email_verified_at ? 'Verified' : 'Unverified')
                    ->colors([
                        'success' => 'Verified',
                        'warning' => 'Unverified',
                    ]),
                BadgeColumn::make('role')
                    ->label('Role')
                    ->colors([
                        'primary' => 'student',
                        'warning' => 'teacher',
                        'danger' => 'admin',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('quiz_attempts_count')
                    ->label('Total Attempts')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('viewAttempts')
                    ->label('View Attempts')
                    ->modalHeading('Quiz Attempt History')
                    ->icon('heroicon-m-rectangle-stack')
                    ->modalContent(function (User $record) {
                        $attempts = Quiz_attempt::query()
                            ->with(['quiz.category'])
                            ->where('student_id', $record->id)
                            ->orderByDesc('started_at')
                            ->get();

                        return view('filament.widgets.student-attempt-history-modal', [
                            'student' => $record,
                            'attempts' => $attempts,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalSize('lg')
                    ->modalCancelActionLabel('Close'),

                Tables\Actions\Action::make('viewStudent')
                    ->label('View Profile')
                    ->url(fn (User $record) => route('filament.admin.resources.students.view', $record))
                    ->icon('heroicon-m-eye')
                    ->openUrlInNewTab(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
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
            ->defaultSort('created_at', 'desc');
    }
}