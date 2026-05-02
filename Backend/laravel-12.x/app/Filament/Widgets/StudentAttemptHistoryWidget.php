<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\AdminDashboard;
use App\Models\Quiz_attempt;
use App\Models\User;
use App\Services\AcademicYearService;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class StudentAttemptHistoryWidget extends BaseWidget
{
    protected static ?string $heading = 'Student Quiz Attempt History';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 2;

    protected $listeners = ['academicYearChanged' => '$refresh'];

    public function table(Table $table): Table
    {
        $academicYear = AdminDashboard::getSelectedAcademicYear();
        [$startDate, $endDate] = app(AcademicYearService::class)->getDateRange($academicYear);

        return $table
            ->query(
                User::query()
                    ->where('role', 'student')
                    ->when(
                        Schema::hasColumn('users', 'academic_year'),
                        fn ($query) => $query->where('academic_year', $academicYear),
                        function ($query) use ($academicYear) {
                            [$startDate, $endDate] = app(AcademicYearService::class)->getDateRange($academicYear);

                            return $query->whereBetween('created_at', [$startDate, $endDate]);
                        },
                    )
                    ->withCount([
                        'quizAttempts as quiz_attempts_count' => function (Builder $query) use ($academicYear, $startDate, $endDate): void {
                            $query->when(
                                Schema::hasColumn('quiz_attempts', 'school_year'),
                                fn ($query) => $query->where('school_year', $academicYear),
                                fn ($query) => $query->whereBetween('submitted_at', [$startDate, $endDate]),
                            );
                        },
                    ])
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
                        $academicYear = AdminDashboard::getSelectedAcademicYear();
                        [$startDate, $endDate] = app(AcademicYearService::class)->getDateRange($academicYear);

                        $attempts = Quiz_attempt::query()
                            ->with(['quiz.category'])
                            ->where('student_id', $record->id)
                            ->when(
                                Schema::hasColumn('quiz_attempts', 'school_year'),
                                fn ($query) => $query->where('school_year', $academicYear),
                                fn ($query) => $query->whereBetween('submitted_at', [$startDate, $endDate]),
                            )
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
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if ($value === 'verified') {
                            return $query->whereNotNull('email_verified_at');
                        }

                        if ($value === 'unverified') {
                            return $query->whereNull('email_verified_at');
                        }

                        return $query;
                    }),
            ])
            ->paginated([10, 25, 50])
            ->defaultSort('created_at', 'desc');
    }
}
