<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\AdminDashboard;
use App\Models\Quiz_attempt;
use App\Models\User;
use App\Services\AcademicYearService;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class StudentPerformanceAnalyticsWidget extends BaseWidget
{
    protected static ?string $heading = 'Student Performance Analytics';

    protected static ?int $sort = 5;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

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
                        fn ($query) => $query->whereBetween('created_at', [$startDate, $endDate]),
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
                    ->sortable(),

                TextColumn::make('avg_score')
                    ->label('Average Score')
                    ->formatStateUsing(function (User $record): string {
                        $academicYear = AdminDashboard::getSelectedAcademicYear();
                        [$startDate, $endDate] = app(AcademicYearService::class)->getDateRange($academicYear);

                        $attempts = $record->quizAttempts()
                            ->where('status', 'submitted')
                            ->where('attempt_type', Quiz_attempt::TYPE_GRADED)
                            ->when(
                                Schema::hasColumn('quiz_attempts', 'school_year'),
                                fn ($query) => $query->where('school_year', $academicYear),
                                fn ($query) => $query->whereBetween('submitted_at', [$startDate, $endDate]),
                            )
                            ->get();

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
                        $academicYear = AdminDashboard::getSelectedAcademicYear();
                        [$startDate, $endDate] = app(AcademicYearService::class)->getDateRange($academicYear);

                        $attempts = $record->quizAttempts()
                            ->where('status', 'submitted')
                            ->where('attempt_type', Quiz_attempt::TYPE_GRADED)
                            ->when(
                                Schema::hasColumn('quiz_attempts', 'school_year'),
                                fn ($query) => $query->where('school_year', $academicYear),
                                fn ($query) => $query->whereBetween('submitted_at', [$startDate, $endDate]),
                            )
                            ->get();

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
                        $academicYear = AdminDashboard::getSelectedAcademicYear();
                        [$startDate, $endDate] = app(AcademicYearService::class)->getDateRange($academicYear);

                        $lastAttempt = $record->quizAttempts()
                            ->when(
                                Schema::hasColumn('quiz_attempts', 'school_year'),
                                fn ($query) => $query->where('school_year', $academicYear),
                                fn ($query) => $query->whereBetween('submitted_at', [$startDate, $endDate]),
                            )
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
            ->defaultSort('quiz_attempts_count', 'desc');
    }
}
