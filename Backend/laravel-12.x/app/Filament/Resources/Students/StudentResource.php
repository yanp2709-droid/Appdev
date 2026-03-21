<?php

namespace App\Filament\Resources\Students;

use App\Filament\Resources\Students\Pages\ListStudents;
use App\Filament\Resources\Students\Pages\ViewStudent;
use App\Filament\Resources\Students\RelationManagers\QuizAttemptsRelationManager;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
                    ->label('Joined')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('attempts_count')
                    ->label('Total Attempts')
                    ->counts('quizAttempts')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make(),
                DeleteAction::make()
                    ->label('Terminate')
                    ->modalHeading('Terminate Student Account')
                    ->modalDescription('This will permanently delete the student account and all related data.')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->icon('heroicon-m-trash')
                    ->hidden(fn (User $record): bool => $record->role === 'admin'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Terminate Selected')
                    ->modalHeading('Terminate Selected Students')
                    ->modalDescription('This will permanently delete the selected student accounts.')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->icon('heroicon-m-trash'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            QuizAttemptsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStudents::route('/'),
            'view' => ViewStudent::route('/{record}'),
        ];
    }
}
