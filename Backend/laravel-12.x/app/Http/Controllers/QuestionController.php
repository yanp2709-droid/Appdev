<?php

namespace App\Http\Controllers;

use App\Http\Traits\ApiResponse;
use App\Models\Question;
use Illuminate\Http\Request;
use App\Http\Requests\QuestionFetchRequest;
use App\Http\Resources\QuestionResource;
use Illuminate\Support\Facades\Cache;

class QuestionController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
   public function index(QuestionFetchRequest $request)
    {
        try {
            set_time_limit(60);

            $limit = $request->limit ?? 10;
            $random = (bool) $request->random;

            $cacheKey = "questions:cat:{$request->category_id}:limit:{$limit}:rand:" . ($random ? '1' : '0');

            $questions = Cache::remember($cacheKey, 300, function () use ($request, $limit, $random) {
                $query = Question::with(['options' => function ($q) {
                    $q->select('id', 'question_id', 'option_text', 'order_index');
                }])
                    ->select('id', 'category_id', 'question_type', 'question_text', 'points')
                    ->where('category_id', $request->category_id);

                if ($random) {
                    $query->inRandomOrder();
                }

                return $query->limit($limit)->get();
            });

            return QuestionResource::collection($questions);
        } catch (\Throwable $e) {
            \Log::error('Questions API Error: ' . $e->getMessage());

            return response()->json([
                'data' => [],
                'message' => 'Error: ' . $e->getMessage(),
                'error' => $e->getCode()
            ], 500);
        }
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Question $question)
    {
        //
    }

    public function preview(Request $request, Question $question)
    {
        if (!$request->user() || !($request->user()->isAdmin() || $request->user()->isTeacher())) {
            return $this->forbidden('Only admins and teachers can preview question payloads.');
        }

        if (!$question->isPreviewReady()) {
            return $this->error(
                'validation_error',
                'Question is not preview-ready.',
                422,
                $question->getValidationErrors()
            );
        }

        return $this->success([
            'question' => $question->getPreviewPayload($request->boolean('include_correct_answers')),
        ], 'Question preview generated.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Question $question)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Question $question)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Question $question)
    {
        //
    }
}
