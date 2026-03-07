<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\QuizzesController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\QuestionController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your TechQuiz app.
|
*/

// Public / test route
Route::get('/test', function () {
    return response()->json(['message' => 'API route is working!']);
});

// Auth routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    
    // Routes requiring authentication
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// User routes (alternative direct /user)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Quiz routes protected by role
Route::middleware(['auth:sanctum'])->group(function () {

    
    // Teacher-only routes
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/quiz/create', [QuizzesController::class, 'store']);
    });

    // Student-only routes
    Route::middleware('role:student')->group(function () {
        Route::post('/quiz/attempt', [QuizAttemptController::class, 'attempt']);
    });

    Route::get('/categories', [CategoriesController::class, 'index']);

    Route::get('/questions', [QuestionController::class, 'index']);

});

