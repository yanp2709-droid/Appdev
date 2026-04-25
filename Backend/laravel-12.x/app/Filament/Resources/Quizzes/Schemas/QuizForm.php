<?php

namespace App\Filament\Resources\Quizzes\Schemas;

use App\Models\Quiz;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

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
                            ->searchable()
                            ->disabled(fn () => Auth::user()?->role === 'teacher')
                            ->default(fn () => Auth::user()?->role === 'teacher' ? Auth::user()->id : null)
                            ->dehydrated(),
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
                        Toggle::make('timer_enabled')
                            ->default(true)
                            ->live(),
                        Toggle::make('shuffle_questions')
                            ->default(false),
                        Toggle::make('shuffle_options')
                            ->default(false),
                        TextInput::make('duration_minutes')
                            ->integer()
                            ->minValue(1)
                            ->maxValue(300)
                            ->default(Quiz::DEFAULT_DURATION_MINUTES)
                            ->required(fn (callable $get) => (bool) $get('timer_enabled'))
                            ->visible(fn (callable $get) => (bool) $get('timer_enabled'))
                            ->validationMessages([
                                'integer' => 'Duration must be a whole number in minutes.',
                                'min' => 'Duration must be at least 1 minute.',
                                'max' => 'Duration cannot exceed 300 minutes (5 hours).',
                            ]),
                        TextInput::make('max_attempts')
                            ->label('Attempt Limit')
                            ->integer()
                            ->minValue(1)
                            ->maxValue(100)
                            ->nullable()
                            ->helperText('Leave blank to allow unlimited attempts.')
                            ->validationMessages([
                                'integer' => 'Attempt limit must be a whole number.',
                                'min' => 'Attempt limit must be at least 1.',
                                'max' => 'Attempt limit cannot exceed 100.',
                            ]),
                    ])
                    ->columns(1),
                Section::make('Review Settings')
                    ->schema([
                        Toggle::make('show_answers_after_submit')
                            ->default(false)
                            ->live(),
                        Toggle::make('show_correct_answers_after_submit')
                            ->default(false)
                            ->visible(fn (callable $get) => (bool) $get('show_answers_after_submit'))
                            ->helperText('Shows correct answers after quiz submission'),
                    ])
                    ->columns(2),
            ]);
    }
}
