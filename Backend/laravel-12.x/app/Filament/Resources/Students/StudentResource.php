<?php

namespace App\Filament\Resources\Students;

use App\Filament\Resources\Students\Pages\ListStudents;
use App\Filament\Resources\Students\Pages\ViewStudent;
use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Models\User;
use App\Models\Quiz_attempt;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
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

class StudentResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static ?string $navigationLabel = 'Students';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', 'student');
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
                    ->counts('quizAttempts')
                    ->sortable(),


            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make(),
                Action::make('deactivate')
                    ->label('Deactivate')
                    ->icon('heroicon-m-lock-closed')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Deactivate Student Account')
                    ->modalDescription('This will deactivate the student account. They will no longer be able to log in, but all quiz records will be preserved.')
                    ->visible(fn (User $record): bool => $record->is_active)
                    ->action(fn (User $record) => $record->update(['is_active' => false])),
                Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-m-lock-open')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Activate Student Account')
                    ->modalDescription('This will reactivate the student account.')
                    ->visible(fn (User $record): bool => ! $record->is_active)
                    ->action(fn (User $record) => $record->update(['is_active' => true])),
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
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('first_name')->label('First Name')->required(),
            TextInput::make('last_name')->label('Last Name')->required(),
            TextInput::make('email')->label('Email Address')->email()->required(),
            TextInput::make('section')->label('Section')->required(),
            TextInput::make('student_id')->label('Student ID')->required(),
            TextInput::make('year_level')->label('Year Level')->required(),
            TextInput::make('course')->label('Course')->required(),
        ]);
    }
}
