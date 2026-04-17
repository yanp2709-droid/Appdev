<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class StudentId2302Seeder extends Seeder
{
    public function run(): void
    {
        // Remove all existing students with 7-digit student_id starting with 2302
        User::where('role', 'student')
            ->where('student_id', 'like', '2302%')
            ->whereRaw('LENGTH(student_id) = 7')
            ->delete();

        $students = [];
        for ($i = 0; $i < 200; $i++) {
            // Generate 7-digit student_id starting with 2302 (e.g., 2302001, 2302002, ...)
            $studentId = '2302' . str_pad((string)($i + 1), 3, '0', STR_PAD_LEFT); // 2302001 to 2302199
            $email = $studentId . '@lnu.edu.ph';
            $students[] = [
                'name' => 'Student ' . ($i + 1),
                'first_name' => 'Student',
                'last_name' => (string)($i + 1),
                'email' => $email,
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'role' => 'student',
                'student_id' => $studentId,
                'section' => 'AI' . chr(65 + ($i % 5)), // AI + A-E
                'year_level' => '1',
                'course' => 'BSIT',
                'privacy_consent' => true,
                'is_protected' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        User::insert($students);
    }
}
