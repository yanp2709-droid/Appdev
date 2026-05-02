<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Filament\Widgets\StudentQuizAttemptsTableWidget;
use App\Filament\Widgets\StudentScoreStatsWidget;
use App\Models\Quiz;
use App\Models\QuizRetakeAllowance;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class ViewStudent extends ViewRecord
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('enableGradedRetake')
                ->label('Enable Graded Retake')
                ->icon('heroicon-m-arrow-path-rounded-square')
                ->color('warning')
                ->visible(fn (): bool => auth()->user()?->isAdmin() || auth()->user()?->isTeacher())
                ->form([
                    Select::make('quiz_id')
                        ->label('Quiz')
                        ->options(fn () => Quiz::query()
                            ->whereHas('attempts', fn ($query) => $query->where('student_id', $this->record->id))
                            ->with('category')
                            ->get()
                            ->mapWithKeys(fn (Quiz $quiz) => [
                                $quiz->id => trim(($quiz->title ?: 'Quiz') . ' - ' . ($quiz->category->name ?? 'Unknown Subject')),
                            ])
                            ->all())
                        ->searchable()
                        ->required(),
                    TextInput::make('additional_graded_attempts')
                        ->label('Extra graded attempts to allow')
                        ->numeric()
                        ->default(1)
                        ->minValue(1)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $allowance = QuizRetakeAllowance::firstOrNew([
                        'student_id' => $this->record->id,
                        'quiz_id' => (int) $data['quiz_id'],
                    ]);

                    $allowance->additional_graded_attempts =
                        (int) ($allowance->additional_graded_attempts ?? 0) + (int) $data['additional_graded_attempts'];
                    $allowance->updated_by = auth()->id();
                    $allowance->save();

                    $quiz = Quiz::with('category')->find($data['quiz_id']);

                    Notification::make()
                        ->title('Graded retake enabled')
                        ->body(
                            sprintf(
                                '%s can now take %d additional graded attempt(s) for %s.',
                                $this->record->name,
                                (int) $data['additional_graded_attempts'],
                                $quiz?->category?->name ?? $quiz?->title ?? 'the selected quiz'
                            )
                        )
                        ->success()
                        ->send();
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Personal Information')
                    ->description('Basic account and profile details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Full Name')
                            ->weight(FontWeight::Bold)
                            ->columnSpanFull(),

                        TextEntry::make('email')
                            ->label('Email Address')
                            ->copyable()
                            ->icon('heroicon-m-envelope'),

                        TextEntry::make('student_id')
                            ->label('Student ID')
                            ->icon('heroicon-m-identification')
                            ->default('N/A'),

                        TextEntry::make('section')
                            ->label('Section')
                            ->icon('heroicon-m-squares-2x2')
                            ->default('N/A'),

                        TextEntry::make('year_level')
                            ->label('Year Level')
                            ->icon('heroicon-m-academic-cap')
                            ->default('N/A'),

                        TextEntry::make('course')
                            ->label('Course')
                            ->icon('heroicon-m-book-open')
                            ->default('N/A'),

                        TextEntry::make('email_verified_at')
                            ->label('Email Status')
                            ->badge()
                            ->state(function (User $record): string {
                                return $record->email_verified_at ? 'Verified' : 'Unverified';
                            })
                            ->color(fn (User $record): string => $record->email_verified_at ? 'success' : 'warning')
                            ->columnSpanFull(),

                        TextEntry::make('created_at')
                            ->label('Account Created')
                            ->dateTime()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected function getFooterWidgets(): array
    {
        return [
            StudentScoreStatsWidget::class,
            StudentQuizAttemptsTableWidget::class,
        ];
    }

    public function getFooterWidgetsColumns(): int | array
    {
        return 1;
    }
}
