<?php

namespace App\Filament\Resources\Students;

use App\Filament\Resources\Students\Pages\ListStudents;
use App\Filament\Resources\Students\Pages\ViewStudent;
use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Models\User;
use App\Models\Quiz_attempt;
use App\Services\AcademicYearService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema as DatabaseSchema;

class StudentResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static ?string $navigationLabel = 'Students';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        $academicYear = app(AcademicYearService::class)->getSelectedAcademicYear();

        return parent::getEloquentQuery()
            ->where('role', 'student')
            ->when(
                DatabaseSchema::hasColumn('users', 'academic_year'),
                fn ($query) => $query->where('academic_year', $academicYear),
                function ($query) use ($academicYear) {
                    [$startDate, $endDate] = app(AcademicYearService::class)->getDateRange($academicYear);

                    return $query->whereBetween('created_at', [$startDate, $endDate]);
                },
            );
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('quiz_attempts_count')
                    ->label('Total Attempts')
                    ->state(function (User $record): int {
                        $academicYear = app(AcademicYearService::class)->getSelectedAcademicYear();

                        return $record->quizAttempts()
                            ->when(
                                DatabaseSchema::hasColumn('quiz_attempts', 'school_year'),
                                fn ($query) => $query->where('school_year', $academicYear),
                                function ($query) use ($academicYear) {
                                    [$startDate, $endDate] = app(AcademicYearService::class)->getDateRange($academicYear);

                                    return $query->whereBetween('submitted_at', [$startDate, $endDate]);
                                },
                            )
                            ->count();
                    })
                    ->sortable(),


            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-m-lock-open')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Activate Student Account')
                    ->modalDescription('This will reactivate the student account.')
                    ->visible(fn (User $record): bool => ! $record->is_active)
                    ->action(fn (User $record) => $record->update(['is_active' => true])),
                Action::make('resetPassword')
                    ->label('Reset Password')
                    ->icon('heroicon-m-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Reset Student Password')
                    ->modalDescription('The admin cannot see the current password for security reasons. A new temporary password will be generated and shown after confirmation.')
                    ->action(function (User $record) {
                        $tempPassword = \Illuminate\Support\Str::random(10);
                        $record->update([
                            'password' => bcrypt($tempPassword),
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Password Reset')
                            ->body("{$record->name}'s temporary password is: {$tempPassword}. Please share this securely and ask them to change it immediately.")
                            ->success()
                            ->persistent()
                            ->send();
                    }),
                DeleteAction::make()
                    ->label('Terminate')
                    ->modalHeading('Terminate Student Account')
                    ->modalDescription(fn (User $record): string => $record->quizAttempts()->exists()
                        ? 'This student cannot be deleted because they already have quiz records. Consider deactivating the account instead.'
                        : 'This will permanently delete the student account and all related data.')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->icon('heroicon-m-trash')
                    ->hidden(fn (User $record): bool => $record->role === 'admin')
                    ->disabled(fn (User $record): bool => $record->quizAttempts()->exists())
                    ->tooltip(fn (User $record): ?string => $record->quizAttempts()->exists()
                        ? 'This student cannot be deleted because they already have quiz records.'
                        : null),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Terminate Selected')
                    ->modalHeading('Terminate Selected Students')
                    ->modalDescription('This will permanently delete the selected student accounts. Students with quiz records will be skipped.')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->icon('heroicon-m-trash'),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStudents::route('/'),
            'create' => CreateStudent::route('/create'),
            'view' => ViewStudent::route('/{record}'),
            'edit' => \App\Filament\Resources\Students\Pages\EditStudent::route('/{record}/edit'),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('first_name')->label('First Name')->required()->maxLength(100),
            TextInput::make('last_name')->label('Last Name')->required()->maxLength(100),
            TextInput::make('email')
                ->label('Email Address')
                ->email()
                ->required()
                ->helperText('Will be kept in the form student_id@lnu.edu.ph.'),
            TextInput::make('section')
                ->label('Section')
                ->required()
                ->maxLength(4)
                ->helperText('Use the AIxx format, such as AI33.'),
            TextInput::make('student_id')
                ->label('Student ID')
                ->required()
                ->maxLength(8)
                ->helperText('Use 8 digits that start with 230, such as 23010001.'),
            TextInput::make('year_level')->label('Year Level')->required(),
            TextInput::make('course')
                ->label('Course')
                ->required()
                ->default('BSIT')
                ->disabled()
                ->dehydrated()
                ->helperText('Student records always use BSIT.'),
            TextInput::make('current_password')
                ->password()
                ->revealable()
                ->label('Current Password')
                ->helperText('Enter the current password before setting a new one.')
                ->required(fn ($get) => filled($get('password')))
                ->visible(fn (string $operation): bool => $operation === 'edit')
                ->dehydrated(false),
            TextInput::make('password')
                ->password()
                ->revealable()
                ->label('New Password')
                ->minLength(8)
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->nullable(),
            TextInput::make('passwordConfirmation')
                ->label('Confirm Password')
                ->password()
                ->revealable()
                ->same('password')
                ->dehydrated(false)
                ->visible(fn ($get) => filled($get('password'))),
        ]);
    }
}
