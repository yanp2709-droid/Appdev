<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Quizzes\QuizResource;
use App\Filament\Resources\Questions\QuestionResource;
use App\Models\Category;
use App\Models\Question;
use App\Models\Quiz;
use Exception;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateQuestion extends CreateRecord
{
    protected static string $resource = QuestionResource::class;

    public ?string $questionTypeOverride = null;

    public array $preparedQuestionData = [];

    public ?int $quizId = null;

    public ?int $categoryId = null;

    public function mount(): void
    {
        $this->quizId = request()->integer('quiz_id') ?: null;
        $this->categoryId = request()->integer('category_id') ?: null;

        parent::mount();
    }

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

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            CategoryResource::getUrl('index') => 'Subject',
        ];

        if ($this->quizId) {
            $quiz = Quiz::with('category')->find($this->quizId);

            if ($quiz?->category) {
                $breadcrumbs[CategoryResource::getUrl('quizzes', ['record' => $quiz->category])] = $quiz->category->name;
            }

            if ($quiz) {
                $breadcrumbs[QuizResource::getUrl('questions', ['record' => $quiz])] = $quiz->title;
            }

            $breadcrumbs[] = 'Questions';

            return $breadcrumbs;
        }

        if ($this->categoryId) {
            $category = Category::find($this->categoryId);

            if ($category) {
                $breadcrumbs[CategoryResource::getUrl('quizzes', ['record' => $category])] = $category->name;
            }

            $breadcrumbs[] = 'Quiz';

            return $breadcrumbs;
        }

        return $breadcrumbs;
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
        $quizId = data_get($data, $sectionKey . '.quiz_id');

        if (empty($categoryId) && empty($quizId)) {
            return [];
        }

        $preserved = [];
        if (!empty($categoryId)) {
            $preserved['category_id'] = $categoryId;
        }
        if (!empty($quizId)) {
            $preserved['quiz_id'] = $quizId;
        }

        return [
            $sectionKey => $preserved,
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

            if (empty($normalizedData['quiz_id']) && !empty($this->quizId)) {
                $normalizedData['quiz_id'] = $this->quizId;
            }

            if (empty($normalizedData['category_id']) && !empty($normalizedData['quiz_id'])) {
                $quiz = Quiz::find($normalizedData['quiz_id']);
                if ($quiz) {
                    $normalizedData['category_id'] = $quiz->category_id;
                }
            }

            if (empty($normalizedData['category_id']) && !empty($this->categoryId)) {
                $normalizedData['category_id'] = $this->categoryId;
            }

            if (empty($normalizedData['category_id']) && empty($normalizedData['quiz_id'])) {
                throw new Exception('Subject or quiz is required.');
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
        $quizId = $this->record?->quiz_id;
        if ($quizId) {
            return QuizResource::getUrl('questions', ['record' => $quizId]);
        }

        $categoryId = $this->record?->category_id;
        if ($categoryId) {
            return CategoryResource::getUrl('quizzes', ['record' => $categoryId]);
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
