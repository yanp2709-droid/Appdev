<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\AdminDashboard;
use App\Models\User;
use App\Services\AcademicYearService;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class StudentInformationWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 1;

    protected static ?int $sort = 1;

    protected static ?string $heading = 'Recent Registered Students';

    protected $listeners = ['academicYearChanged' => '$refresh'];

    public function table(Table $table): Table
    {
        $academicYear = AdminDashboard::getSelectedAcademicYear();

        return $table
            ->query(
                User::query()
                    ->where('role', 'student')
                    ->when(
                        \Illuminate\Support\Facades\Schema::hasColumn('users', 'academic_year'),
                        fn ($query) => $query->where('academic_year', $academicYear),
                        function ($query) use ($academicYear) {
                            [$startDate, $endDate] = app(AcademicYearService::class)->getDateRange($academicYear);

                            return $query->whereBetween('created_at', [$startDate, $endDate]);
                        },
                    )
                    ->withCount([
                        'quizAttempts as quiz_attempts_count' => function ($query) use ($academicYear): void {
                            $query->when(
                                \Illuminate\Support\Facades\Schema::hasColumn('quiz_attempts', 'school_year'),
                                fn ($query) => $query->where('school_year', $academicYear),
                                function ($query) use ($academicYear) {
                                    [$startDate, $endDate] = app(AcademicYearService::class)->getDateRange($academicYear);

                                    return $query->whereBetween('submitted_at', [$startDate, $endDate]);
                                },
                            );
                        },
                    ])
                    ->latest('created_at')
                    ->limit(300)
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
                    ->label('Quiz Attempts')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime('M j, Y - g:i A')
                    ->sortable(),
            ]);
    }
}
