<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Result;

class ResultsController extends Controller
{
    public function latest(Request $request)
    {
        $student = $request->user();

        $result = Result::where('student_id', $student->id)
            ->where('status', 'completed')
            ->latest('submitted_at')
            ->first();

        if (!$result) {
            return response()->json([
                'message' => 'No results found',
                'data' => null
            ]);
        }

        return response()->json([
            'score' => $result->score,
            'correct_answers' => $result->correct_answers,
            'total_items' => $result->total_items,
            'submitted_at' => $result->submitted_at
        ]);
    }

    public function history(Request $request)
    {
        $student = $request->user();

        $results = Result::where('student_id', $student->id)
            ->where('status', 'completed')
            ->orderBy('submitted_at', 'desc')
            ->get();

        return response()->json($results);
    }
}