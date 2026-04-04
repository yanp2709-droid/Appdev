<?php

namespace App\Filament\Resources\Quizzes\Schemas;

use App\Models\Quiz;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Set;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuizForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Quiz Details')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Select::make('category_id')
                            ->label('Category')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable(),
                        Select::make('teacher_id')
                            ->label('Teacher')
                            ->relationship('teacher', 'name')
                            ->required()
                            ->searchable(),
                        Select::make('difficulty')
                            ->options([
                                'Easy' => 'Easy',
                                'Medium' => 'Medium',
                                'Hard' => 'Hard',
                            ])
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Delivery Settings')
                    ->schema([
                        Toggle::make('shuffle_questions')->default(false),
                        Toggle::make('shuffle_options')->default(false),
                        Toggle::make('timer_enabled')->default(true)->live(),
                        TextInput::make('duration_minutes')
                            ->numeric()
                            ->minValue(1)
                            ->default(Quiz::DEFAULT_DURATION_MINUTES)
                            ->required(fn (callable $get) => (bool) $get('timer_enabled'))
                            ->visible(fn (callable $get) => (bool) $get('timer_enabled')),
                        TextInput::make('max_attempts')
                            ->label('Attempt Limit')
                            ->numeric()
                            ->minValue(1)
                            ->nullable()
                            ->helperText('Leave blank to allow unlimited attempts.'),
                    ])
                    ->columns(2),
                Section::make('Review Settings')
                    ->schema([
                        Toggle::make('allow_review_before_submit')
                            ->default(false),
                        Toggle::make('show_score_immediately')
                            ->default(true),
                        Toggle::make('show_answers_after_submit')
                            ->default(false)
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state): void {
                                if (!$state) {
                                    $set('show_correct_answers_after_submit', false);
                                }
                            }),
                        Toggle::make('show_correct_answers_after_submit')
                            ->default(false)
                            ->disabled(fn (callable $get) => !(bool) $get('show_answers_after_submit'))
                            ->dehydrated(true),
                    ])
                    ->columns(2),
            ]);
    }
}
