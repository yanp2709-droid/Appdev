<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/test', function () {
    return response()->json(['message' => 'API route is working!']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/quiz/create', [QuizController::class, 'store']);
});

Route::middleware(['auth:sanctum', 'role:teacher'])->group(function () {
    Route::get('/quiz/my', [QuizController::class, 'myQuizzes']);
});

Route::middleware(['auth:sanctum', 'role:student'])->group(function () {
    Route::post('/quiz/attempt', [QuizAttemptController::class, 'attempt']);
});