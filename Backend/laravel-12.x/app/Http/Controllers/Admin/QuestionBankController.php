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
                'mimetypes:text/csv,text/plain,application/vnd.ms-excel',
                'max:5120',
            ],
        ]);

        $result = $service->importCsv($request->file('file'));

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

            $result = $service->importJsonFromFile($request->file('file'));
        } else {
            $payload = $request->json()->all();
            $questions = $payload['questions'] ?? (is_array($payload) ? $payload : null);
            $result = $service->importJsonPayload($questions);
        }

        return response()->json($result);
    }

    public function exportJson(QuestionBankService $service)
    {
        return response()->json($service->exportJson());
    }

    public function exportCsv(QuestionBankService $service)
    {
        return $service->exportCsv();
    }
}
