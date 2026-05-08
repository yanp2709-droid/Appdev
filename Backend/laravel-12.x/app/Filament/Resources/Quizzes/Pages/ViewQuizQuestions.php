<?php

namespace App\Filament\Resources\Quizzes\Pages;

use App\Filament\Resources\Questions\QuestionResource;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Quizzes\QuizResource;
use App\Models\Question;
use App\Services\QuestionBank\QuestionBankService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewQuizQuestions extends ViewRecord
{
    protected static string $resource = QuizResource::class;

    protected string $view = 'filament.resources.quizzes.pages.view-quiz-questions';

    public array $selectedQuestionIds = [];

    public function getTitle(): string
    {
        return $this->getRecord()->title . ' Questions';
    }

    public function hasResourceBreadcrumbs(): bool
    {
        return false;
    }

    public function getBreadcrumbs(): array
    {
        $category = $this->getRecord()->category;
        $breadcrumbs = [
            CategoryResource::getUrl('index') => 'Subject',
        ];

        if ($category) {
            $breadcrumbs[CategoryResource::getUrl('quizzes', ['record' => $category])] = $category->name;
        }

        $breadcrumbs[] = $this->getRecord()->title;

        return $breadcrumbs;
    }

    protected function getHeaderActions(): array
    {
        $academicYearService = app(\App\Services\AcademicYearService::class);
        $isCurrentYear = $academicYearService->getSelectedAcademicYear() === $academicYearService->getCurrentAcademicYear();

        return [
            CreateAction::make('newQuestion')
                ->label('New Question')
                ->url(fn () => QuestionResource::getUrl('create') . '?quiz_id=' . $this->getRecord()->id)
                ->disabled(! $isCurrentYear)
                ->tooltip(fn (): ?string => $isCurrentYear ? null : 'New questions can only be created for the current academic year'),
            Action::make('importCsv')
                ->label('Import CSV')
                ->disabled(! $isCurrentYear)
                ->tooltip(fn (): ?string => $isCurrentYear ? null : 'Imports can only be performed for the current academic year')
                ->form([
                    FileUpload::make('file')
                        ->label('CSV File')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->disk('local')
                        ->directory('question-bank-imports')
                        ->required(),
                ])
                ->action(function (array $data, QuestionBankService $service): void {
                    $result = $service->importCsvForQuiz($data['file'], $this->getRecord()->id);
                    $this->notifyImportResult($result);
                }),
            Action::make('importJson')
                ->label('Import JSON')
                ->disabled(! $isCurrentYear)
                ->tooltip(fn (): ?string => $isCurrentYear ? null : 'Imports can only be performed for the current academic year')
                ->form([
                    FileUpload::make('file')
                        ->label('JSON File')
                        ->acceptedFileTypes(['application/json', 'text/plain'])
                        ->disk('local')
                        ->directory('question-bank-imports')
                        ->required(),
                ])
                ->action(function (array $data, QuestionBankService $service): void {
                    $result = $service->importJsonFromFileForQuiz($data['file'], $this->getRecord()->id);
                    $this->notifyImportResult($result);
                }),
            Action::make('exportCsv')
                ->label('Export CSV')
                ->action(fn (QuestionBankService $service) => $service->exportCsvForQuiz($this->getRecord()->id)),
            Action::make('exportJson')
                ->label('Export JSON')
                ->action(function (QuestionBankService $service) {
                    return response()->streamDownload(function () use ($service) {
                        echo json_encode($service->exportJsonForQuiz($this->getRecord()->id), JSON_PRETTY_PRINT);
                    }, 'question_bank_export.json', [
                        'Content-Type' => 'application/json',
                    ]);
                }),
        ];
    }

    public function getQuestions(): \Illuminate\Support\Collection
    {
        return $this->getRecord()
            ->questions()
            ->orderByDesc('created_at')
            ->get();
    }

    public function deleteSelectedQuestions(): void
    {
        $questionIds = collect($this->selectedQuestionIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($questionIds->isEmpty()) {
            return;
        }

        Question::query()
            ->whereIn('id', $questionIds)
            ->where('quiz_id', $this->getRecord()->id)
            ->delete();

        $deletedCount = $questionIds->count();
        $this->selectedQuestionIds = [];

        Notification::make()
            ->title('Questions deleted')
            ->body($deletedCount . ' question(s) were deleted successfully.')
            ->success()
            ->send();
    }

    private function notifyImportResult(array $result): void
    {
        $errors = $result['errors'] ?? [];
        $status = $result['status'] ?? 'success';

        $bodyLines = [
            'Imported: ' . ($result['imported_count'] ?? 0),
            'Failed: ' . ($result['failed_count'] ?? 0),
        ];

        if (! empty($errors)) {
            $preview = array_slice($errors, 0, 5);

            foreach ($preview as $error) {
                $bodyLines[] = 'Row ' . $error['row'] . ' ' . $error['field'] . ': ' . $error['message'];
            }

            if (count($errors) > 5) {
                $bodyLines[] = '...and ' . (count($errors) - 5) . ' more.';
            }
        }

        Notification::make()
            ->title($status === 'success' ? 'Import complete' : 'Import finished with issues')
            ->body(implode("\n", $bodyLines))
            ->status($status === 'success' ? 'success' : 'warning')
            ->send();
    }
}
