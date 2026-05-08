<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\QuestionBank\QuestionBankService;

class QuestionBankController extends Controller
{
    public function importCsv(Request $request, QuestionBankService $service)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:5120',
            ],
        ]);

        if ($request->filled('quiz_id')) {
            $result = $service->importCsvForQuiz($request->file('file'), $request->integer('quiz_id'));
        } else {
            $result = $service->importCsv($request->file('file'), $request->integer('category_id') ?: null);
        }

        // If service detected that file is not a valid CSV, return 422
        if ($result['rejected'] && isset($result['errors'])) {
            foreach ($result['errors'] as $error) {
                // Check for file/header level errors or missing required column errors
                if (in_array($error['field'], ['file', 'header']) ||
                    strpos($error['message'] ?? '', 'Missing required column') !== false) {
                    return response()->json($result, 422);
                }
            }
        }

        return response()->json($result);
    }

    public function importJson(Request $request, QuestionBankService $service)
    {
        if ($request->hasFile('file')) {
            $request->validate([
                'file' => [
                    'required',
                    'file',
                    'mimetypes:application/json,text/plain',
                    'max:5120',
                ],
            ]);

            $result = $request->filled('quiz_id')
                ? $service->importJsonFromFileForQuiz($request->file('file'), $request->integer('quiz_id'))
                : $service->importJsonFromFile($request->file('file'), $request->integer('category_id') ?: null);
        } else {
            $payload = $request->json()->all();
            $questions = $payload['questions'] ?? (is_array($payload) ? $payload : null);
            $result = $request->filled('quiz_id')
                ? $service->importJsonPayloadForQuiz($questions, $request->integer('quiz_id'))
                : $service->importJsonPayload($questions, $request->integer('category_id') ?: null);
        }

        return response()->json($result);
    }

    public function exportJson(QuestionBankService $service)
    {
        if (request()->filled('quiz_id')) {
            return response()->json($service->exportJsonForQuiz(request()->integer('quiz_id')));
        }

        return response()->json($service->exportJson(request()->integer('category_id') ?: null));
    }

    public function exportCsv(QuestionBankService $service)
    {
        if (request()->filled('quiz_id')) {
            return $service->exportCsvForQuiz(request()->integer('quiz_id'));
        }

        return $service->exportCsv(request()->integer('category_id') ?: null);
    }
}
