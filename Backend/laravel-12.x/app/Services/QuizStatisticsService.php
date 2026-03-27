<?php

namespace App\Services;

use App\Models\Quiz_attempt;
use App\Models\User;
use App\Models\Quiz;
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
        
        $submittedQuery = Quiz_attempt::where('status', 'submitted');
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
        $submittedAttempts = (clone $attempts)->where('status', 'submitted');
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
     * Get statistics grouped by date range
     */
    public function getStatisticsByDateRange(\DateTime $startDate, \DateTime $endDate): array
    {
        $submittedQuery = Quiz_attempt::where('status', 'submitted')
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

        $attempts = Quiz_attempt::where('status', 'submitted')->get();

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
