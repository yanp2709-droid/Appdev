<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Quizzes\QuizResource;
use App\Filament\Resources\Questions\QuestionResource;
use App\Models\Question;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Filament\Resources\Pages\EditRecord;

class EditQuestion extends EditRecord
{
    protected static string $resource = QuestionResource::class;

    public array $preparedQuestionData = [];

    public function hasResourceBreadcrumbs(): bool
    {
        return false;
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            CategoryResource::getUrl('index') => 'Subject',
        ];

        $quiz = $this->record->quiz ?? null;
        $category = $quiz?->category ?? $this->record->category;

        if ($category) {
            $breadcrumbs[CategoryResource::getUrl('quizzes', ['record' => $category])] = $category->name;
        }

        if ($quiz) {
            $breadcrumbs[QuizResource::getUrl('questions', ['record' => $quiz])] = $quiz->title;
        }

        $breadcrumbs[] = 'Edit';

        return $breadcrumbs;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $sectionKey = match ($this->record->question_type) {
            Question::TYPE_TRUE_FALSE => 'true_false',
            Question::TYPE_MCQ => 'multiple_choice',
            Question::TYPE_MULTI_SELECT => 'multi_select',
            Question::TYPE_SHORT_ANSWER => 'short_answer',
            default => 'multiple_choice',
        };

        $sectionData = [
            'category_id' => $data['category_id'] ?? null,
            'quiz_id' => $data['quiz_id'] ?? null,
            'points' => $data['points'] ?? 5,
            'question_text' => $data['question_text'] ?? '',
        ];

        if ($this->record->question_type === Question::TYPE_SHORT_ANSWER) {
            $sectionData['answer_key'] = $data['answer_key'] ?? '';
        } else {
            $sectionData['options'] = $this->record->options->map(function ($option) {
                return [
                    'id' => $option->id,
                    'option_text' => $option->option_text,
                    'is_correct' => $option->is_correct,
                ];
            })->all();
        }

        $data[$sectionKey] = $sectionData;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $sectionKey = match ($this->record->question_type) {
            Question::TYPE_TRUE_FALSE => 'true_false',
            Question::TYPE_MCQ => 'multiple_choice',
            Question::TYPE_MULTI_SELECT => 'multi_select',
            Question::TYPE_SHORT_ANSWER => 'short_answer',
            default => 'multiple_choice',
        };

        $sectionData = $data[$sectionKey] ?? [];
        $flattened = array_merge($data, $sectionData);
        $flattened['question_type'] = $this->record->question_type;

        unset(
            $flattened['true_false'],
            $flattened['multiple_choice'],
            $flattened['multi_select'],
            $flattened['short_answer'],
        );

        if (empty($flattened['category_id']) && !empty($flattened['quiz_id'])) {
            $quiz = \App\Models\Quiz::find($flattened['quiz_id']);
            if ($quiz) {
                $flattened['category_id'] = $quiz->category_id;
            }
        }

        $errors = Question::validatePayload($flattened, $this->record);

        if (! empty($errors)) {
            throw ValidationException::withMessages(['question' => $errors]);
        }

        $this->preparedQuestionData = $flattened;

        return Question::normalizeQuestionPayload($flattened);
    }

    protected function afterSave(): void
    {
        try {
            $options = $this->preparedQuestionData['options'] ?? [];
            $this->preparedQuestionData = [];

            if ($this->record->question_type === Question::TYPE_SHORT_ANSWER || empty($options) || ! is_array($options)) {
                return;
            }

            $existingOptionIds = $this->record->options()->pluck('id')->all();
            $submittedOptionIds = [];

            foreach ($options as $index => $optionData) {
                if (! isset($optionData['option_text']) || empty($optionData['option_text'])) {
                    continue;
                }

                $optionId = $optionData['id'] ?? null;

                if ($optionId && in_array((int) $optionId, $existingOptionIds, true)) {
                    $this->record->options()->where('id', $optionId)->update([
                        'option_text' => $optionData['option_text'],
                        'is_correct' => ! empty($optionData['is_correct']),
                        'order_index' => $index,
                    ]);
                    $submittedOptionIds[] = (int) $optionId;
                } else {
                    $newOption = $this->record->options()->create([
                        'option_text' => $optionData['option_text'],
                        'is_correct' => ! empty($optionData['is_correct']),
                        'order_index' => $index,
                    ]);
                    $submittedOptionIds[] = $newOption->id;
                }
            }

            $optionsToDelete = array_diff($existingOptionIds, $submittedOptionIds);
            if (! empty($optionsToDelete)) {
                $this->record->options()->whereIn('id', $optionsToDelete)->delete();
            }
        } catch (Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Failed to update options: ' . $e->getMessage())
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        $quizId = $this->record->quiz_id ?? null;

        if ($quizId) {
            return QuizResource::getUrl('questions', ['record' => $quizId]);
        }

        $categoryId = $this->record->category_id ?? null;
        if ($categoryId) {
            return CategoryResource::getUrl('quizzes', ['record' => $categoryId]);
        }

        return CategoryResource::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Success!')
            ->body('Question updated successfully!');
    }
}
