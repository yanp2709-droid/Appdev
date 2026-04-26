<?php

namespace App\Filament\Widgets;

use App\Models\Quiz_attempt;
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
        $this->selectedSchoolYear = $this->getCurrentSchoolYear();
    }

    protected function getViewData(): array
    {
        return [
            'schoolYearOptions' => $this->getSchoolYearOptions(),
            'selectedSchoolYearLabel' => $this->formatSchoolYear($this->selectedSchoolYear),
            'averageData' => $this->getAverageData(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function getSchoolYearOptions(): array
    {
        if (! Schema::hasColumn('quiz_attempts', 'school_year')) {
            return [];
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

        $currentSchoolYear = $this->getCurrentSchoolYear();

        if (! in_array($currentSchoolYear, $schoolYears, true)) {
            $schoolYears[] = $currentSchoolYear;
            rsort($schoolYears);
        }

        $options = [];

        foreach ($schoolYears as $schoolYear) {
            $options[$schoolYear] = $this->formatSchoolYear($schoolYear);
        }

        return $options;
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

    protected function formatSchoolYear(string $schoolYear): string
    {
        $normalizedSchoolYear = preg_replace('/\s+/', '', trim($schoolYear));

        if (preg_match('/^(\d{4})[-–](\d{4})$/', $normalizedSchoolYear, $matches)) {
            return "{$matches[1]}–{$matches[2]}";
        }

        return trim($schoolYear);
    }

    protected function getCurrentSchoolYear(): string
    {
        $referenceDate = now();
        $startYear = $referenceDate->month >= 6
            ? $referenceDate->year
            : $referenceDate->year - 1;

        return sprintf('%d-%d', $startYear, $startYear + 1);
    }
}
