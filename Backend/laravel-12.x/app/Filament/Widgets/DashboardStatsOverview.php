<?php

namespace App\Filament\Widgets;

use App\Models\Quiz_attempt;
use App\Models\User;
use App\Services\AcademicYearService;
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

    protected $listeners = ['academicYearChanged' => 'syncSelectedSchoolYear'];

    public function mount(): void
    {
        $this->selectedSchoolYear = app(AcademicYearService::class)->getSelectedAcademicYear();
    }

    public function syncSelectedSchoolYear(): void
    {
        $this->selectedSchoolYear = app(AcademicYearService::class)->getSelectedAcademicYear();
    }

    public function changeSchoolYear(): void
    {
        $this->selectedSchoolYear = app(AcademicYearService::class)->setSelectedAcademicYear($this->selectedSchoolYear);
        $this->dispatch('academicYearChanged');
    }

    protected function getStats(): array
    {
        $selectedSchoolYear = $this->selectedSchoolYear ?: app(AcademicYearService::class)->getSelectedAcademicYear();
        $formattedSchoolYear = $this->formatSchoolYear($selectedSchoolYear);

        $totalStudentsQuery = User::query()->where('role', 'student');

        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'academic_year')) {
            $totalStudentsQuery->where('academic_year', $selectedSchoolYear);
        } else {
            [$startDate, $endDate] = app(AcademicYearService::class)->getDateRange($selectedSchoolYear);
            $totalStudentsQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        $totalStudents = $totalStudentsQuery->count();

        $totalAttemptsQuery = Quiz_attempt::query();
        $submittedAttemptsQuery = Quiz_attempt::query()->where('status', 'submitted');

        if (Schema::hasColumn('quiz_attempts', 'school_year')) {
            $totalAttemptsQuery->where('school_year', $selectedSchoolYear);
            $submittedAttemptsQuery->where('school_year', $selectedSchoolYear);
        } else {
            $totalAttemptsQuery->whereBetween('submitted_at', [$startDate, $endDate]);
            $submittedAttemptsQuery->whereBetween('submitted_at', [$startDate, $endDate]);
        }

        $totalAttempts = $totalAttemptsQuery->count();
        $submittedAttempts = $submittedAttemptsQuery->count();

        $averageValue = 'No data available';
        $averageDescription = "No submitted records for A.Y. {$formattedSchoolYear}";

        $averageStatsQuery = Quiz_attempt::query()
            ->where('status', 'submitted')
            ->where('attempt_type', Quiz_attempt::TYPE_GRADED)
            ->select(DB::raw('AVG(score_percent) as average_score'), DB::raw('COUNT(*) as attempt_count'));

        if (Schema::hasColumn('quiz_attempts', 'school_year')) {
            $averageStatsQuery->where('school_year', $selectedSchoolYear);
        } else {
            $averageStatsQuery->whereBetween('submitted_at', [$startDate, $endDate]);
        }

        $averageStats = $averageStatsQuery->first();
        $attemptCount = (int) ($averageStats->attempt_count ?? 0);

        if ($attemptCount > 0) {
            $averageValue = round((float) ($averageStats->average_score ?? 0), 2) . '%';
            $averageDescription = "A.Y. {$formattedSchoolYear} class average";
        }

        return [
            Stat::make('Total Students', $totalStudents)
                ->description("Active student accounts for A.Y. {$formattedSchoolYear}")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Total Attempts', $totalAttempts)
                ->description("All quiz attempts for A.Y. {$formattedSchoolYear}")
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info'),

            Stat::make('Submitted Attempts', $submittedAttempts)
                ->description("Completed and graded attempts for A.Y. {$formattedSchoolYear}")
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
        return app(AcademicYearService::class)->getCurrentAcademicYear();
    }

    private function formatSchoolYear(string $schoolYear): string
    {
        return app(AcademicYearService::class)->formatAcademicYear($schoolYear);
    }

    /**
     * @return array<string, string>
     */
    private function getSchoolYearOptions(): array
    {
        return app(AcademicYearService::class)->getOptions();
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
            . 'wire:change="changeSchoolYear()" '
            . 'style="min-width:7.5rem;height:2rem;border:1px solid #d1d5db;border-radius:0.5rem;background:#ffffff;padding:0.2rem 1.75rem 0.2rem 0.55rem;font-size:0.75rem;font-weight:600;color:#6b7280;box-shadow:0 1px 2px rgba(0,0,0,0.05);outline:none;appearance:none;-webkit-appearance:none;-moz-appearance:none;">'
            . $options
            . '</select>'
            . '<span style="position:absolute;right:0.5rem;pointer-events:none;color:#6b7280;font-size:0.75rem;line-height:1;">▾</span>'
            . '</span>'
            . '</span>'
        );
    }
}
