<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Quiz_attempt;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\HtmlString;

class DashboardStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    public string $selectedSchoolYear = '';

    public function mount(): void
    {
        $this->selectedSchoolYear = $this->getCurrentSchoolYear();
    }

    protected function getStats(): array
    {
        $totalStudents = User::where('role', 'student')->count();
        $totalAttempts = Quiz_attempt::count();
        $submittedAttempts = Quiz_attempt::where('status', 'submitted')->count();
        $selectedSchoolYear = $this->selectedSchoolYear ?: $this->getCurrentSchoolYear();
        $formattedSchoolYear = $this->formatSchoolYear($selectedSchoolYear);

        $averageValue = 'No data available';
        $averageDescription = "No submitted records for A.Y. {$formattedSchoolYear}";

        if (Schema::hasColumn('quiz_attempts', 'school_year')) {
            $averageStats = Quiz_attempt::query()
                ->where('status', 'submitted')
                ->where('school_year', $selectedSchoolYear)
                ->select(DB::raw('AVG(score_percent) as average_score'), DB::raw('COUNT(*) as attempt_count'))
                ->first();

            $attemptCount = (int) ($averageStats->attempt_count ?? 0);

            if ($attemptCount > 0) {
                $averageValue = round((float) ($averageStats->average_score ?? 0), 2) . '%';
                $averageDescription = "A.Y. {$formattedSchoolYear} class average";
            }
        }

        return [
            Stat::make('Total Students', $totalStudents)
                ->description('Active student accounts')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Total Attempts', $totalAttempts)
                ->description('All quiz attempts')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info'),

            Stat::make('Submitted Attempts', $submittedAttempts)
                ->description('Completed and graded attempts')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary'),

            Stat::make($this->makeAverageScoreLabel(), $averageValue)
                ->description($averageDescription)
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),
        ];
    }

    private function getCurrentSchoolYear(): string
    {
        $referenceDate = now();
        $startYear = $referenceDate->month >= 6
            ? $referenceDate->year
            : $referenceDate->year - 1;

        return sprintf('%d-%d', $startYear, $startYear + 1);
    }

    private function formatSchoolYear(string $schoolYear): string
    {
        $normalizedSchoolYear = preg_replace('/\s+/', '', trim($schoolYear));

        if (preg_match('/^(\d{4})[-–](\d{4})$/', $normalizedSchoolYear, $matches)) {
            return "{$matches[1]}–{$matches[2]}";
        }

        return trim($schoolYear);
    }

    /**
     * @return array<string, string>
     */
    private function getSchoolYearOptions(): array
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

    private function makeAverageScoreLabel(): HtmlString
    {
        $options = '';

        foreach ($this->getSchoolYearOptions() as $value => $label) {
            $selected = $value === $this->selectedSchoolYear ? ' selected' : '';
            $options .= '<option value="' . e($value) . '"' . $selected . '>' . e($label) . '</option>';
        }

        if ($options === '') {
            $options = '<option value="">No data available</option>';
        }

        return new HtmlString(
            '<span style="display:flex;width:100%;align-items:flex-start;justify-content:space-between;gap:0.75rem;">'
            . '<span>Average Score</span>'
            . '<span style="position:relative;display:inline-flex;align-items:center;">'
            . '<select wire:model.live="selectedSchoolYear" aria-label="Select academic year" '
            . 'style="min-width:7.5rem;height:2rem;border:1px solid #d1d5db;border-radius:0.5rem;background:#ffffff;padding:0.2rem 1.75rem 0.2rem 0.55rem;font-size:0.75rem;font-weight:600;color:#6b7280;box-shadow:0 1px 2px rgba(0,0,0,0.05);outline:none;appearance:none;-webkit-appearance:none;-moz-appearance:none;">'
            . $options
            . '</select>'
            . '<span style="position:absolute;right:0.5rem;pointer-events:none;color:#6b7280;font-size:0.75rem;line-height:1;">▾</span>'
            . '</span>'
            . '</span>'
        );
    }
}
