<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\QuizzesController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\Admin\QuestionBankController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Api\AuthController as ApiAuthController;

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
    Route::post('/register', [AuthController::class, 'register']);
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

// Public read-only routes (use auth if you need to restrict later)
Route::get('/categories', [CategoriesController::class, 'index']);
Route::get('/questions', [QuestionController::class, 'index']);

// Quiz routes protected by role
Route::middleware(['auth:sanctum'])->group(function () {
    // Teacher-only routes
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('/quiz/create', [QuizzesController::class, 'store']);
    });

    // Student-only routes
    Route::middleware('role:student')->group(function () {
        Route::post('/quiz/attempt', [QuizAttemptController::class, 'attempt']);
        Route::get('/quiz/attempts', [QuizAttemptController::class, 'history']);
        Route::post('/quiz/attempts/{attempt}/answer', [QuizAttemptController::class, 'saveAnswer']);
        Route::post('/quiz/attempts/{attempt}/submit', [QuizAttemptController::class, 'submit']);
        Route::get('/quiz/attempts/{attempt}/detail', [QuizAttemptController::class, 'detail']);
        Route::get('/quiz/attempts/{attempt}', [QuizAttemptController::class, 'status']);
    });
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Admin routes - protected by role
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/admin/categories', [CategoriesController::class, 'store']);
});

Route::middleware(['auth:sanctum', 'role:admin|teacher'])->prefix('admin/questions')->group(function () {
    Route::post('/import/csv', [QuestionBankController::class, 'importCsv']);
    Route::post('/import/json', [QuestionBankController::class, 'importJson']);
    Route::get('/export/csv', [QuestionBankController::class, 'exportCsv']);
    Route::get('/export/json', [QuestionBankController::class, 'exportJson']);
});

// Admin Statistics and Analytics routes
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/statistics/dashboard', [AdminDashboardController::class, 'dashboard']);
    Route::get('/statistics/students', [AdminDashboardController::class, 'students']);
    Route::get('/statistics/student/{studentId}', [AdminDashboardController::class, 'studentDetail']);
    Route::get('/statistics/attempts', [AdminDashboardController::class, 'attempts']);
    Route::get('/statistics/attempt-history', [AdminDashboardController::class, 'studentAttemptHistory']);
    Route::get('/statistics/categories', [AdminDashboardController::class, 'categoryStats']);
});

