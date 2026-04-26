<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

$firstNames = [
    'Juan', 'Maria', 'Jose', 'Ana', 'Pedro', 'Rosa', 'Miguel', 'Carmen', 'Antonio', 'Isabella',
    'Luis', 'Sofia', 'Carlos', 'Elena', 'Diego', 'Lucia', 'Francisco', 'Paula', 'Manuel', 'Laura',
    'Fernando', 'Sara', 'Rafael', 'Clara', 'David', 'Marta', 'Alejandro', 'Noemi', 'Javier', 'Beatriz',
    'Roberto', 'Pilar', 'Enrique', 'Teresa', 'Alberto', 'Celia', 'Victor', 'Lidia', 'Gabriel', 'Alma',
    'Marco', 'Luz', 'Ramon', 'Eva', 'Oscar', 'Nina', 'Hugo', 'Iris', 'Leo', 'Mia',
    'Andres', 'Angela', 'Benjamin', 'Bianca', 'Cristian', 'Camila', 'Daniel', 'Diana', 'Eduardo', 'Emily',
    'Fabian', 'Fiona', 'George', 'Giselle', 'Henry', 'Hannah', 'Ivan', 'Ingrid', 'James', 'Julia',
    'Kevin', 'Katrina', 'Lorenzo', 'Leah', 'Martin', 'Lorena', 'Nathan', 'Melissa', 'Oliver', 'Natalie',
    'Patrick', 'Olivia', 'Quentin', 'Patricia', 'Richard', 'Rachel', 'Samuel', 'Rebecca', 'Thomas', 'Sophie',
    'Ulrich', 'Tanya', 'Vincent', 'Vanessa', 'William', 'Veronica', 'Xavier', 'Wendy', 'Yves', 'Yasmine',
    'Zachary', 'Zoe', 'Adrian', 'Amber', 'Benedict', 'Belle', 'Christian', 'Catherine', 'Dominic', 'Daphne',
    'Ethan', 'Elaine', 'Frederick', 'Esther', 'Gerald', 'Faith', 'Harold', 'Gwen', 'Ian', 'Hailey',
    'Jerome', 'Isabelle', 'Kenneth', 'Jasmine', 'Lawrence', 'Joy', 'Marcus', 'Karen', 'Nicholas', 'Kimberly',
    'Orlando', 'Liza', 'Phillip', 'Margaret', 'Raymond', 'Monica', 'Simon', 'Nancy', 'Theodore', 'Priscilla',
    'Ulysses', 'Queenie', 'Vernon', 'Ruth', 'Warren', 'Sharon', 'Xander', 'Theresa', 'Yohan', 'Ursula',
    'Zeke', 'Vivian', 'Alfonso', 'Wanda', 'Bruno', 'Xenia', 'Cesar', 'Yolanda', 'Dante', 'Zelda',
    'Ernesto', 'Abigail', 'Felix', 'Bernadette', 'Gregorio', 'Cecilia', 'Hector', 'Dorothy', 'Ignacio', 'Eleanor',
    'Julian', 'Florence', 'Kristoffer', 'Genevieve', 'Lucas', 'Henrietta', 'Mateo', 'Imelda', 'Nicolas', 'Jacqueline',
    'Omar', 'Kristine', 'Pablo', 'Lourdes', 'Quirino', 'Maricel', 'Rodrigo', 'Nenita', 'Santiago', 'Ophelia',
    'Tomas', 'Perla', 'Uriel', 'Rosalinda', 'Vicente', 'Socorro', 'Wilfredo', 'Trinidad', 'Yago', 'Victoria',
    'Zandro', 'Yolanda', 'Arnel', 'Zenaida', 'Bong', 'Adelaida', 'Carlo', 'Agnes', 'Dennis', 'Amparo',
    'Erwin', 'Beatriz', 'Ferdinand', 'Consuelo', 'Gerry', 'Divina', 'Harvey', 'Ester', 'Ike', 'Fe',
    'Jojo', 'Gloria', 'Kiko', 'Hilda', 'Louie', 'Irma', 'Manny', 'Jocelyn', 'Noy', 'Luningning',
    'Ogie', 'Magdalena', 'Pocholo', 'Nimfa', 'Rico', 'Ofelia', 'Sonny', 'Paz', 'Tito', 'Remedios',
    'Vhong', 'Susana', 'Wally', 'Teresita', 'Yanyan', 'Ursula', 'Zanjoe', 'Virginia', 'Atom', 'Welma'
];

