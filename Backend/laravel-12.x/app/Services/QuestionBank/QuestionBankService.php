<?php

namespace App\Services\QuestionBank;

use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuestionBankService
{
    private const REQUIRED_COLUMNS = [
        'question_text',
        'category',
        'question_type',
        'options',
        'correct_answer',
        'points',
        'answer_key',
    ];

    private const QUESTION_TYPES = ['mcq', 'tf', 'ordering', 'short_answer'];

    public function importCsv(UploadedFile|string $file): array
    {
        $path = $this->resolveFilePath($file);
        if ($path === null) {
            return [
                'status' => 'failed',
                'imported_count' => 0,
                'failed_count' => 1,
                'partial' => false,
                'rejected' => true,
                'errors' => [
                    $this->rowError(1, 'file', 'Uploaded file could not be resolved.'),
                ],
            ];
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            return [
                'status' => 'failed',
                'imported_count' => 0,
                'failed_count' => 1,
                'partial' => false,
                'rejected' => true,
                'errors' => [
                    $this->rowError(1, 'file', 'Unable to read uploaded file.'),
                ],
            ];
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return [
                'status' => 'failed',
                'imported_count' => 0,
                'failed_count' => 1,
                'partial' => false,
                'rejected' => true,
                'errors' => [
                    $this->rowError(1, 'header', 'CSV header row is missing.'),
                ],
            ];
        }

        $headerMap = $this->buildHeaderMap($header);
        $missing = array_values(array_diff(self::REQUIRED_COLUMNS, array_keys($headerMap)));
        if (!empty($missing)) {
            fclose($handle);
            return [
                'status' => 'failed',
                'imported_count' => 0,
                'failed_count' => count($missing),
                'partial' => false,
                'rejected' => true,
                'errors' => array_map(function ($column) {
                    return $this->rowError(1, $column, 'Missing required column.');
                }, $missing),
            ];
        }

        $imported = 0;
        $errors = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            if ($this->isRowEmpty($row)) {
                continue;
            }

            $payload = $this->mapRowToPayload($row, $headerMap);
            $result = $this->validateAndNormalizeRow($payload, $rowNumber);

            if (!empty($result['errors'])) {
                $errors = array_merge($errors, $result['errors']);
                continue;
            }

            $createResult = $this->createQuestionFromNormalized($result['data'], $rowNumber);
            if (!empty($createResult['errors'])) {
                $errors = array_merge($errors, $createResult['errors']);
                continue;
            }

            $imported++;
        }

        fclose($handle);

        return $this->buildImportResponse($imported, $errors);
    }

    public function importJsonFromFile(UploadedFile|string $file): array
    {
        $path = $this->resolveFilePath($file);
        if ($path === null) {
            return [
                'status' => 'failed',
                'imported_count' => 0,
                'failed_count' => 1,
                'partial' => false,
                'rejected' => true,
                'errors' => [
                    $this->rowError(1, 'file', 'Uploaded file could not be resolved.'),
                ],
            ];
        }

        $raw = file_get_contents($path);
        $decoded = json_decode($raw, true);
        $questions = $decoded['questions'] ?? (is_array($decoded) ? $decoded : null);

        return $this->importJsonPayload($questions);
    }

    public function importJsonPayload($questions): array
    {
        if (!is_array($questions)) {
            return [
                'status' => 'failed',
                'imported_count' => 0,
                'failed_count' => 1,
                'partial' => false,
                'rejected' => true,
                'errors' => [
                    $this->rowError(1, 'payload', 'Invalid JSON payload. Expected a "questions" array.'),
                ],
            ];
        }

        $imported = 0;
        $errors = [];

        foreach (array_values($questions) as $index => $payload) {
            $rowNumber = $index + 1;
            if (!is_array($payload)) {
                $errors[] = $this->rowError($rowNumber, 'row', 'Each question must be an object.');
                continue;
            }

            $result = $this->validateAndNormalizeRow($payload, $rowNumber);
            if (!empty($result['errors'])) {
                $errors = array_merge($errors, $result['errors']);
                continue;
            }

            $createResult = $this->createQuestionFromNormalized($result['data'], $rowNumber);
            if (!empty($createResult['errors'])) {
                $errors = array_merge($errors, $createResult['errors']);
                continue;
            }

            $imported++;
        }

        return $this->buildImportResponse($imported, $errors);
    }

    public function exportJson(): array
    {
        $questions = Question::with('options', 'category')->get();

        return [
            'questions' => $questions->map(function (Question $question) {
                return $this->exportQuestionPayload($question);
            })->values(),
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $questions = Question::with('options', 'category')->get();

        $callback = function () use ($questions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, self::REQUIRED_COLUMNS);

            foreach ($questions as $question) {
                $payload = $this->exportQuestionPayload($question);
                $row = [
                    $payload['question_text'],
                    $payload['category'],
                    $payload['question_type'],
                    json_encode($payload['options']),
                    $payload['correct_answer'],
                    $payload['points'],
                    $payload['answer_key'],
                ];
                fputcsv($handle, $row);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, 'question_bank_export.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function buildHeaderMap(array $header): array
    {
        $map = [];
        foreach ($header as $index => $name) {
            $key = $this->normalizeHeader($name);
            if ($key !== '') {
                $map[$key] = $index;
            }
        }

        return $map;
    }

    private function normalizeHeader(string $name): string
    {
        $key = strtolower(trim($name));
        $key = str_replace([' ', '-'], '_', $key);

        return $key;
    }

    private function mapRowToPayload(array $row, array $headerMap): array
    {
        $payload = [];
        foreach ($headerMap as $key => $index) {
            $payload[$key] = $row[$index] ?? null;
        }

        return $payload;
    }

    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function validateAndNormalizeRow(array $payload, int $rowNumber): array
    {
        $errors = [];

        $questionText = trim((string) ($payload['question_text'] ?? ''));
        if ($questionText === '') {
            $errors[] = $this->rowError($rowNumber, 'question_text', 'Question text is required.');
        }

        $categoryValue = $payload['category'] ?? null;
        $categoryId = $this->resolveCategoryId($categoryValue);
        if ($categoryId === null) {
            $errors[] = $this->rowError($rowNumber, 'category', 'Category does not exist.');
        }

        $questionType = strtolower(trim((string) ($payload['question_type'] ?? '')));
        if (!in_array($questionType, self::QUESTION_TYPES, true)) {
            $errors[] = $this->rowError($rowNumber, 'question_type', 'Question type must be mcq, tf, ordering, or short_answer.');
        }

        $pointsRaw = $payload['points'] ?? null;
        $points = 1;
        if ($pointsRaw !== null && trim((string) $pointsRaw) !== '') {
            if (!is_numeric($pointsRaw) || (int) $pointsRaw < 1) {
                $errors[] = $this->rowError($rowNumber, 'points', 'Points must be a positive integer.');
            } else {
                $points = (int) $pointsRaw;
            }
        }

        $answerKey = trim((string) ($payload['answer_key'] ?? ''));

        $optionsResult = $this->normalizeOptions($payload['options'] ?? null);
        if ($optionsResult['error'] !== null) {
            $errors[] = $this->rowError($rowNumber, 'options', $optionsResult['error']);
        }
        $options = $optionsResult['options'];

        $correctAnswerRaw = $payload['correct_answer'] ?? null;
        $correctIndex = null;

        if ($questionType === 'short_answer') {
            if ($answerKey === '') {
                $errors[] = $this->rowError($rowNumber, 'answer_key', 'Answer key is required for short_answer.');
            }
            $options = [];
        } elseif ($questionType === 'ordering') {
            if (count($options) < 2) {
                $errors[] = $this->rowError($rowNumber, 'options', 'Ordering questions need at least 2 options.');
            }
        } elseif (in_array($questionType, ['mcq', 'tf'], true)) {
            if ($questionType === 'tf' && count($options) === 0) {
                $options = ['True', 'False'];
            }

            if (count($options) < 2) {
                $errors[] = $this->rowError($rowNumber, 'options', 'Multiple-choice questions need at least 2 options.');
            }

            $correctResult = $this->normalizeCorrectAnswer($correctAnswerRaw, $options);
            if ($correctResult['error'] !== null) {
                $errors[] = $this->rowError($rowNumber, 'correct_answer', $correctResult['error']);
            } else {
                $correctIndex = $correctResult['index'];
            }

            if ($questionType === 'tf' && count($options) !== 2) {
                $errors[] = $this->rowError($rowNumber, 'options', 'True/False questions must have exactly 2 options.');
            }
        }

        if ($categoryId !== null && $questionText !== '' && $questionType !== '') {
            $duplicateExists = Question::where('category_id', $categoryId)
                ->where('question_text', $questionText)
                ->where('question_type', $questionType)
                ->exists();
            if ($duplicateExists) {
                $errors[] = $this->rowError($rowNumber, 'question_text', 'Duplicate question for this category and type.');
            }
        }

        if (!empty($errors)) {
            return [
                'errors' => $errors,
            ];
        }

        return [
            'data' => [
                'question_text' => $questionText,
                'category_id' => $categoryId,
                'question_type' => $questionType,
                'points' => $points,
                'answer_key' => $questionType === 'short_answer' ? $answerKey : null,
                'options' => $options,
                'correct_index' => $correctIndex,
            ],
            'errors' => [],
        ];
    }

    private function resolveCategoryId($value): ?int
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $category = Category::find((int) $value);
            if ($category) {
                return $category->id;
            }
        }

        $category = Category::whereRaw('LOWER(name) = ?', [mb_strtolower($value)])->first();

        return $category?->id;
    }

    private function normalizeOptions($raw): array
    {
        if (is_array($raw)) {
            $options = array_values(array_filter(array_map('trim', $raw), function ($value) {
                return $value !== '';
            }));

            return ['options' => $options, 'error' => null];
        }

        if ($raw === null) {
            return ['options' => [], 'error' => null];
        }

        $raw = trim((string) $raw);
        if ($raw === '') {
            return ['options' => [], 'error' => null];
        }

        if (str_starts_with($raw, '[')) {
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                return ['options' => [], 'error' => 'Options must be a JSON array or pipe-separated values.'];
            }
            $options = array_values(array_filter(array_map('trim', $decoded), function ($value) {
                return $value !== '';
            }));

            return ['options' => $options, 'error' => null];
        }

        $parts = array_map('trim', explode('|', $raw));
        $options = array_values(array_filter($parts, function ($value) {
            return $value !== '';
        }));

        return ['options' => $options, 'error' => null];
    }

    private function normalizeCorrectAnswer($raw, array $options): array
    {
        if ($raw === null || trim((string) $raw) === '') {
            return ['index' => null, 'error' => 'Correct answer is required.'];
        }

        $rawString = trim((string) $raw);

        if (is_numeric($rawString)) {
            $index = (int) $rawString;
            if ($index < 1 || $index > count($options)) {
                return ['index' => null, 'error' => 'Correct answer index is out of range.'];
            }

            return ['index' => $index, 'error' => null];
        }

        $lower = mb_strtolower($rawString);
        $matchIndex = null;
        foreach ($options as $index => $option) {
            if (mb_strtolower($option) === $lower) {
                if ($matchIndex !== null) {
                    return ['index' => null, 'error' => 'Correct answer matches multiple options.'];
                }
                $matchIndex = $index + 1;
            }
        }

        if ($matchIndex === null) {
            return ['index' => null, 'error' => 'Correct answer must match one of the options or be a 1-based index.'];
        }

        return ['index' => $matchIndex, 'error' => null];
    }

    private function createQuestionFromNormalized(array $data, int $rowNumber): array
    {
        try {
            return DB::transaction(function () use ($data) {
                $question = Question::create([
                    'category_id' => $data['category_id'],
                    'question_type' => $data['question_type'],
                    'question_text' => $data['question_text'],
                    'points' => $data['points'],
                    'answer_key' => $data['answer_key'],
                ]);

                if (in_array($data['question_type'], ['mcq', 'tf'], true)) {
                    foreach ($data['options'] as $index => $optionText) {
                        QuestionOption::create([
                            'question_id' => $question->id,
                            'option_text' => $optionText,
                            'is_correct' => ($index + 1) === $data['correct_index'],
                        ]);
                    }
                } elseif ($data['question_type'] === 'ordering') {
                    foreach ($data['options'] as $index => $optionText) {
                        QuestionOption::create([
                            'question_id' => $question->id,
                            'option_text' => $optionText,
                            'order_index' => $index + 1,
                            'is_correct' => false,
                        ]);
                    }
                }

                return ['errors' => []];
            }, 3);
        } catch (\Throwable $e) {
            return [
                'errors' => [
                    $this->rowError($rowNumber, 'row', 'Failed to save question: ' . $e->getMessage()),
                ],
            ];
        }
    }

    private function buildImportResponse(int $imported, array $errors): array
    {
        $failed = count($errors);
        $status = 'success';
        if ($imported === 0 && $failed > 0) {
            $status = 'failed';
        } elseif ($imported > 0 && $failed > 0) {
            $status = 'partial_success';
        }

        return [
            'status' => $status,
            'imported_count' => $imported,
            'failed_count' => $failed,
            'partial' => $imported > 0 && $failed > 0,
            'rejected' => $imported === 0 && $failed > 0,
            'errors' => $errors,
        ];
    }

    private function exportQuestionPayload(Question $question): array
    {
        $options = $question->options->pluck('option_text')->values()->all();
        $correctIndex = null;

        if (in_array($question->question_type, ['mcq', 'tf'], true)) {
            $correct = $question->options->firstWhere('is_correct', true);
            if ($correct) {
                $correctIndex = $question->options->search(function ($option) use ($correct) {
                    return $option->id === $correct->id;
                });
                $correctIndex = $correctIndex === false ? null : $correctIndex + 1;
            }
        }

        return [
            'question_text' => $question->question_text,
            'category' => $question->category?->name ?? '',
            'question_type' => $question->question_type,
            'options' => $options,
            'correct_answer' => $correctIndex,
            'points' => $question->points,
            'answer_key' => $question->question_type === 'short_answer' ? $question->answer_key : null,
        ];
    }

    private function rowError(int $rowNumber, string $field, string $message): array
    {
        return [
            'row' => $rowNumber,
            'field' => $field,
            'message' => $message,
        ];
    }

    private function resolveFilePath(UploadedFile|string $file): ?string
    {
        if ($file instanceof UploadedFile) {
            return $file->getRealPath() ?: null;
        }

        $disk = Storage::disk('local');
        if (!$disk->exists($file)) {
            return null;
        }

        return $disk->path($file);
    }
}
