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

        $result = $service->importCsv($request->file('file'), $request->integer('category_id') ?: null);

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

            $result = $service->importJsonFromFile($request->file('file'), $request->integer('category_id') ?: null);
        } else {
            $payload = $request->json()->all();
            $questions = $payload['questions'] ?? (is_array($payload) ? $payload : null);
            $result = $service->importJsonPayload($questions, $request->integer('category_id') ?: null);
        }

        return response()->json($result);
    }

    public function exportJson(QuestionBankService $service)
    {
        return response()->json($service->exportJson(request()->integer('category_id') ?: null));
    }

    public function exportCsv(QuestionBankService $service)
    {
        return $service->exportCsv(request()->integer('category_id') ?: null);
    }
}