$lastNames = [
    'Dela Cruz', 'Santos', 'Garcia', 'Reyes', 'Torres', 'Ramos', 'Perez', 'Mendoza', 'Cruz', 'Gonzales',
    'Hernandez', 'Lopez', 'Villanueva', 'Tan', 'Lim', 'Sy', 'Go', 'Chan', 'Ong', 'Diaz',
    'Flores', 'Aquino', 'Castro', 'Ortiz', 'Navarro', 'Silva', 'Bautista', 'Padilla', 'Rocha', 'Salvador',
    'Tiongson', 'Velasco', 'Yambao', 'Zamora', 'Cabello', 'Dominguez', 'Encarnacion', 'Fortuna', 'Gomez', 'Halili',
    'Abad', 'Abellana', 'Abrigo', 'Acosta', 'Acuña', 'Adriano', 'Agbayani', 'Aguilar', 'Alba', 'Alcantara',
    'Alejo', 'Alfonso', 'Alvarado', 'Alvarez', 'Amador', 'Andaya', 'Ang', 'Antonio', 'Apostol', 'Aragon',
    'Arce', 'Arenas', 'Arguelles', 'Arquiza', 'Asuncion', 'Atienza', 'Austria', 'Avena', 'Axalan', 'Aytona',
    'Bacani', 'Baclayon', 'Badillo', 'Bagatsing', 'Bagnas', 'Balanay', 'Balbas', 'Balili', 'Ballesteros', 'Baluyot',
    'Banal', 'Bandong', 'Barba', 'Barcelona', 'Barrameda', 'Barrientos', 'Bautista', 'Bayani', 'Belmonte', 'Benitez',
    'Bernal', 'Bernardo', 'Bilog', 'Bocobo', 'Borja', 'Bravo', 'Buenaventura', 'Buenavista', 'Bugayong', 'Bulatao',
    'Cabrera', 'Cachola', 'Cadiz', 'Cailing', 'Calderon', 'Calibo', 'Callanta', 'Camacho', 'Candelaria', 'Capili',
    'Capulong', 'Carag', 'Cariño', 'Carreon', 'Casimiro', 'Castañeda', 'Castillo', 'Castro', 'Catapang', 'Cayabyab',
    'Celiz', 'Chavez', 'Chico', 'Cipriano', 'Claveria', 'Clemente', 'Co', 'Coloma', 'Constantino', 'Coronel',
    'Cortez', 'Cosejo', 'Crisostomo', 'Cuaresma', 'Cueto', 'Cunanan', 'Dacanay', 'Dacquil', 'Dadulla', 'Daez',
    'Dagohoy', 'Dalisay', 'Danganan', 'Daquiz', 'Dayan', 'De Guzman', 'De Jesus', 'De Leon', 'De Vera',
    'Del Rosario', 'Dela Peña', 'Dela Torre', 'Delos Reyes', 'Delos Santos', 'Dimaculangan', 'Dimaguiba', 'Diwa', 'Dizon', 'Doctor',
    'Dumlao', 'Duran', 'Ebreo', 'Edralin', 'Eisma', 'Eleazar', 'Elizalde', 'Empleo', 'Enalpe', 'Enriquez',
    'Erdozain', 'Esguerra', 'Estalilla', 'Estanislao', 'Estrella', 'Evangelista', 'Fabella', 'Fajardo', 'Falcon',
    'Fernandez', 'Ferrer', 'Florendo', 'Fonacier', 'Francisco', 'Franco', 'Fulgencio', 'Gacosta', 'Galaw', 'Gallardo',
    'Gamboa', 'Gammad', 'Gan', 'Gangan', 'Garay', 'Gaviola', 'Geronimo', 'Gines', 'Giniguinto',
    'Gloria', 'Go', 'Gonzaga', 'Gonzales', 'Guevarra', 'Guevara', 'Guiterez', 'Gulapa', 'Gutierrez', 'Guico',
    'Hermoso', 'Hernando', 'Hidalgo', 'Hilado', 'Hipolito', 'Hizon', 'Honradez', 'Ibarra', 'Ibasco', 'Ilagan',
    'Ilao', 'Ildefonso', 'Inocencio', 'Isip', 'Jacinto', 'Jalipa', 'Javellana', 'Javier', 'Jocson', 'Joson',
    'Joven', 'Juaneza', 'Jumarang', 'Jusi', 'Laborte', 'Lacanlale', 'Lacsamana', 'Lactaoen', 'Lagasca', 'Lagman',
    'Lalu', 'Lambino', 'Landrito', 'Langit', 'Lantin', 'Lao', 'Lapena', 'Lapid', 'Lara', 'Lascano',
    'Laxamana', 'Layug', 'Lazaro', 'Ledesma', 'Legaspi', 'Lerio', 'Libed', 'Librero', 'Licera', 'Linao',
    'Lirio', 'Litao', 'Litonjua', 'Liwanag', 'Llamas', 'Llanes', 'Lo', 'Locsin', 'Logarta', 'Lontoc',
    'Lorenzo', 'Lucero', 'Lumanog', 'Luzano', 'Maagad', 'Mabini', 'Macalalad', 'Macalintal', 'Macatangay', 'Maceda',
    'Madrid', 'Madridejos', 'Magallanes', 'Magbanua', 'Magdaleno', 'Magpantay', 'Magtibay', 'Magsaysay', 'Mahilum', 'Maiquez',
    'Majorenos', 'Malabanan', 'Malabuyoc', 'Malate', 'Malig', 'Malit', 'Mallari', 'Mallillin', 'Maloles', 'Manaig',
    'Manalo', 'Manansala', 'Manapat', 'Mangahas', 'Mangaoang', 'Mangilit', 'Maniego', 'Manlangit', 'Manlapaz', 'Manliclic',
    'Manongsong', 'Mantilla', 'Manuel', 'Mapalo', 'Mapa', 'Maranan', 'Marasigan', 'Marcelino', 'Marcelo', 'Mariano',
    'Marquez', 'Martinez', 'Marzan', 'Mascardo', 'Matias', 'Matriano', 'Mauhay', 'Maylem', 'Medenilla', 'Medina',
    'Mejia', 'Mendez', 'Mercado', 'Meramonte', 'Miagros', 'Mijares', 'Militante', 'Millado', 'Mirafuente', 'Miranda',
    'Misa', 'Mojares', 'Molinos', 'Morales', 'Morano', 'Morelos', 'Morfe', 'Morillo', 'Mosqueda', 'Motas',
    'Muñoz', 'Nable', 'Nacion', 'Nacpil', 'Nagrama', 'Nambatac', 'Nanquil', 'Narciso', 'Natividad', 'Nava',
    'Navato', 'Nepomuceno', 'Neri', 'Nicolao', 'Nieves', 'Nillo', 'Nilo', 'Noches', 'Nolido', 'Noller'
];

// Shuffle for randomized pairing
shuffle($firstNames);
shuffle($lastNames);

$dummyUsers = User::where('role', 'student')
    ->where('name', 'like', 'Student %')
    ->orderBy('student_id')
    ->get();

$i = 0;
$usedPairs = [];
foreach ($dummyUsers as $user) {
    // Pick a unique-ish combination
    $attempts = 0;
    do {
        $fn = $firstNames[array_rand($firstNames)];
        $ln = $lastNames[array_rand($lastNames)];
        $pairKey = $fn . '|' . $ln;
        $attempts++;
    } while (isset($usedPairs[$pairKey]) && $attempts < 50);

    $usedPairs[$pairKey] = true;
    $newName = $fn . ' ' . $ln;

    $user->update([
        'name' => $newName,
        'first_name' => $fn,
        'last_name' => $ln,
    ]);
    echo "Updated {$user->student_id}: {$user->name} -> $newName\n";
    $i++;
}

echo "Updated {$i} dummy users.\n";