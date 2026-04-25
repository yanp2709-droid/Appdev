<?php

namespace App\Filament\Resources\Questions\Schemas;

use App\Models\Question;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        $cancelUrl = fn (): string => request()->headers->get('referer')
            ?: \App\Filament\Resources\Categories\CategoryResource::getUrl('index');

        return $schema
            ->components([
                self::questionSection(
                    title: 'True or False',
                    description: 'Use this section when the answer should be exactly one of two choices.',
                    statePath: 'true_false',
                    optionsLabel: 'True/False Options',
                    optionsHelperText: 'Add exactly two options and mark one as correct.',
                    defaultOptions: [
                        ['option_text' => 'True'],
                        ['option_text' => 'False'],
                    ],
                    createMethod: 'createTrueFalse',
                    createAnotherMethod: 'createTrueFalseAnother',
                    cancelUrl: $cancelUrl,
                    withAnswerKey: false,
                ),

                self::questionSection(
                    title: 'Multiple Choice',
                    description: 'Use this section for a single correct answer from multiple options.',
                    statePath: 'multiple_choice',
                    optionsLabel: 'Multiple Choice Options',
                    optionsHelperText: 'Add at least two options and mark exactly one as correct.',
                    defaultOptions: [],
                    createMethod: 'createMultipleChoice',
                    createAnotherMethod: 'createMultipleChoiceAnother',
                    cancelUrl: $cancelUrl,
                    withAnswerKey: false,
                ),

                self::questionSection(
                    title: 'Multiselect',
                    description: 'Use this section when more than one answer can be correct.',
                    statePath: 'multi_select',
                    optionsLabel: 'Multiselect Options',
                    optionsHelperText: 'Add at least two options and mark one or more as correct.',
                    defaultOptions: [],
                    createMethod: 'createMultiSelect',
                    createAnotherMethod: 'createMultiSelectAnother',
                    cancelUrl: $cancelUrl,
                    withAnswerKey: false,
                ),

                self::questionSection(
                    title: 'Short Answer',
                    description: 'Use this section when the student answers with text instead of options.',
                    statePath: 'short_answer',
                    optionsLabel: null,
                    optionsHelperText: null,
                    defaultOptions: [],
                    createMethod: 'createShortAnswer',
                    createAnotherMethod: 'createShortAnswerAnother',
                    cancelUrl: $cancelUrl,
                    withAnswerKey: true,
                ),
            ]);
    }

    private static function questionSection(
        string $title,
        string $description,
        string $statePath,
        ?string $optionsLabel,
        ?string $optionsHelperText,
        array $defaultOptions,
        string $createMethod,
        string $createAnotherMethod,
        callable $cancelUrl,
        bool $withAnswerKey,
    ): Section {
        $schema = [
            self::categoryField(),

            TextInput::make('points')
                ->label('Points')
                ->integer()
                ->default(5)
                ->minValue(1)
                ->maxValue(1000)
                ->validationMessages([
                    'integer' => 'Points must be a whole number.',
                    'min' => 'Points must be at least 1.',
                    'max' => 'Points cannot exceed 1000.',
                ]),

            Textarea::make('question_text')
                ->label('Question Prompt')
                ->rows(3)
                ->columnSpanFull()
                ->placeholder('Enter the question text...'),
        ];

        if ($withAnswerKey) {
            $schema[] = Textarea::make('answer_key')
                ->label('Answer Key & Rubric')
                ->rows(5)
                ->columnSpanFull()
                ->placeholder('Describe the expected answer, key points, or grading criteria...');
        } else {
            $schema[] = self::optionsRepeater($optionsLabel ?? 'Options')
                ->default($defaultOptions)
                ->helperText($optionsHelperText)
                ->columnSpanFull();
        }

        return Section::make($title)
            ->description($description)
            ->columnSpanFull()
            ->statePath($statePath)
            ->schema($schema)
            ->columns(2)
            ->footerActions(self::sectionActions(
                self::sectionPrefix($statePath),
                $createMethod,
                $createAnotherMethod,
                $cancelUrl,
            ))
            ->footerActionsAlignment(Alignment::Start);
    }

    private static function categoryField(): Select
    {
        $field = Select::make('category_id')
            ->label('Category')
            ->relationship('category', 'name')
            ->searchable()
            ->createOptionAction(null);

        if (request()->filled('category_id')) {
            return $field
                ->default(fn (): ?int => request()->integer('category_id'))
                ->disabled()
                ->dehydrated();
        }

        return $field;
    }

    private static function optionsRepeater(string $label): Repeater
    {
        return Repeater::make('options')
            ->label($label)
            ->reactive()
            ->schema([
                TextInput::make('option_text')
                    ->label('Option Text')
                    ->columnSpan(2),

                Checkbox::make('is_correct')
                    ->label('Correct')
                    ->columnSpan(1),
            ])
            ->columns(3)
            ->orderable()
            ->collapsible()
            ->addActionLabel('Add Option')
            ->disableItemCreation(false)
            ->disableItemDeletion(false);
    }

    /**
     * @return array<Action>
     */
    private static function sectionActions(string $prefix, string $createMethod, string $createAnotherMethod, callable $cancelUrl): array
    {
        return [
            Action::make($prefix . '_create')
                ->label('Create')
                ->action($createMethod)
                ->color('warning'),

            Action::make($prefix . '_createAnother')
                ->label('Create & create another')
                ->action($createAnotherMethod)
                ->color('gray'),

            Action::make($prefix . '_cancel')
                ->label('Cancel')
                ->url($cancelUrl)
                ->color('gray'),
        ];
    }

    private static function sectionPrefix(string $statePath): string
    {
        return match ($statePath) {
            'true_false' => 'tf',
            'multiple_choice' => 'mcq',
            'multi_select' => 'multi',
            'short_answer' => 'short',
            default => $statePath,
        };
    }
}
