<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Questions\QuestionResource;
use App\Models\Question;
use Illuminate\Validation\ValidationException;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Exception;

class CreateQuestion extends CreateRecord
{
    protected static string $resource = QuestionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            // Ensure category_id is set
            if (empty($data['category_id'])) {
                throw new Exception('Category is required.');
            }

            // Extract options before validation (options array should not be in Question fillable)
            $options = $data['options'] ?? [];
            
            // Remove options from data as it's not a fillable field on Question
            unset($data['options']);

            // Validate the question data (without options)
            $dataToValidate = $data;
            $dataToValidate['options'] = $options; // Add back for validation
            
            $errors = Question::validatePayload($dataToValidate);

            if (!empty($errors)) {
                throw new Exception(implode(", ", $errors));
            }

            // Just return the question fields (options will be handled in afterCreate)
            return $data;
        } catch (Exception $e) {
            throw ValidationException::withMessages([
                'question_text' => [$e->getMessage()],
            ]);
        }
    }

    protected function afterCreate(): void
    {
        try {
            $data = $this->form->getState();
            $options = $data['options'] ?? [];

            if (!empty($options) && is_array($options)) {
                foreach ($options as $index => $optionData) {
                    if (!isset($optionData['option_text']) || empty($optionData['option_text'])) {
                        continue;
                    }

                    $this->record->options()->create([
                        'option_text' => $optionData['option_text'],
                        'is_correct' => !empty($optionData['is_correct']),
                        'order_index' => $index,
                    ]);
                }
            }
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
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Success!')
            ->body('Question created successfully!');
    }
}

