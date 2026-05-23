<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\AdminDashboard;
use App\Models\Quiz_attempt;
use App\Models\User;
use App\Services\AcademicYearService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

class StudentScoreStatsWidget extends BaseWidget
{
    public ?User $record = null;

    protected static bool $isLazy = false;

    protected $listeners = ['academicYearChanged' => '$refresh'];

    protected function getStats(): array
    {
        if (! $this->record || $this->record->role !== 'student') {
            return [];
        }

        $academicYear = AdminDashboard::getSelectedAcademicYear();
        [$startDate, $endDate] = app(AcademicYearService::class)->getDateRange($academicYear);

        $attempts = $this->record->quizAttempts()
            ->where('status', 'submitted')
            ->where('attempt_type', Quiz_attempt::TYPE_GRADED)
            ->when(
                Schema::hasColumn('quiz_attempts', 'school_year'),
                fn ($query) => $query->where('school_year', $academicYear),
                fn ($query) => $query->whereBetween('submitted_at', [$startDate, $endDate]),
            );

        $attemptCount = $attempts->count();
        $averageScore = $attempts->avg('score_percent') ?? 0;
        $highestScore = $attempts->max('score_percent') ?? 0;
        $lowestScore = $attempts->min('score_percent') ?? 0;
        $scoreSeries = $attempts
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->pluck('score_percent')
            ->map(fn ($value): float => round((float) $value, 2))
            ->values()
            ->all();

        $totalSubmittedChart = $this->buildTotalSubmittedChart(count($scoreSeries));
        $averageScoreChart = $this->buildRunningAverageChart($scoreSeries);
        $highestScoreChart = $this->buildRunningMaxChart($scoreSeries);
        $lowestScoreChart = $this->buildRunningMinChart($scoreSeries);

        $performanceColor = $averageScore >= 80 ? 'success' : ($averageScore >= 60 ? 'warning' : 'danger');

        return [
            Stat::make('Total Submitted', $attemptCount)
                ->description('Quiz attempts completed')
                ->descriptionIcon('heroicon-m-check-circle')
                ->chart($totalSubmittedChart)
                ->color('info'),

            Stat::make('Average Score', round($averageScore) . '%')
                ->description('Overall performance')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->chart($averageScoreChart)
                ->color($performanceColor),

            Stat::make('Highest Score', round($highestScore) . '%')
                ->description('Best attempt')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($highestScoreChart)
                ->color('success'),

            Stat::make('Lowest Score', round($lowestScore) . '%')
                ->description('Lowest attempt')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->chart($lowestScoreChart)
                ->color('warning'),
        ];
    }

    /**
     * @return array<int, float|int>
     */
    private function buildTotalSubmittedChart(int $count): array
    {
        if ($count <= 0) {
            return [0];
        }

        return range(1, $count);
    }

    /**
     * @param array<int, float> $scores
     * @return array<int, float>
     */
    private function buildRunningAverageChart(array $scores): array
    {
        if ($scores === []) {
            return [0];
        }

        $sum = 0.0;
        $chart = [];

        foreach ($scores as $index => $score) {
            $sum += $score;
            $chart[] = round($sum / ($index + 1), 2);
        }

        return $chart;
    }

    /**
     * @param array<int, float> $scores
     * @return array<int, float>
     */
    private function buildRunningMaxChart(array $scores): array
    {
        if ($scores === []) {
            return [0];
        }

        $max = null;
        $chart = [];

        foreach ($scores as $score) {
            $max = $max === null ? $score : max($max, $score);
            $chart[] = round($max, 2);
        }

        return $chart;
    }

    /**
     * @param array<int, float> $scores
     * @return array<int, float>
     */
    private function buildRunningMinChart(array $scores): array
    {
        if ($scores === []) {
            return [0];
        }

        $min = null;
        $chart = [];

        foreach ($scores as $score) {
            $min = $min === null ? $score : min($min, $score);
            $chart[] = round($min, 2);
        }

        return $chart;
    }
}
