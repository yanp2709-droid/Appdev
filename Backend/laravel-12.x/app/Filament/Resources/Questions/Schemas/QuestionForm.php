<?php

namespace App\Filament\Resources\Questions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Schema;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->required()
                    ->searchable(),

                Select::make('question_type')
                    ->label('Question Type')
                    ->options([
                        'mcq' => 'Multiple Choice (MCQ)',
                        'tf' => 'True/False',
                        'multi_select' => 'Multi-Select',
                        'short_answer' => 'Short Answer',
                    ])
                    ->required()
                    ->live()
                    ->helperText('Choose the type of question'),

                TextInput::make('points')
                    ->label('Points')
                    ->numeric()
                    ->default(5)
                    ->required()
                    ->minValue(1),

                Textarea::make('question_text')
                    ->label('Question Prompt')
                    ->required()
                    ->rows(3)
                    ->placeholder('Enter the question text...'),

                // MCQ Options Editor
                Repeater::make('options')
                    ->label('Answer Options')
                    ->relationship('options')
                    ->visible(function (callable $get) {
                        $type = $get('question_type');
                        return in_array($type, ['mcq', 'tf', 'multi_select'], true);
                    })
                    ->reactive()
                    ->schema([
                        TextInput::make('option_text')
                            ->label('Option Text')
                            ->required()
                            ->columnSpan(2),

                        Checkbox::make('is_correct')
                            ->label('Correct Answer')
                            ->columnSpan(1)
                            ->helperText(fn (callable $get) =>
                                $get('../../question_type') === 'tf'
                                    ? 'True/False questions must have exactly one correct answer'
                                    : ($get('../../question_type') === 'multi_select'
                                        ? 'Multi-select questions can have multiple correct answers'
                                        : 'Multiple choice questions must have exactly one correct answer')
                            ),
                    ])
                    ->columns(3)
                    ->minItems(fn (callable $get) => $get('question_type') === 'tf' ? 2 : 2)
                    ->maxItems(fn (callable $get) => $get('question_type') === 'tf' ? 2 : null)
                    ->orderable()
                    ->collapsible()
                    ->addActionLabel('Add Option'),

                // Short Answer - Answer Key
                Textarea::make('answer_key')
                    ->label('Answer Key & Rubric')
                    ->visible(fn (callable $get) => $get('question_type') === 'short_answer')
                    ->required(fn (callable $get) => $get('question_type') === 'short_answer')
                    ->rows(5)
                    ->placeholder('Describe the expected answer, key points, or grading criteria...'),
            ]);
    }
}
