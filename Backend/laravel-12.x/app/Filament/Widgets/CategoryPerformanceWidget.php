<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\AdminDashboard;
use App\Services\AcademicYearService;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as DatabaseSchema;

class CategoryPerformanceWidget extends ChartWidget
{
    use HasFiltersSchema;

    protected ?string $heading = 'Quiz Performance';

    protected static ?int $sort = 4;

    protected static bool $isLazy = false;

    protected ?string $maxHeight = '300px';

    protected int | string | array $columnSpan = 1;

    protected bool $hasDeferredFilters = true;

    protected $listeners = ['academicYearChanged' => 'resetAcademicYearFilters'];

    public function filtersSchema(Schema $schema): Schema
    {
        [$defaultFrom, $defaultTo] = app(AcademicYearService::class)->getDateRange(AdminDashboard::getSelectedAcademicYear());

        return $schema->components([
            DatePicker::make('date_from')
                ->label('From')
                ->default($defaultFrom->toDateString()),
            DatePicker::make('date_to')
                ->label('To')
                ->default($defaultTo->toDateString()),
        ]);
    }

    public function resetAcademicYearFilters(): void
    {
        [$dateFrom, $dateTo] = app(AcademicYearService::class)->getDateRange(AdminDashboard::getSelectedAcademicYear());

        $this->filters['date_from'] = $dateFrom->toDateString();
        $this->filters['date_to'] = $dateTo->toDateString();
    }

    protected function getData(): array
    {
        $dateFrom = $this->filters['date_from'] ?? null;
        $dateTo = $this->filters['date_to'] ?? null;

        if (! $dateFrom || ! $dateTo) {
            [$dateFrom, $dateTo] = app(AcademicYearService::class)->getDateRange(AdminDashboard::getSelectedAcademicYear());
            $dateFrom = $dateFrom->toDateString();
            $dateTo = $dateTo->toDateString();
        }

        $academicYear = AdminDashboard::getSelectedAcademicYear();
        $stats = DB::table('quiz_attempts')
            ->join('quizzes', 'quiz_attempts.quiz_id', '=', 'quizzes.id')
            ->join('categories', 'quizzes.category_id', '=', 'categories.id')
            ->when(
                DatabaseSchema::hasColumn('quiz_attempts', 'school_year'),
                fn ($query) => $query->where('quiz_attempts.school_year', $academicYear),
            )
            ->when($dateFrom, fn ($query) => $query->whereDate('quiz_attempts.submitted_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('quiz_attempts.submitted_at', '<=', $dateTo))
            ->groupBy('categories.id', 'categories.name')
            ->select('categories.name', DB::raw('COUNT(quiz_attempts.id) as total_attempts'))
            ->orderByDesc('total_attempts')
            ->limit(5)
            ->get();

        $labels = $stats->pluck('name')->toArray();
        $scores = $stats->pluck('total_attempts')->map(fn ($count) => (int) ($count ?? 0))->toArray();

        // Handle empty data case
        if (empty($labels)) {
            $labels = ['No Data'];
            $scores = [0];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Quiz Attempts',
                    'data' => $scores,
                    'backgroundColor' => [
                        '#f87171',
                        '#fb923c',
                        '#fbbf24',
                        '#a3e635',
                        '#4ade80',
                    ],
                    'borderColor' => '#ffffff',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
