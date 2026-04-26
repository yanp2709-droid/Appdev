<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Questions\QuestionResource;
use App\Models\Question;
use Exception;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateQuestion extends CreateRecord
{
    protected static string $resource = QuestionResource::class;

    public ?string $questionTypeOverride = null;

    public array $preparedQuestionData = [];

    public function createTrueFalse(): void
    {
        $this->questionTypeOverride = Question::TYPE_TRUE_FALSE;
        $this->create();
    }

    public function createTrueFalseAnother(): void
    {
        $this->questionTypeOverride = Question::TYPE_TRUE_FALSE;
        $this->create(another: true);
    }

    public function createMultipleChoice(): void
    {
        $this->questionTypeOverride = Question::TYPE_MCQ;
        $this->create();
    }

    public function createMultipleChoiceAnother(): void
    {
        $this->questionTypeOverride = Question::TYPE_MCQ;
        $this->create(another: true);
    }

    public function createMultiSelect(): void
    {
        $this->questionTypeOverride = Question::TYPE_MULTI_SELECT;
        $this->create();
    }

    public function createMultiSelectAnother(): void
    {
        $this->questionTypeOverride = Question::TYPE_MULTI_SELECT;
        $this->create(another: true);
    }

    public function createShortAnswer(): void
    {
        $this->questionTypeOverride = Question::TYPE_SHORT_ANSWER;
        $this->create();
    }

    public function createShortAnswerAnother(): void
    {
        $this->questionTypeOverride = Question::TYPE_SHORT_ANSWER;
        $this->create(another: true);
    }

    protected function getFormActions(): array
    {
        return [];
    }

    public function hasResourceBreadcrumbs(): bool
    {
        return false;
    }

    protected function preserveFormDataWhenCreatingAnother(array $data): array
    {
        $sectionKey = match ($this->questionTypeOverride) {
            Question::TYPE_TRUE_FALSE => 'true_false',
            Question::TYPE_MCQ => 'multiple_choice',
            Question::TYPE_MULTI_SELECT => 'multi_select',
            Question::TYPE_SHORT_ANSWER => 'short_answer',
            default => null,
        };

        if ($sectionKey === null) {
            return [];
        }

        $categoryId = data_get($data, $sectionKey . '.category_id');

        if (empty($categoryId)) {
            return [];
        }

        return [
            $sectionKey => [
                'category_id' => $categoryId,
            ],
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            $questionType = $this->questionTypeOverride;
            $sectionKey = match ($questionType) {
                Question::TYPE_TRUE_FALSE => 'true_false',
                Question::TYPE_MCQ => 'multiple_choice',
                Question::TYPE_MULTI_SELECT => 'multi_select',
                Question::TYPE_SHORT_ANSWER => 'short_answer',
                default => null,
            };

            if ($questionType === null || $sectionKey === null) {
                throw new Exception('Question type is required.');
            }

            $sectionData = $data[$sectionKey] ?? [];
            $normalizedData = array_merge($data, $sectionData);

            if (empty($normalizedData['category_id'])) {
                throw new Exception('Category is required.');
            }

            if (empty($normalizedData['points'])) {
                $normalizedData['points'] = 5;
            }

            $normalizedData['question_type'] = $questionType;

            if ($questionType === Question::TYPE_SHORT_ANSWER) {
                $normalizedData['answer_key'] = $sectionData['answer_key'] ?? null;
            } else {
                $normalizedData['options'] = $sectionData['options'] ?? [];
            }

            unset(
                $normalizedData['true_false'],
                $normalizedData['multiple_choice'],
                $normalizedData['multi_select'],
                $normalizedData['short_answer'],
            );

            $errors = Question::validatePayload($normalizedData);

            if (! empty($errors)) {
                throw new Exception(implode(', ', $errors));
            }

            $this->preparedQuestionData = $normalizedData;

            return $normalizedData;
        } catch (Exception $e) {
            throw ValidationException::withMessages([
                'question_text' => [$e->getMessage()],
            ]);
        }
    }

    protected function afterCreate(): void
    {
        try {
            $data = $this->preparedQuestionData;
            $options = $data['options'] ?? [];

            if (! empty($options) && is_array($options)) {
                foreach ($options as $index => $optionData) {
                    if (! isset($optionData['option_text']) || empty($optionData['option_text'])) {
                        continue;
                    }

                    $this->record->options()->create([
                        'option_text' => $optionData['option_text'],
                        'is_correct' => ! empty($optionData['is_correct']),
                        'order_index' => $index,
                    ]);
                }
            }

            $this->preparedQuestionData = [];
        } catch (Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Failed to create options: ' . $e->getMessage())
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        $categoryId = $this->record?->category_id;

        if ($categoryId) {
            return CategoryResource::getUrl('questions', ['record' => $categoryId]);
        }

        return CategoryResource::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Success!')
            ->body('Question created successfully!');
    }
}
