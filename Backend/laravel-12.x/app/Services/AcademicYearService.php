<?php

namespace App\Services;

use Carbon\Carbon;

class AcademicYearService
{
    public const SESSION_KEY = 'selected_academic_year';

    /**
     * @return array<string, string>
     */
    public function getOptions(): array
    {
        return [
            '2023-2024' => '2023-2024',
            '2024-2025' => '2024-2025',
            '2025-2026' => '2025-2026',
        ];
    }

    public function getCurrentAcademicYear(): string
    {
        $currentYear = now()->year;

        if (now()->month >= 6) {
            return $currentYear . '-' . ($currentYear + 1);
        }

        return ($currentYear - 1) . '-' . $currentYear;
    }

    public function getSelectedAcademicYear(): string
    {
        $selectedYear = session(self::SESSION_KEY, $this->getCurrentAcademicYear());

        return array_key_exists($selectedYear, $this->getOptions())
            ? $selectedYear
            : $this->getCurrentAcademicYear();
    }

    public function setSelectedAcademicYear(?string $academicYear): string
    {
        $academicYear = $this->normalizeAcademicYear($academicYear);

        if (! array_key_exists($academicYear, $this->getOptions())) {
            $academicYear = $this->getCurrentAcademicYear();
        }

        session([self::SESSION_KEY => $academicYear]);

        return $academicYear;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function getDateRange(string $academicYear): array
    {
        $academicYear = $this->normalizeAcademicYear($academicYear);
        [$startYear, $endYear] = explode('-', $academicYear);

        $startDate = Carbon::createFromDate((int) $startYear, 6, 1)->startOfDay();
        $endDate = Carbon::createFromDate((int) $endYear, 5, 31)->endOfDay();

        return [$startDate, $endDate];
    }

    public function formatAcademicYear(string $academicYear): string
    {
        $academicYear = $this->normalizeAcademicYear($academicYear);

        if (preg_match('/^(\d{4})-(\d{4})$/', $academicYear, $matches)) {
            return sprintf('%s-%s', $matches[1], $matches[2]);
        }

        return $academicYear;
    }

    private function normalizeAcademicYear(?string $academicYear): string
    {
        return preg_replace('/\s+/', '', trim((string) $academicYear));
    }
}
