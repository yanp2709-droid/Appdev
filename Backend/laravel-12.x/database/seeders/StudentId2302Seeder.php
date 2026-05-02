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
            'Vhong', 'Susana', 'Wally', 'Teresita', 'Yanyan', ' Ursula', 'Zanjoe', 'Virginia', 'Atom', 'Welma'
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
            'Dagohoy', 'Dalisay', 'Danganan', 'Daquiz', ' DATU', 'Dayan', 'De Guzman', 'De Jesus', 'De Leon', 'De Vera',
            'Del Rosario', 'Dela Peña', 'Dela Torre', 'Delos Reyes', 'Delos Santos', 'Dimaculangan', 'Dimaguiba', 'Diwa', 'Dizon', 'Doctor',
            'Dumlao', 'Duran', 'Ebreo', 'Edralin', 'Eisma', 'Eleazar', 'Elizalde', 'Empleo', 'Enalpe', 'Enriquez',
            'Erdozain', 'Esguerra', ' Español', 'Estalilla', 'Estanislao', 'Estrella', 'Evangelista', 'Fabella', 'Fajardo', 'Falcon',
            'Fernandez', 'Ferrer', 'Florendo', 'Fonacier', 'Francisco', 'Franco', 'Fulgencio', 'Gacosta', 'Galaw', 'Gallardo',
            'Gamboa', 'Gammad', 'Gan', 'Gangan', 'Garay', 'Garcia', 'Gaviola', 'Geronimo', 'Gines', 'Giniguinto',
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

        // Shuffle both arrays for random-like but deterministic combinations
        $shuffledFirst = $firstNames;
        $shuffledLast = $lastNames;
        shuffle($shuffledFirst);
        shuffle($shuffledLast);

        $students = [];
        $usedPairs = [];

        for ($i = 0; $i < 200; $i++) {
            // Ensure unique name combinations where possible
            $attempts = 0;
            do {
                $fn = $shuffledFirst[array_rand($shuffledFirst)];
                $ln = $shuffledLast[array_rand($shuffledLast)];
                $pairKey = $fn . '|' . $ln;
                $attempts++;
            } while (isset($usedPairs[$pairKey]) && $attempts < 50);

            $usedPairs[$pairKey] = true;

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

