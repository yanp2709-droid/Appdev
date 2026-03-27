<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Questions\QuestionResource;
use App\Models\Question;
use App\Services\QuestionBank\QuestionBankService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCategoryQuestions extends ViewRecord
{
    protected static string $resource = CategoryResource::class;

    protected string $view = 'filament.resources.categories.pages.view-category-questions';

    public array $selectedQuestionIds = [];

    public function getTitle(): string
    {
        return $this->getRecord()->name . ' Questions';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make('newQuestion')
                ->label('New Question')
                ->url(QuestionResource::getUrl('create')),
            Action::make('importCsv')
                ->label('Import CSV')
                ->form([
                    FileUpload::make('file')
                        ->label('CSV File')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->disk('local')
                        ->directory('question-bank-imports')
                        ->required(),
                ])
                ->action(function (array $data, QuestionBankService $service): void {
                    $result = $service->importCsv($data['file']);
                    $this->notifyImportResult($result);
                }),
            Action::make('importJson')
                ->label('Import JSON')
                ->form([
                    FileUpload::make('file')
                        ->label('JSON File')
                        ->acceptedFileTypes(['application/json', 'text/plain'])
                        ->disk('local')
                        ->directory('question-bank-imports')
                        ->required(),
                ])
                ->action(function (array $data, QuestionBankService $service): void {
                    $result = $service->importJsonFromFile($data['file']);
                    $this->notifyImportResult($result);
                }),
            Action::make('exportCsv')
                ->label('Export CSV')
                ->action(fn (QuestionBankService $service) => $service->exportCsv()),
            Action::make('exportJson')
                ->label('Export JSON')
                ->action(function (QuestionBankService $service) {
                    return response()->streamDownload(function () use ($service) {
                        echo json_encode($service->exportJson(), JSON_PRETTY_PRINT);
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
            ->where('category_id', $this->getRecord()->id)
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
