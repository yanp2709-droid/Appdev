<?php

namespace App\Filament\Resources\Teachers;

use App\Filament\Resources\Teachers\Pages\CreateTeacher;
use App\Filament\Resources\Teachers\Pages\EditTeacher;
use App\Filament\Resources\Teachers\Pages\ListTeachers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
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

                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minLength(8)
                    ->same('passwordConfirmation')
                    ->dehydrated(fn (?string $state): bool => filled($state)),

                TextInput::make('passwordConfirmation')
                    ->label('Confirm Password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(false),
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

                IconColumn::make('is_protected')
                    ->label('Protected')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (User $record): bool => $record->isProtected()),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Delete Selected Teachers'),
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
