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
        if (!$this->record || $this->record->role !== 'student') {
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

        $performanceColor = $averageScore >= 80 ? 'success' : ($averageScore >= 60 ? 'warning' : 'danger');

        return [
            Stat::make('Total Submitted', $attemptCount)
                ->description('Quiz attempts completed')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info'),

            Stat::make('Average Score', round($averageScore, 2) . '%')
                ->description('Overall performance')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($performanceColor),

            Stat::make('Highest Score', round($highestScore, 2) . '%')
                ->description('Best attempt')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Lowest Score', round($lowestScore, 2) . '%')
                ->description('Lowest attempt')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('warning'),
        ];
    }
}
