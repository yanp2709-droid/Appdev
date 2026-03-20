<?php

namespace App\Filament\Resources\Attempts;

use App\Filament\Resources\Attempts\Pages\ListAttempts;
use App\Models\Quiz_attempt;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class AttemptResource extends Resource
{
    protected static ?string $model = Quiz_attempt::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Quiz Attempts';

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('quiz.category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'submitted' => 'success',
                        'in_progress' => 'warning',
                        'expired' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('started_at')
                    ->label('Started')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('total_items')
                    ->label('Questions')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('correct_answers')
                    ->label('Correct')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('score_percent')
                    ->label('Score (%)')
                    ->formatStateUsing(fn ($state) => round($state, 2) . '%')
                    ->sortable()
                    ->color(fn ($state) => $state >= 70 ? 'success' : ($state >= 50 ? 'warning' : 'danger')),
            ])
            ->filters([
                Filter::make('status')
                    ->query(fn ($query, $data) => $query->where('status', $data['value'] ?? null))
                    ->form([
                        \Filament\Forms\Components\Select::make('value')
                            ->options([
                                'submitted' => 'Submitted',
                                'in_progress' => 'In Progress',
                                'expired' => 'Expired',
                            ]),
                    ]),
            ])
            ->actions([
                // Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('submitted_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttempts::route('/'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
