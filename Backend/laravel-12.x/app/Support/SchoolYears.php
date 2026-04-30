<?php

namespace App\Support;

final class SchoolYears
{
    /**
     * @return list<string>
     */
    public static function defaults(): array
    {
        return [
            '2024-2025',
            '2025-2026',
            '2026-2027',
        ];
    }

    public static function current(): string
    {
        $referenceDate = now();
        $startYear = $referenceDate->month >= 6
            ? $referenceDate->year
            : $referenceDate->year - 1;

        return sprintf('%d-%d', $startYear, $startYear + 1);
    }

    public static function format(?string $schoolYear): string
    {
        $schoolYear = trim((string) $schoolYear);
        $normalizedSchoolYear = preg_replace('/\s+/', '', $schoolYear);

        if (preg_match('/^(\d{4})[-–](\d{4})$/', $normalizedSchoolYear, $matches)) {
            return "{$matches[1]}–{$matches[2]}";
        }

        return $schoolYear;
    }

    /**
     * @param  iterable<string>  $schoolYears
     * @return array<string, string>
     */
    public static function options(iterable $schoolYears = []): array
    {
        $values = [];

        foreach ($schoolYears as $schoolYear) {
            $schoolYear = trim((string) $schoolYear);

            if ($schoolYear !== '') {
                $values[] = $schoolYear;
            }
        }

        $allSchoolYears = array_values(array_filter(array_unique(array_merge(
            $values,
            static::defaults(),
            [static::current()],
        ))));

        rsort($allSchoolYears);

        $options = [];

        foreach ($allSchoolYears as $schoolYear) {
            $options[$schoolYear] = static::format($schoolYear);
        }

        return $options;
    }
}
