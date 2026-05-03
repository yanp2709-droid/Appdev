<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\AdminDashboard;
use App\Models\Quiz_attempt;
use App\Services\AcademicYearService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AverageScoreCardWidget extends Widget
{
    protected string $view = 'filament.widgets.average-score-card-widget';

    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 1;

    protected $listeners = ['academicYearChanged' => '$refresh'];

    protected function getViewData(): array
    {
        return [
            'averageData' => $this->getAverageData(),
        ];
    }

    /**
     * @return array{has_data: bool, average_score: float|null, attempt_count: int}
     */
    protected function getAverageData(): array
    {
        $academicYear = AdminDashboard::getSelectedAcademicYear();
        [$startDate, $endDate] = app(AcademicYearService::class)->getDateRange($academicYear);

        $stats = Quiz_attempt::query()
            ->where('status', 'submitted')
            ->where('attempt_type', Quiz_attempt::TYPE_GRADED)
            ->when(
                Schema::hasColumn('quiz_attempts', 'school_year'),
                fn ($query) => $query->where('school_year', $academicYear),
                fn ($query) => $query->whereBetween('submitted_at', [$startDate, $endDate]),
            )
            ->select(DB::raw('AVG(score_percent) as average_score'), DB::raw('COUNT(*) as attempt_count'))
            ->first();

        $attemptCount = (int) ($stats->attempt_count ?? 0);

        return [
            'has_data' => $attemptCount > 0,
            'average_score' => $attemptCount > 0 ? round((float) ($stats->average_score ?? 0), 2) : null,
            'attempt_count' => $attemptCount,
        ];
    }

}
