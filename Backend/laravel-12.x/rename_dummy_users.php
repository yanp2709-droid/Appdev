<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

$firstNames = ['Juan', 'Maria', 'Jose', 'Ana', 'Pedro', 'Rosa', 'Miguel', 'Carmen', 'Antonio', 'Isabella', 'Luis', 'Sofia', 'Carlos', 'Elena', 'Diego', 'Lucia', 'Francisco', 'Paula', 'Manuel', 'Laura', 'Fernando', 'Sara', 'Rafael', 'Clara', 'David', 'Marta', 'Alejandro', 'Noemi', 'Javier', 'Beatriz', 'Roberto', 'Pilar', 'Enrique', 'Teresa', 'Alberto', 'Celia', 'Victor', 'Lidia', 'Gabriel'];

$lastNames = ['Dela Cruz', 'Santos', 'Garcia', 'Reyes', 'Torres', 'Ramos', 'Perez', 'Mendoza', 'Cruz', 'Gonzales', 'Hernandez', 'Lopez', 'Villanueva', 'Tan', 'Lim', 'Sy', 'Go', 'Chan', 'Ong'];

$dummyUsers = User::where('role', 'student')
    ->where('name', 'like', 'Student %')
    ->orderBy('student_id')
    ->get();

$i = 0;
foreach ($dummyUsers as $user) {
    $fn = $firstNames[$i % count($firstNames)];
    $ln = $lastNames[$i % count($lastNames)];
    $newName = $fn . ' ' . $ln;
    $user->update([
        'name' => $newName,
        'first_name' => $fn,
        'last_name' => $ln,
    ]);
    echo "Updated {$user->student_id}: Student ? -> $newName\n";
    $i++;
}

echo "Updated {$i} dummy users.\n";

