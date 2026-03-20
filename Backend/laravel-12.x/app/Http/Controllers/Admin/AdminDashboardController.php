<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use App\Models\Quiz_attempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    use ApiResponse;

    /**
     * Get dashboard statistics overview
     */
    public function dashboard()
    {
        $totalStudents = User::where('role', 'student')->count();
        $totalAttempts = Quiz_attempt::count();
        $submittedAttempts = Quiz_attempt::where('status', 'submitted')->count();
        $avgScore = Quiz_attempt::where('status', 'submitted')->avg('score_percent') ?? 0;

        return $this->success([
            'statistics' => [
                'total_students' => $totalStudents,
                'total_attempts' => $totalAttempts,
                'submitted_attempts' => $submittedAttempts,
                'average_score' => round($avgScore, 2),
            ],
        ], 'Dashboard statistics retrieved.');
    }

    /**
     * Get all students with basic information and attempt counts
     */
    public function students(Request $request)
    {
        $perPage = $request->query('per_page', 20);
        $search = $request->query('search');

        $query = User::where('role', 'student')
            ->select('id', 'name', 'email', 'created_at');

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        }

        $students = $query->paginate($perPage);

        // Add attempt counts and average scores for each student
        $studentsData = $students->getCollection()->map(function ($student) {
            $attempts = Quiz_attempt::where('student_id', $student->id);
            $submittedAttempts = (clone $attempts)->where('status', 'submitted');

            return [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'created_at' => $student->created_at,
                'total_attempts' => $attempts->count(),
                'submitted_attempts' => $submittedAttempts->count(),
                'average_score' => round($submittedAttempts->avg('score_percent') ?? 0, 2),
            ];
        });

        return $this->success([
            'students' => $studentsData,
            'pagination' => [
                'total' => $students->total(),
                'per_page' => $students->perPage(),
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'from' => $students->firstItem(),
                'to' => $students->lastItem(),
            ],
        ], 'Students retrieved.');
    }

    /**
     * Get detailed information for a specific student
     */
    public function studentDetail($studentId)
    {
        $student = User::find($studentId);

        if (!$student || $student->role !== 'student') {
            return $this->error('not_found', 'Student not found.', 404);
        }

        $attempts = Quiz_attempt::where('student_id', $studentId)->with('quiz.category');
        $submittedAttempts = (clone $attempts)->where('status', 'submitted');

        $studentData = [
            'id' => $student->id,
            'name' => $student->name,
            'email' => $student->email,
            'created_at' => $student->created_at,
            'total_attempts' => $attempts->count(),
            'submitted_attempts' => $submittedAttempts->count(),
            'in_progress_attempts' => (clone $attempts)->where('status', 'in_progress')->count(),
            'expired_attempts' => (clone $attempts)->where('status', 'expired')->count(),
            'average_score' => round($submittedAttempts->avg('score_percent') ?? 0, 2),
            'highest_score' => round($submittedAttempts->max('score_percent') ?? 0, 2),
            'lowest_score' => round($submittedAttempts->min('score_percent') ?? 0, 2),
        ];

        return $this->success([
            'student' => $studentData,
        ], 'Student details retrieved.');
    }

    /**
     * Get all quiz attempts with optional filtering
     */
    public function attempts(Request $request)
    {
        $perPage = $request->query('per_page', 20);
        $status = $request->query('status'); // submitted, in_progress, expired
        $categoryId = $request->query('category_id');
        $studentId = $request->query('student_id');

        $query = Quiz_attempt::with(['student:id,name,email', 'quiz.category']);

        // Apply filters
        if ($status) {
            $query->where('status', $status);
        }
        if ($categoryId) {
            $query->whereHas('quiz', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }
        if ($studentId) {
            $query->where('student_id', $studentId);
        }

        $attempts = $query->orderByDesc('submitted_at')
            ->orderByDesc('started_at')
            ->paginate($perPage);

        $attemptsData = $attempts->getCollection()->map(function ($attempt) {
            return [
                'id' => $attempt->id,
                'student_id' => $attempt->student_id,
                'student_name' => $attempt->student->name,
                'student_email' => $attempt->student->email,
                'quiz_id' => $attempt->quiz_id,
                'category_name' => $attempt->quiz->category->name ?? 'Unknown',
                'status' => $attempt->status,
                'started_at' => $attempt->started_at,
                'submitted_at' => $attempt->submitted_at,
                'total_items' => $attempt->total_items,
                'answered_count' => $attempt->answered_count,
                'correct_answers' => $attempt->correct_answers,
                'score_percent' => $attempt->score_percent,
            ];
        });

        return $this->success([
            'attempts' => $attemptsData,
            'pagination' => [
                'total' => $attempts->total(),
                'per_page' => $attempts->perPage(),
                'current_page' => $attempts->currentPage(),
                'last_page' => $attempts->lastPage(),
                'from' => $attempts->firstItem(),
                'to' => $attempts->lastItem(),
            ],
        ], 'Attempts retrieved.');
    }

    /**
     * Get students with their recent attempts (for dashboard view)
     */
    public function studentAttemptHistory(Request $request)
    {
        $perPage = $request->query('per_page', 20);

        $students = User::where('role', 'student')
            ->with(['attempts' => function ($query) {
                $query->where('status', 'submitted')
                    ->orderByDesc('submitted_at')
                    ->limit(5); // Last 5 submissions per student
            }])
            ->paginate($perPage);

        $studentsWithAttempts = $students->getCollection()->map(function ($student) {
            $attempts = $student->attempts->map(function ($attempt) {
                return [
                    'id' => $attempt->id,
                    'category' => $attempt->quiz->category->name ?? 'Unknown',
                    'score' => $attempt->score_percent,
                    'submitted_at' => $attempt->submitted_at,
                ];
            });

            return [
                'student_id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'recent_attempts' => $attempts,
                'total_submitted' => Quiz_attempt::where('student_id', $student->id)
                    ->where('status', 'submitted')
                    ->count(),
            ];
        });

        return $this->success([
            'data' => $studentsWithAttempts,
            'pagination' => [
                'total' => $students->total(),
                'per_page' => $students->perPage(),
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
            ],
        ], 'Student attempt history retrieved.');
    }

    /**
     * Get statistics by category
     */
    public function categoryStats()
    {
        $stats = DB::table('quiz_attempts')
            ->join('quizzes', 'quiz_attempts.quiz_id', '=', 'quizzes.id')
            ->join('categories', 'quizzes.category_id', '=', 'categories.id')
            ->where('quiz_attempts.status', 'submitted')
            ->groupBy('categories.id', 'categories.name')
            ->select(
                'categories.id',
                'categories.name',
                DB::raw('COUNT(*) as attempt_count'),
                DB::raw('AVG(quiz_attempts.score_percent) as average_score'),
                DB::raw('MAX(quiz_attempts.score_percent) as highest_score'),
                DB::raw('MIN(quiz_attempts.score_percent) as lowest_score')
            )
            ->orderByDesc('attempt_count')
            ->get();

        $categoryStats = $stats->map(function ($stat) {
            return [
                'category_id' => $stat->id,
                'category_name' => $stat->name,
                'total_attempts' => $stat->attempt_count,
                'average_score' => round($stat->average_score, 2),
                'highest_score' => round($stat->highest_score, 2),
                'lowest_score' => round($stat->lowest_score, 2),
            ];
        });

        return $this->success([
            'statistics' => $categoryStats,
        ], 'Category statistics retrieved.');
    }
}
