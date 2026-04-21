<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class StudentId2302Seeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'ian@gmail.com'],
            [
                'name' => 'Ian Kenneth',
                'first_name' => 'Ian',
                'last_name' => 'Kenneth',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'role' => 'student',
                'student_id' => '2302999',
                'section' => 'AIA',
                'year_level' => '1',
                'course' => 'BSIT',
                'privacy_consent' => true,
                'is_protected' => false,
            ]
        );

        // Remove all existing students with 7-digit student_id starting with 2302
        User::where('role', 'student')
            ->where('student_id', 'like', '2302%')
            ->where('email', '!=', 'ian@gmail.com')
            ->whereRaw('LENGTH(student_id) = 7')
            ->delete();

        $firstNames = [
            'Juan', 'Maria', 'Jose', 'Ana', 'Pedro', 'Rosa', 'Miguel', 'Carmen', 'Antonio', 'Isabella',
            'Luis', 'Sofia', 'Carlos', 'Elena', 'Diego', 'Lucia', 'Francisco', 'Paula', 'Manuel', 'Laura',
            'Fernando', 'Sara', 'Rafael', 'Clara', 'David', 'Marta', 'Alejandro', 'Noemi', 'Javier', 'Beatriz',
            'Roberto', 'Pilar', 'Enrique', 'Teresa', 'Alberto', 'Celia', 'Victor', 'Lidia', 'Gabriel', 'Alma',
            'Marco', 'Luz', 'Ramon', 'Eva', 'Oscar', 'Nina', 'Hugo', 'Iris', 'Leo', 'Mia'
        ];

        $lastNames = [
            'Dela Cruz', 'Santos', 'Garcia', 'Reyes', 'Torres', 'Ramos', 'Perez', 'Mendoza', 'Cruz', 'Gonzales',
            'Hernandez', 'Lopez', 'Villanueva', 'Tan', 'Lim', 'Sy', 'Go', 'Chan', 'Ong', 'Diaz',
            'Flores', 'Aquino', 'Castro', 'Ortiz', 'Navarro', 'Silva', 'Bautista', 'Padilla', 'Rocha', 'Salvador',
            'Tiongson', 'Velasco', 'Yambao', 'Zamora', 'Cabello', 'Dominguez', 'Encarnacion', 'Fortuna', 'Gomez', 'Halili'
        ];

        $students = [];
        for ($i = 0; $i < 200; $i++) {
            $fn = $firstNames[$i % count($firstNames)];
            $ln = $lastNames[$i % count($lastNames)];
            // Generate 7-digit student_id starting with 2302 (e.g., 2302001, 2302002, ...)
            $studentId = '2302' . str_pad((string)($i + 1), 3, '0', STR_PAD_LEFT); // 2302001 to 2302199
            $email = $studentId . '@lnu.edu.ph';
            $students[] = [
                'name' => $fn . ' ' . $ln,
                'first_name' => $fn,
                'last_name' => $ln,
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

