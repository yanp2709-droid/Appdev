<?php

namespace App\Filament\Resources\Questions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Get;
use Filament\Schemas\Schema;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        $hasCategoryId = request()->has('category_id');
        
        return $schema
            ->components([
                // Show Select if no category_id in URL, otherwise show Hidden
                $hasCategoryId 
                    ? Hidden::make('category_id')
                        ->default(function () {
                            return request()->query('category_id');
                        })
                    : Select::make('category_id')
                        ->label('Category')
                        ->relationship('category', 'name')
                        ->required()
                        ->searchable()
                        ->createOptionAction(null),

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
                            ->label('✓ Correct')
                            ->columnSpan(1)
                            ->helperText(fn (callable $get) =>
                                $get('../../question_type') === 'tf'
                                    ? 'TF: exactly 1 correct'
                                    : ($get('../../question_type') === 'multi_select'
                                        ? 'Multi: ≥ 1 correct'
                                        : 'MCQ: exactly 1 correct')
                            ),
                    ])
                    ->columns(3)
                    ->minItems(fn (callable $get) => $get('question_type') === 'tf' ? 2 : 2)
                    ->maxItems(fn (callable $get) => $get('question_type') === 'tf' ? 2 : null)
                    ->orderable()
                    ->collapsible()
                    ->addActionLabel('Add Option')
                    ->disableItemCreation(false)
                    ->disableItemDeletion(false),

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
