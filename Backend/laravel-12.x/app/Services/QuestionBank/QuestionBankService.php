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

    private const QUESTION_TYPES = ['mcq', 'multiple_choice', 'tf', 'true_false', 'multi_select', 'short_answer'];

    public function importCsv(UploadedFile|string $file, ?int $categoryId = null): array
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
            $result = $this->validateAndNormalizeRow($payload, $rowNumber, $categoryId);

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

    public function importJsonFromFile(UploadedFile|string $file, ?int $categoryId = null): array
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

        return $this->importJsonPayload($questions, $categoryId);
    }

    public function importJsonPayload($questions, ?int $categoryId = null): array
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

            $result = $this->validateAndNormalizeRow($payload, $rowNumber, $categoryId);
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

    public function exportJson(?int $categoryId = null): array
    {
        $questions = Question::with('options', 'category')
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->get();

        return [
            'questions' => $questions->map(function (Question $question) {
                return $this->exportQuestionPayload($question);
            })->values(),
        ];
    }

    public function exportCsv(?int $categoryId = null): StreamedResponse
    {
        $questions = Question::with('options', 'category')
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->get();

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
                    is_array($payload['correct_answer']) ? json_encode($payload['correct_answer']) : $payload['correct_answer'],
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

    private function validateAndNormalizeRow(array $payload, int $rowNumber, ?int $forcedCategoryId = null): array
    {
        $errors = [];

        $questionText = trim((string) ($payload['question_text'] ?? ''));
        if ($questionText === '') {
            $errors[] = $this->rowError($rowNumber, 'question_text', 'Question text is required.');
        }

        $categoryId = $forcedCategoryId;
        if ($categoryId === null) {
            $categoryValue = $payload['category'] ?? null;
            $categoryId = $this->resolveCategoryId($categoryValue);
            if ($categoryId === null) {
                $errors[] = $this->rowError($rowNumber, 'category', 'Category does not exist.');
            }
        } elseif (! Category::whereKey($categoryId)->exists()) {
            $errors[] = $this->rowError($rowNumber, 'category', 'Category does not exist.');
        }

        $questionType = strtolower(trim((string) ($payload['question_type'] ?? '')));
        $questionType = Question::normalizeQuestionType($questionType);

        if (!in_array($questionType, [Question::TYPE_MCQ, Question::TYPE_TRUE_FALSE, Question::TYPE_MULTI_SELECT, Question::TYPE_SHORT_ANSWER], true)) {
            $errors[] = $this->rowError($rowNumber, 'question_type', 'Question type must be multiple_choice, true_false, multi_select, or short_answer.');
        }

        $pointsRaw = $payload['points'] ?? null;
        $points = 1;
        if ($pointsRaw !== null && trim((string) $pointsRaw) !== '') {
            if (!is_numeric($pointsRaw) || (float) $pointsRaw <= 0 || (float) $pointsRaw != floor((float) $pointsRaw)) {
                $errors[] = $this->rowError($rowNumber, 'points', 'Points must be a positive integer.');
            } elseif ((int) $pointsRaw > 1000) {
                $errors[] = $this->rowError($rowNumber, 'points', 'Points cannot exceed 1000.');
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
        $correctIndexes = [];

        if ($questionType === Question::TYPE_SHORT_ANSWER) {
            if ($answerKey === '') {
                $errors[] = $this->rowError($rowNumber, 'answer_key', 'Answer key is required for short_answer.');
            }
            $options = [];
        } elseif (in_array($questionType, [Question::TYPE_MCQ, Question::TYPE_TRUE_FALSE, Question::TYPE_MULTI_SELECT], true)) {
            if ($questionType === Question::TYPE_TRUE_FALSE && count($options) === 0) {
                $options = ['True', 'False'];
            }

            if (count($options) < 2) {
                $errors[] = $this->rowError($rowNumber, 'options', 'Choice-based questions need at least 2 options.');
            }

            $correctResult = $this->normalizeCorrectAnswer($correctAnswerRaw, $options, $questionType);
            if ($correctResult['error'] !== null) {
                $errors[] = $this->rowError($rowNumber, 'correct_answer', $correctResult['error']);
            } else {
                $correctIndexes = $correctResult['indexes'];
            }

            if ($questionType === Question::TYPE_TRUE_FALSE && count($options) !== 2) {
                $errors[] = $this->rowError($rowNumber, 'options', 'True/False questions must have exactly 2 options.');
            }
        }

        if ($categoryId !== null && $questionText !== '' && !empty($questionType)) {
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
                'answer_key' => $questionType === Question::TYPE_SHORT_ANSWER ? $answerKey : null,
                'options' => $options,
                'correct_indexes' => $correctIndexes,
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

    private function normalizeCorrectAnswer($raw, array $options, string $questionType): array
    {
        if ($raw === null || trim((string) $raw) === '') {
            return ['indexes' => [], 'error' => 'Correct answer is required.'];
        }

        $rawValues = is_array($raw) ? $raw : preg_split('/\s*\|\s*/', trim((string) $raw));
        $rawValues = array_values(array_filter(array_map(function ($value) {
            return trim((string) $value);
        }, $rawValues), fn ($value) => $value !== ''));

        if (empty($rawValues)) {
            return ['indexes' => [], 'error' => 'Correct answer is required.'];
        }

        $indexes = [];

        foreach ($rawValues as $rawString) {
            $lower = mb_strtolower($rawString);
            $matchIndex = null;
            foreach ($options as $index => $option) {
                if (mb_strtolower($option) === $lower) {
                    if ($matchIndex !== null) {
                        return ['indexes' => [], 'error' => 'Correct answer matches multiple options.'];
                    }
                    $matchIndex = $index + 1;
                }
            }

            if ($matchIndex !== null) {
                $indexes[] = $matchIndex;
                continue;
            }

            if (is_numeric($rawString)) {
                $index = (int) $rawString;
                if ($index < 1 || $index > count($options)) {
                    return ['indexes' => [], 'error' => 'Correct answer index is out of range.'];
                }

                $indexes[] = $index;
                continue;
            }

            return ['indexes' => [], 'error' => 'Correct answer must match one of the options or be a 1-based index.'];
        }

        $indexes = array_values(array_unique($indexes));

        if ($questionType !== Question::TYPE_MULTI_SELECT && count($indexes) !== 1) {
            return ['indexes' => [], 'error' => 'This question type must have exactly 1 correct answer.'];
        }

        return ['indexes' => $indexes, 'error' => null];
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

                if (in_array($data['question_type'], [Question::TYPE_MCQ, Question::TYPE_TRUE_FALSE, Question::TYPE_MULTI_SELECT], true)) {
                    foreach ($data['options'] as $index => $optionText) {
                        QuestionOption::create([
                            'question_id' => $question->id,
                            'option_text' => $optionText,
                            'is_correct' => in_array($index + 1, $data['correct_indexes'], true),
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
        $correctAnswer = null;

        if (in_array($question->question_type, [Question::TYPE_MCQ, Question::TYPE_TRUE_FALSE, Question::TYPE_MULTI_SELECT], true)) {
            $correctIndexes = $question->options
                ->filter(fn ($option) => (bool) $option->is_correct)
                ->map(function ($correct) use ($question) {
                    $index = $question->options->search(fn ($option) => $option->id === $correct->id);

                    return $index === false ? null : $index + 1;
                })
                ->filter(fn ($index) => $index !== null)
                ->values()
                ->all();

            $correctAnswer = $question->question_type === Question::TYPE_MULTI_SELECT
                ? $correctIndexes
                : ($correctIndexes[0] ?? null);
        }

        return [
            'id' => $question->id,
            'question_text' => $question->question_text,
            'category' => $question->category?->name ?? '',
            'question_type' => $question->question_type,
            'options' => $options,
            'correct_answer' => $correctAnswer,
            'points' => $question->points,
            'answer_key' => $question->question_type === Question::TYPE_SHORT_ANSWER ? $question->answer_key : null,
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
