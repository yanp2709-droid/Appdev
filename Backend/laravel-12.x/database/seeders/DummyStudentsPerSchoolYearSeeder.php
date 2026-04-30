<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\SchoolYears;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DummyStudentsPerSchoolYearSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    private array $schoolYearPrefixes = [
        '2024-2025' => '2425',
        '2025-2026' => '2526',
        '2026-2027' => '2627',
    ];

    /**
     * @var list<string>
     */
    private array $courses = [
        'BSIT',
        'BSCS',
        'BSIS',
    ];

    /**
     * @var list<string>
     */
    private array $sections = [
        'A',
        'B',
        'C',
        'D',
        'E',
    ];

    public function run(): void
    {
        $hashedPassword = Hash::make('password');
        $timestamp = now();

        foreach (SchoolYears::defaults() as $schoolYear) {
            $this->seedSchoolYear($schoolYear, $hashedPassword, $timestamp);
        }
    }

    private function seedSchoolYear(string $schoolYear, string $hashedPassword, $timestamp): void
    {
        $prefix = $this->schoolYearPrefixes[$schoolYear] ?? substr(str_replace('-', '', $schoolYear), 2, 4);

        User::query()
            ->where('role', 'student')
            ->where('student_id', 'like', $prefix . '%')
            ->where('email', 'like', '%@dummy.techquiz.local')
            ->delete();

        $students = [];

        for ($i = 1; $i <= 300; $i++) {
            $number = str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $studentId = $prefix . $number;
            $yearLevel = (string) (($i - 1) % 4 + 1);
            $section = 'IT-' . $this->sections[($i - 1) % count($this->sections)];
            $course = $this->courses[($i - 1) % count($this->courses)];
            $firstName = 'Student';
            $lastName = $prefix . '-' . $number;

            $students[] = [
                'name' => "{$firstName} {$lastName}",
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => strtolower($studentId . '@dummy.techquiz.local'),
                'email_verified_at' => $timestamp,
                'password' => $hashedPassword,
                'role' => 'student',
                'student_id' => $studentId,
                'section' => $section,
                'year_level' => $yearLevel,
                'course' => $course,
                'school_year' => $schoolYear,
                'privacy_consent' => true,
                'is_protected' => false,
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        foreach (array_chunk($students, 100) as $chunk) {
            User::insert($chunk);
        }
    }
}
