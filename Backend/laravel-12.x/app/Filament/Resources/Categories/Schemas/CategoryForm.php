<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Quiz;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category Details')
                    ->schema([
                        TextInput::make('name')
                            ->label('Category Name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g., Programming Basics'),

                        TextInput::make('time_limit_minutes')
                            ->label('Time Limit (Minutes)')
                            ->numeric()
                            ->minValue(1)
                            ->default(Quiz::DEFAULT_DURATION_MINUTES)
                            ->required()
                            ->helperText('Students will use this timing when they take the category quiz.'),

                        Textarea::make('description')
                            ->label('Description')
                            ->nullable()
                            ->rows(4)
                            ->placeholder('Describe the category...'),

                        Toggle::make('is_published')
                            ->label('Published')
                            ->default(true)
                            ->helperText('Make this category visible to students'),
                    ])
                    ->columns(2),

                Section::make('Quiz Delivery')
                    ->schema([
                        Select::make('difficulty')
                            ->options([
                                'Easy' => 'Easy',
                                'Medium' => 'Medium',
                                'Hard' => 'Hard',
                            ])
                            ->required()
                            ->default('Easy'),
                        Toggle::make('timer_enabled')
                            ->default(true),
                        Toggle::make('shuffle_questions')
                            ->default(false),
                        Toggle::make('shuffle_options')
                            ->default(false),
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
