<?php

namespace App\Filament\Widgets;

use App\Models\Quiz_attempt;
use App\Support\SchoolYears;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AverageScoreCardWidget extends Widget
{
    protected string $view = 'filament.widgets.average-score-card-widget';

    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 1;

    public string $selectedSchoolYear = '';

    public function mount(): void
    {
        $this->selectedSchoolYear = SchoolYears::current();
    }

    protected function getViewData(): array
    {
        return [
            'schoolYearOptions' => $this->getSchoolYearOptions(),
            'selectedSchoolYearLabel' => SchoolYears::format($this->selectedSchoolYear),
            'averageData' => $this->getAverageData(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function getSchoolYearOptions(): array
    {
        if (! Schema::hasColumn('quiz_attempts', 'school_year')) {
            return SchoolYears::options();
        }

        $schoolYears = Quiz_attempt::query()
            ->select('school_year')
            ->whereNotNull('school_year')
            ->where('school_year', '!=', '')
            ->distinct()
            ->orderBy('school_year', 'desc')
            ->pluck('school_year')
            ->filter()
            ->values()
            ->all();

        return SchoolYears::options($schoolYears);
    }

    /**
     * @return array{has_column: bool, has_data: bool, average_score: float|null, attempt_count: int}
     */
    protected function getAverageData(): array
    {
        if (! Schema::hasColumn('quiz_attempts', 'school_year')) {
            return [
                'has_column' => false,
                'has_data' => false,
                'average_score' => null,
                'attempt_count' => 0,
            ];
        }

        if (blank($this->selectedSchoolYear)) {
            return [
                'has_column' => true,
                'has_data' => false,
                'average_score' => null,
                'attempt_count' => 0,
            ];
        }

        $stats = Quiz_attempt::query()
            ->where('status', 'submitted')
            ->where('attempt_type', Quiz_attempt::TYPE_GRADED)
            ->where('school_year', $this->selectedSchoolYear)
            ->select(DB::raw('AVG(score_percent) as average_score'), DB::raw('COUNT(*) as attempt_count'))
            ->first();

        $attemptCount = (int) ($stats->attempt_count ?? 0);

        return [
            'has_column' => true,
            'has_data' => $attemptCount > 0,
            'average_score' => $attemptCount > 0 ? round((float) ($stats->average_score ?? 0), 2) : null,
            'attempt_count' => $attemptCount,
        ];
    }
}
