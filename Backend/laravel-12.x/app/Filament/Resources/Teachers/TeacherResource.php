<?php

namespace App\Filament\Resources\Teachers;

use App\Filament\Resources\Teachers\Pages\CreateTeacher;
use App\Filament\Resources\Teachers\Pages\EditTeacher;
use App\Filament\Resources\Teachers\Pages\ListTeachers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TeacherResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $navigationLabel = 'Teachers';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Full Name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('Email Address')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

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
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minLength(8)
                    ->dehydrated(fn (?string $state): bool => filled($state)),

                TextInput::make('passwordConfirmation')
                    ->label('Confirm Password')
                    ->password()
                    ->revealable()
                    ->same('password')
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(false)
                    ->visible(fn ($get) => filled($get('password'))),
            ])
            ->columns(2);
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
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->sortable(),
            ])
            ->actions([
                EditAction::make(),

                Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Activate Teacher')
                    ->modalDescription('This will allow the teacher to access the admin dashboard again.')
                    ->hidden(fn (User $record): bool => $record->is_active)
                    ->action(fn (User $record) => $record->update(['is_active' => true])),

                Action::make('resetPassword')
                    ->label('Reset Password')
                    ->icon('heroicon-m-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Reset Teacher Password')
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
            ])
            ->bulkActions([
                BulkAction::make('activate')
                    ->label('Activate Selected')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Activate Selected Teachers')
                    ->modalDescription('This will allow the selected teachers to access the admin dashboard again.')
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $record->update(['is_active' => true]);
                        }
                    }),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', 'teacher');
    }

    public static function canViewAny(): bool
    {
        return static::canManageTeachers();
    }

    public static function canCreate(): bool
    {
        return static::canManageTeachers();
    }

    public static function canEdit($record): bool
    {
        return static::canManageTeachers();
    }

    public static function canDelete($record): bool
    {
        return static::canManageTeachers() && ! $record->isProtected();
    }

    public static function canDeleteAny(): bool
    {
        return static::canManageTeachers();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTeachers::route('/'),
            'create' => CreateTeacher::route('/create'),
            'edit' => EditTeacher::route('/{record}/edit'),
        ];
    }

    protected static function canManageTeachers(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->isAdmin()
            && $user->isProtected();
    }
}
