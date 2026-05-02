<?php

namespace App\Services;

use App\Models\Quiz_attempt;
use App\Models\User;
use App\Models\Quiz;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class QuizStatisticsService
{
    /**
     * Get overall statistics for all quiz attempts
     */
    public function getOverallStatistics(): array
    {
        $totalStudents = User::where('role', 'student')->count();
        $totalAttempts = Quiz_attempt::count();
        $submittedAttempts = Quiz_attempt::where('status', 'submitted')->count();
        
        $submittedQuery = Quiz_attempt::where('status', 'submitted')
            ->where('attempt_type', Quiz_attempt::TYPE_GRADED);
        $averageScore = $submittedQuery->avg('score_percent') ?? 0;
        $highestScore = $submittedQuery->max('score_percent') ?? 0;
        $lowestScore = $submittedQuery->min('score_percent') ?? 0;

        return [
            'total_students' => $totalStudents,
            'total_attempts' => $totalAttempts,
            'submitted_attempts' => $submittedAttempts,
            'in_progress_attempts' => Quiz_attempt::where('status', 'in_progress')->count(),
            'expired_attempts' => Quiz_attempt::where('status', 'expired')->count(),
            'average_score' => round($averageScore, 2),
            'highest_score' => round($highestScore, 2),
            'lowest_score' => round($lowestScore, 2),
            'completion_rate' => $totalAttempts > 0 
                ? round(($submittedAttempts / $totalAttempts) * 100, 2)
                : 0,
        ];
    }

    /**
     * Get statistics for a specific student
     */
    public function getStudentStatistics(int $studentId): array
    {
        $student = User::find($studentId);

        if (!$student || $student->role !== 'student') {
            return [];
        }

        $attempts = $student->quizAttempts();
        $submittedAttempts = (clone $attempts)
            ->where('status', 'submitted')
            ->where('attempt_type', Quiz_attempt::TYPE_GRADED);
        $submittedCount = $submittedAttempts->count();

        return [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'student_email' => $student->email,
            'total_attempts' => $attempts->count(),
            'submitted_attempts' => $submittedCount,
            'in_progress_attempts' => (clone $attempts)->where('status', 'in_progress')->count(),
            'expired_attempts' => (clone $attempts)->where('status', 'expired')->count(),
            'average_score' => round($submittedAttempts->avg('score_percent') ?? 0, 2),
            'highest_score' => round($submittedAttempts->max('score_percent') ?? 0, 2),
            'lowest_score' => round($submittedAttempts->min('score_percent') ?? 0, 2),
            'completion_rate' => $attempts->count() > 0
                ? round(($submittedCount / $attempts->count()) * 100, 2)
                : 0,
        ];
    }

    /**
     * Get statistics grouped by quiz
     */
    public function getQuizStatistics(): array
    {
        $stats = DB::table('quiz_attempts')
            ->join('quizzes', 'quiz_attempts.quiz_id', '=', 'quizzes.id')
            ->where('quiz_attempts.status', 'submitted')
            ->where('quiz_attempts.attempt_type', Quiz_attempt::TYPE_GRADED)
            ->groupBy('quizzes.id', 'quizzes.title')
            ->select(
                'quizzes.id',
                'quizzes.title',
                DB::raw('COUNT(*) as attempt_count'),
                DB::raw('AVG(quiz_attempts.score_percent) as average_score'),
                DB::raw('MAX(quiz_attempts.score_percent) as highest_score'),
                DB::raw('MIN(quiz_attempts.score_percent) as lowest_score'),
                DB::raw('STDDEV(quiz_attempts.score_percent) as score_std_dev')
            )
            ->orderByDesc('attempt_count')
            ->get();

        return $stats->map(function ($stat) {
            return [
                'quiz_id' => $stat->id,
                'quiz_title' => $stat->title,
                'total_attempts' => $stat->attempt_count,
                'average_score' => round($stat->average_score ?? 0, 2),
                'highest_score' => round($stat->highest_score ?? 0, 2),
                'lowest_score' => round($stat->lowest_score ?? 0, 2),
                'score_std_dev' => round($stat->score_std_dev ?? 0, 2),
            ];
        })->toArray();
    }

    /**
     * Get statistics grouped by category
     */
    public function getCategoryStatistics(): array
    {
        $stats = DB::table('quiz_attempts')
            ->join('quizzes', 'quiz_attempts.quiz_id', '=', 'quizzes.id')
            ->join('categories', 'quizzes.category_id', '=', 'categories.id')
            ->where('quiz_attempts.status', 'submitted')
            ->where('quiz_attempts.attempt_type', Quiz_attempt::TYPE_GRADED)
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

        return $stats->map(function ($stat) {
            return [
                'category_id' => $stat->id,
                'category_name' => $stat->name,
                'total_attempts' => $stat->attempt_count,
                'average_score' => round($stat->average_score ?? 0, 2),
                'highest_score' => round($stat->highest_score ?? 0, 2),
                'lowest_score' => round($stat->lowest_score ?? 0, 2),
            ];
        })->toArray();
    }

    /**
     * Get category summary cards for the statistics page
     */
    public function getCategoryCardStatistics(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = DB::table('categories')
            ->join('quizzes', 'quizzes.category_id', '=', 'categories.id')
            ->join('quiz_attempts', 'quiz_attempts.quiz_id', '=', 'quizzes.id');

        // Apply date filtering if provided
        if ($dateFrom && $dateTo) {
            $query->whereBetween('quiz_attempts.created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
        }

        $stats = $query->select(
                'categories.id',
                'categories.name',
                DB::raw('COUNT(quiz_attempts.id) as total_attempts'),
                DB::raw('COUNT(DISTINCT quiz_attempts.student_id) as attempted_users'),
                DB::raw('MAX(CASE WHEN quiz_attempts.status = "submitted" AND quiz_attempts.attempt_type = "graded" THEN quiz_attempts.score_percent ELSE NULL END) as highest_score'),
                DB::raw('MIN(CASE WHEN quiz_attempts.status = "submitted" AND quiz_attempts.attempt_type = "graded" THEN quiz_attempts.score_percent ELSE NULL END) as lowest_score'),
                DB::raw('SUM(CASE WHEN quiz_attempts.status = "submitted" AND quiz_attempts.attempt_type = "graded" THEN 1 ELSE 0 END) as submitted_attempts'),
                DB::raw('SUM(CASE WHEN quiz_attempts.status = "in_progress" THEN 1 ELSE 0 END) as in_progress_attempts'),
                DB::raw('SUM(CASE WHEN quiz_attempts.status = "expired" THEN 1 ELSE 0 END) as expired_attempts')
            )
            ->groupBy('categories.id', 'categories.name')
            ->havingRaw('COUNT(quiz_attempts.id) > 0')
            ->orderBy('categories.name')
            ->get();

        return $stats->map(function ($stat) {
            $totalAttempts = (int) $stat->total_attempts;
            $submittedAttempts = (int) $stat->submitted_attempts;

            return [
                'category_id' => (int) $stat->id,
                'category_name' => $stat->name,
                'total_attempts' => $totalAttempts,
                'attempted_users' => (int) $stat->attempted_users,
                'highest_score' => round((float) ($stat->highest_score ?? 0), 2),
                'lowest_score' => round((float) ($stat->lowest_score ?? 0), 2),
                'completion_rate' => $totalAttempts > 0 ? round(($submittedAttempts / $totalAttempts) * 100, 2) : 0,
                'in_progress_attempts' => (int) $stat->in_progress_attempts,
                'expired_attempts' => (int) $stat->expired_attempts,
            ];
        })->toArray();
    }

    /**
     * Get category detail data for the selected statistics card
     */
    public function getCategoryDetailStatistics(int $categoryId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $category = Category::find($categoryId);

        if (! $category) {
            return [];
        }

        $summary = collect($this->getCategoryCardStatistics($dateFrom, $dateTo))
            ->firstWhere('category_id', $categoryId);

        if (! $summary) {
            return [];
        }

        $query = DB::table('quiz_attempts')
            ->join('quizzes', 'quiz_attempts.quiz_id', '=', 'quizzes.id')
            ->join('users', 'quiz_attempts.student_id', '=', 'users.id')
            ->where('quizzes.category_id', $categoryId)
            ->where('users.role', 'student');

        // Apply date filtering if provided
        if ($dateFrom && $dateTo) {
            $query->whereBetween('quiz_attempts.created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
        }

        $users = $query->groupBy('users.id', 'users.name', 'users.email')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                DB::raw('COUNT(quiz_attempts.id) as total_attempts'),
                DB::raw('SUM(CASE WHEN quiz_attempts.status = "submitted" THEN 1 ELSE 0 END) as submitted_attempts'),
                DB::raw('SUM(CASE WHEN quiz_attempts.status = "in_progress" THEN 1 ELSE 0 END) as in_progress_attempts'),
                DB::raw('SUM(CASE WHEN quiz_attempts.status = "expired" THEN 1 ELSE 0 END) as expired_attempts'),
                DB::raw('MAX(CASE WHEN quiz_attempts.status = "submitted" AND quiz_attempts.attempt_type = "graded" THEN quiz_attempts.score_percent ELSE NULL END) as best_score'),
                DB::raw('MIN(CASE WHEN quiz_attempts.status = "submitted" AND quiz_attempts.attempt_type = "graded" THEN quiz_attempts.score_percent ELSE NULL END) as lowest_score')
            )
            ->orderByDesc('best_score')
            ->orderBy('users.name')
            ->get()
            ->map(function ($user) {
                return [
                    'student_id' => (int) $user->id,
                    'student_name' => $user->name,
                    'student_email' => $user->email,
                    'total_attempts' => (int) $user->total_attempts,
                    'submitted_attempts' => (int) $user->submitted_attempts,
                    'in_progress_attempts' => (int) $user->in_progress_attempts,
                    'expired_attempts' => (int) $user->expired_attempts,
                    'best_score' => round((float) ($user->best_score ?? 0), 2),
                    'lowest_score' => round((float) ($user->lowest_score ?? 0), 2),
                ];
            })
            ->values();

        $highestScorer = $users
            ->filter(fn (array $user): bool => $user['best_score'] > 0)
            ->sortByDesc('best_score')
            ->first();

        $lowestScorer = $users
            ->filter(fn (array $user): bool => $user['lowest_score'] > 0)
            ->sortBy('lowest_score')
            ->first();

        return [
            'summary' => $summary,
            'users' => $users->all(),
            'highest_scorer' => $highestScorer,
            'lowest_scorer' => $lowestScorer,
        ];
    }

    /**
     * Get statistics grouped by date range
     */
    public function getStatisticsByDateRange(\DateTime $startDate, \DateTime $endDate): array
    {
        $submittedQuery = Quiz_attempt::where('status', 'submitted')
            ->where('attempt_type', Quiz_attempt::TYPE_GRADED)
            ->whereBetween('submitted_at', [$startDate, $endDate]);

        $totalAttempts = $submittedQuery->count();
        $averageScore = $submittedQuery->avg('score_percent') ?? 0;

        return [
            'date_from' => $startDate->format('Y-m-d'),
            'date_to' => $endDate->format('Y-m-d'),
            'total_attempts' => $totalAttempts,
            'average_score' => round($averageScore, 2),
            'highest_score' => round($submittedQuery->max('score_percent') ?? 0, 2),
            'lowest_score' => round($submittedQuery->min('score_percent') ?? 0, 2),
        ];
    }

    /**
     * Get performance level distribution (e.g., A, B, C, D, F grades)
     */
    public function getPerformanceDistribution(): array
    {
        $grades = [
            'A' => ['min' => 90, 'max' => 100, 'count' => 0],
            'B' => ['min' => 80, 'max' => 89, 'count' => 0],
            'C' => ['min' => 70, 'max' => 79, 'count' => 0],
            'D' => ['min' => 60, 'max' => 69, 'count' => 0],
            'F' => ['min' => 0, 'max' => 59, 'count' => 0],
        ];

        $attempts = Quiz_attempt::where('status', 'submitted')
            ->where('attempt_type', Quiz_attempt::TYPE_GRADED)
            ->get();

        foreach ($attempts as $attempt) {
            $score = $attempt->score_percent ?? 0;
            foreach ($grades as $grade => &$data) {
                if ($score >= $data['min'] && $score <= $data['max']) {
                    $data['count']++;
                    break;
                }
            }
        }

        return $grades;
    }

    /**
     * Get top performing students
     */
    public function getTopStudents(int $limit = 10): array
    {
        $students = User::where('role', 'student')
            ->orderByDesc('created_at')
            ->get();

        $topStudents = [];
        foreach ($students as $student) {
            $avgScore = $student->quizAttempts()
                ->where('status', 'submitted')
                ->where('attempt_type', Quiz_attempt::TYPE_GRADED)
                ->avg('score_percent') ?? 0;

            if ($student->quizAttempts()->count() > 0) {
                $topStudents[] = [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'student_email' => $student->email,
                    'total_attempts' => $student->quizAttempts()->count(),
                    'average_score' => round($avgScore, 2),
                ];
            }
        }

        // Sort by average score descending
        usort($topStudents, function ($a, $b) {
            return $b['average_score'] <=> $a['average_score'];
        });

        return array_slice($topStudents, 0, $limit);
    }

    /**
     * Get difficulty analysis for quizzes
     */
    public function getDifficultyAnalysis(): array
    {
        $difficulties = ['easy', 'medium', 'hard'];
        $analysis = [];

        foreach ($difficulties as $difficulty) {
            $stats = DB::table('quiz_attempts')
                ->join('quizzes', 'quiz_attempts.quiz_id', '=', 'quizzes.id')
                ->where('quiz_attempts.status', 'submitted')
                ->where('quiz_attempts.attempt_type', Quiz_attempt::TYPE_GRADED)
                ->where('quizzes.difficulty', $difficulty)
                ->select(
                    DB::raw('COUNT(*) as attempt_count'),
                    DB::raw('AVG(quiz_attempts.score_percent) as average_score')
                )
                ->first();

            $analysis[$difficulty] = [
                'total_attempts' => $stats->attempt_count ?? 0,
                'average_score' => round($stats->average_score ?? 0, 2),
            ];
        }

        return $analysis;
    }
}
