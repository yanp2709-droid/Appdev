<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Test database connection and timeout
            set_time_limit(60);

            $categories = Category::where('is_published', true)
                ->select('id', 'name', 'description')
                ->timeout(10)
                ->get();

            return response()->json([
                'data' => $categories,
                'message' => 'Success'
            ]);
        } catch (\Throwable $e) {
            \Log::error('Categories API Error: ' . $e->getMessage());

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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $category = Category::create($validated);

        return response()->json([
            'data' => $category,
            'message' => 'Category created successfully'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        //
    }
}
