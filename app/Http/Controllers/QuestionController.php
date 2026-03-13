<?php

namespace App\Http\Controllers;

use App\Models\Question;
use Illuminate\Http\Request;
use App\Http\Requests\QuestionFetchRequest;
use App\Http\Resources\QuestionResource;

class QuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   public function index(QuestionFetchRequest $request)
    {
        try {
            set_time_limit(60);

            $query = Question::with('options')
                ->where('category_id', $request->category_id);

            if ($request->random) {
                $query->inRandomOrder();
            }

            $limit = $request->limit ?? 10;

            $questions = $query->timeout(10)->limit($limit)->get();

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
