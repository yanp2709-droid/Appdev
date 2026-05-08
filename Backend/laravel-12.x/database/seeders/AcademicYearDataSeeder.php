<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Attempt_answer;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\Quiz_attempt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademicYearDataSeeder extends Seeder
{
    private const STUDENTS_PER_YEAR = 300;
    private const QUIZZES_PER_YEAR = 20;
    private const QUIZZES_PER_SUBJECT = 20;
    private const QUESTIONS_PER_QUIZ = 10;
    private const ATTEMPTS_PER_YEAR = [
        '2023-2024' => 3,
        '2024-2025' => 4,
        '2025-2026' => 5,
    ];

    private const ACADEMIC_YEARS = [
        '2023-2024' => ['start' => '2023-06-01', 'end' => '2024-05-31'],
        '2024-2025' => ['start' => '2024-06-01', 'end' => '2025-05-31'],
        '2025-2026' => ['start' => '2025-06-01', 'end' => '2026-05-31'],
    ];

    private const SUBJECTS_2023_2024 = [
        ['name' => 'Introduction to Programming', 'description' => 'Basic programming fundamentals'],
        ['name' => 'Digital Literacy', 'description' => 'Computer basics and internet usage'],
        ['name' => 'Computer Systems', 'description' => 'Hardware and software concepts'],
        ['name' => 'Network Fundamentals', 'description' => 'Basic networking concepts'],
        ['name' => 'Web Development Basics', 'description' => 'HTML and CSS fundamentals'],
        ['name' => 'Database Concepts', 'description' => 'Introduction to databases'],
        ['name' => 'Computer Security', 'description' => 'Basic security principles'],
    ];

    private const SUBJECTS_2024_2025 = [
        ['name' => 'Python Programming', 'description' => 'Python language basics'],
        ['name' => 'Data Management', 'description' => 'Data organization and storage'],
        ['name' => 'System Architecture', 'description' => 'Computer system design'],
        ['name' => 'Network Security', 'description' => 'Securing networks'],
        ['name' => 'Frontend Development', 'description' => 'JavaScript basics'],
        ['name' => 'SQL Database', 'description' => 'Structured query language'],
        ['name' => 'Cyber Defense', 'description' => 'Protection methods'],
    ];

    private const SUBJECTS_2025_2026 = [
        ['name' => 'Advanced Programming', 'description' => 'Advanced coding techniques'],
        ['name' => 'Data Analytics', 'description' => 'Data analysis methods'],
        ['name' => 'Cloud Computing', 'description' => 'Cloud services and deployment'],
        ['name' => 'Cybersecurity', 'description' => 'Security and privacy'],
        ['name' => 'Full Stack Web Dev', 'description' => 'Complete web development'],
        ['name' => 'Database Administration', 'description' => 'Managing databases'],
        ['name' => 'Ethical Hacking', 'description' => 'Penetration testing basics'],
    ];

    private const FIRST_NAMES = [
        'James', 'Mary', 'John', 'Patricia', 'Robert', 'Jennifer', 'Michael', 'Linda', 'William', 'Elizabeth',
        'David', 'Barbara', 'Richard', 'Susan', 'Joseph', 'Jessica', 'Thomas', 'Sarah', 'Charles', 'Karen',
        'Christopher', 'Nancy', 'Daniel', 'Lisa', 'Matthew', 'Betty', 'Anthony', 'Margaret', 'Mark', 'Sandra',
        'Donald', 'Ashley', 'Steven', 'Kimberly', 'Paul', 'Emily', 'Andrew', 'Donna', 'Joshua', 'Michelle',
        'Kenneth', 'Carol', 'Kevin', 'Amanda', 'Brian', 'Dorothy', 'George', 'Melissa', 'Timothy', 'Deborah',
    ];

    private const LAST_NAMES = [
        'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez',
        'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee',
        'Perez', 'Thompson', 'White', 'Harris', 'Sanchez', 'Clark', 'Ramirez', 'Lewis', 'Robinson', 'Walker',
        'Young', 'Allen', 'King', 'Wright', 'Scott', 'Torres', 'Nguyen', 'Hill', 'Flores', 'Green',
    ];

    private const TEACHERS_2023_2024 = [
        'Prof. Anderson', 'Prof. Martinez', 'Prof. Thompson',
    ];

    private const TEACHERS_2024_2025 = [
        'Prof. Wilson', 'Prof. Clark', 'Prof. Lewis',
    ];

    private const TEACHERS_2025_2026 = [
        'Prof. Robinson', 'Prof. Walker', 'Prof. Young',
    ];

    public function run(): void
    {
        $this->command->info('Generating Academic Year Data...');
        $this->command->info('');

        $this->removeLegacySampleQuestions();

        $this->seedAcademicYear('2023-2024', self::SUBJECTS_2023_2024, self::TEACHERS_2023_2024);
        $this->seedAcademicYear('2024-2025', self::SUBJECTS_2024_2025, self::TEACHERS_2024_2025);
        $this->seedAcademicYear('2025-2026', self::SUBJECTS_2025_2026, self::TEACHERS_2025_2026);

        $this->command->info('');
        $this->command->info('Academic Year Data seeding completed!');
    }

    private function seedAcademicYear(string $schoolYear, array $subjects, array $teachers): void
    {
        $this->command->info("Processing A.Y. {$schoolYear}...");

        $yearInfo = self::ACADEMIC_YEARS[$schoolYear];

        $teacher = $this->createTeacher($schoolYear, $teachers, $yearInfo);

        $categories = [];
        $subjectCount = count($subjects);

        foreach ($subjects as $index => $subjectData) {
            $quizCount = self::QUIZZES_PER_SUBJECT;

            $category = $this->createCategoryAndQuizzes($subjectData, $teacher, $schoolYear, $yearInfo, $quizCount);
            $categories[] = $category;
        }

        $students = $this->createStudents($schoolYear, self::STUDENTS_PER_YEAR);

        $totalAttempts = 0;
        foreach ($students as $student) {
            $attemptsCreated = $this->createStudentAttempts($student, $categories, $yearInfo, $schoolYear);
            $totalAttempts += $attemptsCreated;
        }

        $this->command->info("  - Created {$totalAttempts} quiz attempts for A.Y. {$schoolYear}");
        $this->command->info('');
    }

    private function createTeacher(string $schoolYear, array $teacherNames, array $yearInfo): User
    {
        $name = $teacherNames[array_rand($teacherNames)];
        $emailBase = strtolower(str_replace(' ', '.', $name));
        $email = "{$emailBase}.{$schoolYear}@techquiz.edu";
        $registeredAt = $this->getRandomTimestampWithinYear(
            Carbon::parse($yearInfo['start']),
            Carbon::parse($yearInfo['end']),
        );

        $teacher = User::where('email', $email)->first();

        if (!$teacher) {
            $teacher = User::create([
                'name' => $name,
                'email' => $email,
                'password' => bcrypt('password'),
                'role' => 'teacher',
                'academic_year' => $schoolYear,
                'is_active' => true,
                'is_protected' => false,
            ]);
        }

        $this->applyTimestamps($teacher, $registeredAt);

        return $teacher;
    }

    private function createCategoryAndQuizzes(array $subjectData, User $teacher, string $schoolYear, array $yearInfo, int $quizCount): Category
    {
        $categoryTimestamp = $this->getRandomTimestampWithinYear(
            Carbon::parse($yearInfo['start']),
            Carbon::parse($yearInfo['end']),
        );

        $category = Category::updateOrCreate(
            ['name' => $subjectData['name']],
            [
                'description' => $subjectData['description'],
                'is_published' => true,
                'time_limit_minutes' => 15,
            ]
        );
        $this->applyTimestamps($category, $categoryTimestamp);

        $difficultyLevels = ['Easy', 'Medium', 'Hard'];

        for ($quizNum = 1; $quizNum <= $quizCount; $quizNum++) {
            $difficulty = $difficultyLevels[array_rand($difficultyLevels)];
            $quizTimestamp = $this->getRandomTimestampWithinYear(
                Carbon::parse($yearInfo['start']),
                Carbon::parse($yearInfo['end']),
            );
            
            $quizTitle = "{$subjectData['name']} - Quiz {$quizNum}";
            $quiz = Quiz::updateOrCreate(
                [
                    'category_id' => $category->id,
                    'title' => $quizTitle,
                ],
                [
                    'teacher_id' => $teacher->id,
                    'difficulty' => $difficulty,
                    'duration_minutes' => 10,
                    'timer_enabled' => true,
                    'shuffle_questions' => true,
                    'shuffle_options' => true,
                    'max_attempts' => 3,
                    'allow_review_before_submit' => true,
                    'show_score_immediately' => true,
                    'show_answers_after_submit' => true,
                    'show_correct_answers_after_submit' => true,
                    'is_active' => true,
                ]
            );
            $this->applyTimestamps($quiz, $quizTimestamp);

            $existingQuestionIds = $quiz->questions()->pluck('id');
            if ($existingQuestionIds->isNotEmpty()) {
                QuestionOption::whereIn('question_id', $existingQuestionIds)->delete();
                Question::whereIn('id', $existingQuestionIds)->delete();
            }

            $questionBank = $this->getQuestionBankForSubject($subjectData['name']);

            for ($qNum = 1; $qNum <= self::QUESTIONS_PER_QUIZ; $qNum++) {
                $questionTimestamp = $this->getRandomTimestampWithinYear(
                    Carbon::parse($yearInfo['start']),
                    Carbon::parse($yearInfo['end']),
                );
                
                $questionTemplate = $questionBank[($qNum - 1) % count($questionBank)];
                $questionText = $questionTemplate['question'];
                $questionType = $questionTemplate['type'];

                $question = Question::create([
                    'quiz_id' => $quiz->id,
                    'category_id' => $category->id,
                    'question_text' => $questionText,
                    'question_type' => $questionType,
                    'points' => $questionTemplate['points'] ?? 5,
                    'answer_key' => $questionTemplate['answer_key'] ?? null,
                ]);
                $this->applyTimestamps($question, $questionTimestamp);

                if ($questionType === Question::TYPE_TRUE_FALSE) {
                    QuestionOption::create([
                        'question_id' => $question->id,
                        'option_text' => 'True',
                        'is_correct' => true,
                        'order_index' => 0,
                    ]);
                    QuestionOption::create([
                        'question_id' => $question->id,
                        'option_text' => 'False',
                        'is_correct' => false,
                        'order_index' => 1,
                    ]);
                } else {
                    $options = $questionTemplate['options'] ?? [];
                    foreach ($options as $index => $option) {
                        QuestionOption::create([
                            'question_id' => $question->id,
                            'option_text' => $option['text'],
                            'is_correct' => $option['correct'],
                            'order_index' => $index,
                        ]);
                    }
                }
            }
        }

        return $category;
    }

    private function createStudents(string $schoolYear, int $count): array
    {
        $students = [];
        $yearInfo = self::ACADEMIC_YEARS[$schoolYear];
        $startDate = Carbon::parse($yearInfo['start']);
        $endDate = Carbon::parse($yearInfo['end']);

        for ($i = 1; $i <= $count; $i++) {
            $firstName = self::FIRST_NAMES[array_rand(self::FIRST_NAMES)];
            $lastName = self::LAST_NAMES[array_rand(self::LAST_NAMES)];
            $name = "{$firstName} {$lastName}";
            $studentId = $this->generateStudentId($schoolYear, $i);
            $email = $studentId . '@lnu.edu.ph';
            $registeredAt = $this->getRandomTimestampWithinYear($startDate, $endDate);

            $user = User::updateOrCreate(
                ['student_id' => $studentId],
                [
                    'name' => $name,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'password' => bcrypt('password'),
                    'role' => 'student',
                    'academic_year' => $schoolYear,
                    'student_id' => $studentId,
                    'section' => $this->generateSection($schoolYear, $i),
                    'year_level' => (string) ((($i - 1) % 4) + 1),
                    'course' => 'BSIT',
                    'is_active' => true,
                    'is_protected' => false,
                ]
            );

            $this->applyTimestamps($user, $registeredAt);

            $students[] = $user;
        }

        return $students;
    }

    private function getRandomTimestampWithinYear(Carbon $startDate, Carbon $endDate): Carbon
    {
        $effectiveEndDate = $endDate->greaterThan(now()) ? now() : $endDate;
        $days = $startDate->diffInDays($effectiveEndDate);

        return $startDate->copy()->addDays(random_int(0, max(0, $days)))->startOfDay()->addSeconds(random_int(0, 86399));
    }

    private function generateStudentId(string $schoolYear, int $sequence): string
    {
        $prefixMap = [
            '2023-2024' => '2301',
            '2024-2025' => '2302',
            '2025-2026' => '2303',
        ];

        $prefix = $prefixMap[$schoolYear] ?? '2300';

        return $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    private function generateSection(string $schoolYear, int $sequence): string
    {
        $yearCodeMap = [
            '2023-2024' => '1',
            '2024-2025' => '2',
            '2025-2026' => '3',
        ];

        $yearCode = $yearCodeMap[$schoolYear] ?? '0';
        $sectionCode = (string) ((($sequence - 1) % 5) + 1);

        return 'AI' . $yearCode . $sectionCode;
    }

    private function createStudentAttempts(User $student, array $categories, array $yearInfo, string $schoolYear): int
    {
        $attemptCount = 0;
        $attemptTarget = self::ATTEMPTS_PER_YEAR[$schoolYear] ?? 4;
        $quizzes = collect($categories)
            ->flatMap(fn (Category $category) => $category->quizzes()->inRandomOrder()->limit(3)->get())
            ->shuffle()
            ->unique('id')
            ->take($attemptTarget)
            ->values();

        foreach ($quizzes as $index => $quiz) {
            $status = match ($index % 3) {
                0 => 'submitted',
                1 => 'in_progress',
                default => 'expired',
            };

            $attempt = $this->createAttempt($student, $quiz, $yearInfo, $schoolYear, $status);
            if ($attempt) {
                $attemptCount++;
            }
        }

        return $attemptCount;
    }

    private function createAttempt(User $student, Quiz $quiz, array $yearInfo, string $schoolYear, string $status = 'submitted'): ?Quiz_attempt
    {
        $startDate = Carbon::parse($yearInfo['start']);
        $endDate = Carbon::parse($yearInfo['end']);
        $totalItems = $quiz->questions()->count() ?: 10;

        $randomDays = random_int(0, $startDate->diffInDays($endDate));
        $startedAt = $startDate->copy()->addDays($randomDays);
        
        $durationMinutes = $quiz->duration_minutes ?? 10;
        $expiresAt = $startedAt->copy()->addMinutes($durationMinutes);
        $submittedAt = $status === 'submitted'
            ? $startedAt->copy()->addMinutes($durationMinutes + random_int(1, 5))
            : null;

        $attempt = Quiz_attempt::create([
            'student_id' => $student->id,
            'quiz_id' => $quiz->id,
            'attempt_type' => Quiz_attempt::TYPE_GRADED,
            'score' => 0,
            'status' => $status,
            'started_at' => $startedAt,
            'expires_at' => $expiresAt,
            'submitted_at' => $submittedAt,
            'total_items' => 0,
            'answered_count' => 0,
            'correct_answers' => 0,
            'score_percent' => 0,
            'school_year' => $schoolYear,
            'question_sequence' => range(1, $totalItems),
            'last_activity_at' => $submittedAt ?? $startedAt,
            'last_viewed_question_index' => $totalItems - 1,
        ]);

        $this->applyTimestamps($attempt, $startedAt);

        [$answeredCount, $correctAnswers] = $this->createAttemptAnswers($attempt, $quiz, $status);
        $scorePercent = $status === 'submitted'
            ? round(($correctAnswers / max(1, $totalItems)) * 100, 2)
            : 0;

        $attempt->update([
            'score' => $correctAnswers,
            'total_items' => $totalItems,
            'answered_count' => $answeredCount,
            'correct_answers' => $correctAnswers,
            'score_percent' => $scorePercent,
        ]);

        return $attempt;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function createAttemptAnswers(Quiz_attempt $attempt, Quiz $quiz, string $status): array
    {
        $questions = $quiz->questions()
            ->with('options')
            ->orderBy('id')
            ->get();

        $totalQuestions = $questions->count();
        if ($totalQuestions === 0) {
            return [0, 0];
        }

        $answeredTarget = match ($status) {
            'submitted' => $totalQuestions,
            'in_progress' => random_int(3, max(3, min($totalQuestions, 7))),
            default => random_int(1, max(1, min($totalQuestions, 5))),
        };

        $answeredCount = 0;
        $correctAnswers = 0;

        foreach ($questions->take($answeredTarget) as $index => $question) {
            $selection = $this->pickRealisticAnswerForQuestion($question, $status);

            Attempt_answer::create([
                'quiz_attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'answer_id' => null,
                'question_option_id' => $selection['question_option_id'],
                'selected_option_ids' => $selection['selected_option_ids'],
                'text_answer' => $selection['text_answer'],
                'is_correct' => $selection['is_correct'],
                'is_bookmarked' => false,
            ]);

            $answeredCount++;
            if ($selection['is_correct']) {
                $correctAnswers++;
            }
        }

        return [$answeredCount, $correctAnswers];
    }

    /**
     * @return array{question_option_id: int|null, selected_option_ids: array<int>|null, text_answer: string|null, is_correct: bool}
     */
    private function pickRealisticAnswerForQuestion(Question $question, string $status): array
    {
        $options = $question->options->values();
        $correctOption = $options->firstWhere('is_correct', true);
        $shouldBeCorrect = match ($status) {
            'submitted' => random_int(1, 100) <= 72,
            'in_progress' => random_int(1, 100) <= 55,
            default => random_int(1, 100) <= 35,
        };

        if ($options->isNotEmpty()) {
            $selectedOption = $correctOption ?? $options->first();

            if (! $shouldBeCorrect && $options->count() > 1) {
                $incorrectOptions = $options->filter(fn ($option) => ! (bool) $option->is_correct)->values();
                if ($incorrectOptions->isNotEmpty()) {
                    $selectedOption = $incorrectOptions->random();
                }
            }

            return [
                'question_option_id' => $selectedOption?->id ? (int) $selectedOption->id : null,
                'selected_option_ids' => $selectedOption?->id ? [(int) $selectedOption->id] : null,
                'text_answer' => null,
                'is_correct' => (bool) ($selectedOption?->is_correct ?? false),
            ];
        }

        $sampleAnswers = [
            'Explains the main concept clearly.',
            'Demonstrates the correct process.',
            'Uses the recommended best practice.',
            'Identifies the right answer with a short explanation.',
        ];

        return [
            'question_option_id' => null,
            'selected_option_ids' => null,
            'text_answer' => $sampleAnswers[array_rand($sampleAnswers)],
            'is_correct' => $shouldBeCorrect,
        ];
    }

    private function applyTimestamps($model, Carbon $timestamp): void
    {
        DB::table($model->getTable())
            ->where($model->getKeyName(), $model->getKey())
            ->update([
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

        $model->forceFill([
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    private function removeLegacySampleQuestions(): void
    {
        Question::where('question_text', 'like', '%Sample question%')
            ->chunkById(100, function ($questions) {
                $questionIds = $questions->pluck('id');
                QuestionOption::whereIn('question_id', $questionIds)->delete();
                Question::whereIn('id', $questionIds)->delete();
            });
    }

    private function getQuestionBankForSubject(string $subjectName): array
    {
        $name = strtolower($subjectName);

        if (str_contains($name, 'ethical hacking') || str_contains($name, 'cybersecurity') || str_contains($name, 'cyber defense') || str_contains($name, 'computer security') || str_contains($name, 'network security')) {
            return [
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What is the main goal of cybersecurity?',
                    'options' => [
                        ['text' => 'Protect systems from unauthorized access', 'correct' => true],
                        ['text' => 'Build faster web pages', 'correct' => false],
                        ['text' => 'Store large amounts of data', 'correct' => false],
                        ['text' => 'Write code without errors', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which item is a common type of cyber attack?',
                    'options' => [
                        ['text' => 'Phishing', 'correct' => true],
                        ['text' => 'Compiling', 'correct' => false],
                        ['text' => 'Indexing', 'correct' => false],
                        ['text' => 'Caching', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'A strong password should include letters, numbers, and symbols.',
                    'options' => [
                        ['text' => 'True', 'correct' => true],
                        ['text' => 'False', 'correct' => false],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What does a firewall do?',
                    'options' => [
                        ['text' => 'Filters incoming and outgoing network traffic', 'correct' => true],
                        ['text' => 'Stores backup files', 'correct' => false],
                        ['text' => 'Scans documents for spelling errors', 'correct' => false],
                        ['text' => 'Compresses data', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which activity is a good practice to prevent unauthorized access?',
                    'options' => [
                        ['text' => 'Regularly updating software', 'correct' => true],
                        ['text' => 'Sharing your password', 'correct' => false],
                        ['text' => 'Using public Wi-Fi for banking', 'correct' => false],
                        ['text' => 'Ignoring security warnings', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'Encryption converts readable data into unreadable form for protection.',
                    'options' => [
                        ['text' => 'True', 'correct' => true],
                        ['text' => 'False', 'correct' => false],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which term describes software that appears legitimate but harms the system?',
                    'options' => [
                        ['text' => 'Malware', 'correct' => true],
                        ['text' => 'Middleware', 'correct' => false],
                        ['text' => 'Macro', 'correct' => false],
                        ['text' => 'Module', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What is social engineering in cybersecurity?',
                    'options' => [
                        ['text' => 'Tricking people into revealing sensitive information', 'correct' => true],
                        ['text' => 'Designing secure web pages', 'correct' => false],
                        ['text' => 'Encrypting data using keys', 'correct' => false],
                        ['text' => 'Installing antivirus software', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'Two-factor authentication adds a second layer of security.',
                    'options' => [
                        ['text' => 'True', 'correct' => true],
                        ['text' => 'False', 'correct' => false],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which is an example of a secure communication protocol?',
                    'options' => [
                        ['text' => 'HTTPS', 'correct' => true],
                        ['text' => 'FTP', 'correct' => false],
                        ['text' => 'Telnet', 'correct' => false],
                        ['text' => 'HTTP', 'correct' => false],
                    ],
                    'points' => 5,
                ],
            ];
        }

        if (str_contains($name, 'cloud')) {
            return [
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What is cloud computing?',
                    'options' => [
                        ['text' => 'Using remote servers over the internet', 'correct' => true],
                        ['text' => 'Storing files on a USB drive', 'correct' => false],
                        ['text' => 'Installing software from a CD', 'correct' => false],
                        ['text' => 'Connecting two local computers', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'SaaS delivers software applications over the internet.',
                    'options' => [
                        ['text' => 'True', 'correct' => true],
                        ['text' => 'False', 'correct' => false],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which cloud service model provides infrastructure resources?',
                    'options' => [
                        ['text' => 'IaaS', 'correct' => true],
                        ['text' => 'PaaS', 'correct' => false],
                        ['text' => 'SaaS', 'correct' => false],
                        ['text' => 'DaaS', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What is a benefit of cloud computing?',
                    'options' => [
                        ['text' => 'Scalable resources on demand', 'correct' => true],
                        ['text' => 'Requires no internet connection', 'correct' => false],
                        ['text' => 'Unlimited local storage only', 'correct' => false],
                        ['text' => 'Automatic hardware repair', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'Data can be backed up from the cloud to local devices.',
                    'options' => [
                        ['text' => 'True', 'correct' => true],
                        ['text' => 'False', 'correct' => false],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which provider is known for cloud computing services?',
                    'options' => [
                        ['text' => 'Amazon Web Services', 'correct' => true],
                        ['text' => 'Microsoft Word', 'correct' => false],
                        ['text' => 'Adobe Photoshop', 'correct' => false],
                        ['text' => 'Slack', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What does multi-tenancy mean in cloud computing?',
                    'options' => [
                        ['text' => 'Multiple users share the same resources securely', 'correct' => true],
                        ['text' => 'Only one user can access a system at a time', 'correct' => false],
                        ['text' => 'Tenants live in the same building', 'correct' => false],
                        ['text' => 'Hardware is replaced every ten years', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'Cloud computing can reduce the need for local servers.',
                    'options' => [
                        ['text' => 'True', 'correct' => true],
                        ['text' => 'False', 'correct' => false],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which deployment model provides resources to the public?',
                    'options' => [
                        ['text' => 'Public cloud', 'correct' => true],
                        ['text' => 'Private cloud', 'correct' => false],
                        ['text' => 'Hybrid cloud', 'correct' => false],
                        ['text' => 'Community cloud', 'correct' => false],
                    ],
                    'points' => 5,
                ],
            ];
        }

        if (str_contains($name, 'analytics') || str_contains($name, 'data management') || str_contains($name, 'data analytics')) {
            return [
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What is data analytics?',
                    'options' => [
                        ['text' => 'Examining data to find useful patterns', 'correct' => true],
                        ['text' => 'Writing software without testing', 'correct' => false],
                        ['text' => 'Building physical hardware', 'correct' => false],
                        ['text' => 'Designing a house floor plan', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'Data visualization helps communicate insights clearly.',
                    'options' => [
                        ['text' => 'True', 'correct' => true],
                        ['text' => 'False', 'correct' => false],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which tool is often used for data analysis?',
                    'options' => [
                        ['text' => 'Spreadsheet software', 'correct' => true],
                        ['text' => 'Game engine', 'correct' => false],
                        ['text' => 'Video editor', 'correct' => false],
                        ['text' => 'Paint program', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What does “cleaning data” usually involve?',
                    'options' => [
                        ['text' => 'Removing errors and inconsistencies', 'correct' => true],
                        ['text' => 'Adding random values', 'correct' => false],
                        ['text' => 'Deleting all records', 'correct' => false],
                        ['text' => 'Encrypting the dataset', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'Big data refers to extremely large datasets.',
                    'options' => [
                        ['text' => 'True', 'correct' => true],
                        ['text' => 'False', 'correct' => false],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which metric measures average of values?',
                    'options' => [
                        ['text' => 'Mean', 'correct' => true],
                        ['text' => 'Median', 'correct' => false],
                        ['text' => 'Mode', 'correct' => false],
                        ['text' => 'Range', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which chart is best for showing proportions?',
                    'options' => [
                        ['text' => 'Pie chart', 'correct' => true],
                        ['text' => 'Line chart', 'correct' => false],
                        ['text' => 'Scatter plot', 'correct' => false],
                        ['text' => 'Histogram', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'Outliers can affect analysis results.',
                    'options' => [
                        ['text' => 'True', 'correct' => true],
                        ['text' => 'False', 'correct' => false],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What is a common source of data for analytics?',
                    'options' => [
                        ['text' => 'Surveys and logs', 'correct' => true],
                        ['text' => 'Handwritten notes only', 'correct' => false],
                        ['text' => 'Paintings', 'correct' => false],
                        ['text' => 'Fiction books', 'correct' => false],
                    ],
                    'points' => 5,
                ],
            ];
        }

        if (str_contains($name, 'python')) {
            return [
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which keyword is used to define a function in Python?',
                    'options' => [
                        ['text' => 'def', 'correct' => true],
                        ['text' => 'function', 'correct' => false],
                        ['text' => 'fun', 'correct' => false],
                        ['text' => 'define', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'Python uses indentation to group code blocks.',
                    'options' => [
                        ['text' => 'True', 'correct' => true],
                        ['text' => 'False', 'correct' => false],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What does the len() function return in Python?',
                    'options' => [
                        ['text' => 'The number of items in a collection', 'correct' => true],
                        ['text' => 'The last item in a list', 'correct' => false],
                        ['text' => 'The data type of a variable', 'correct' => false],
                        ['text' => 'The sum of numbers', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which symbol is used for comments in Python?',
                    'options' => [
                        ['text' => '#', 'correct' => true],
                        ['text' => '//', 'correct' => false],
                        ['text' => '/*', 'correct' => false],
                        ['text' => '--', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'Lists in Python are ordered collections.',
                    'options' => [
                        ['text' => 'True', 'correct' => true],
                        ['text' => 'False', 'correct' => false],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which function converts a string to an integer in Python?',
                    'options' => [
                        ['text' => 'int()', 'correct' => true],
                        ['text' => 'str()', 'correct' => false],
                        ['text' => 'float()', 'correct' => false],
                        ['text' => 'bool()', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'How do you start a block of code in Python?',
                    'options' => [
                        ['text' => 'With a colon and indentation', 'correct' => true],
                        ['text' => 'With curly braces', 'correct' => false],
                        ['text' => 'With parentheses', 'correct' => false],
                        ['text' => 'With angle brackets', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'A tuple in Python is mutable.',
                    'options' => [
                        ['text' => 'True', 'correct' => false],
                        ['text' => 'False', 'correct' => true],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which keyword is used to create a loop that runs while a condition is true?',
                    'options' => [
                        ['text' => 'while', 'correct' => true],
                        ['text' => 'for', 'correct' => false],
                        ['text' => 'loop', 'correct' => false],
                        ['text' => 'repeat', 'correct' => false],
                    ],
                    'points' => 5,
                ],
            ];
        }

        if (str_contains($name, 'network')) {
            return [
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What does IP stand for in networking?',
                    'options' => [
                        ['text' => 'Internet Protocol', 'correct' => true],
                        ['text' => 'Internal Program', 'correct' => false],
                        ['text' => 'Input Process', 'correct' => false],
                        ['text' => 'Instant Packet', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'A router connects multiple networks together.',
                    'options' => [
                        ['text' => 'True', 'correct' => true],
                        ['text' => 'False', 'correct' => false],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which device forwards data packets between networks?',
                    'options' => [
                        ['text' => 'Router', 'correct' => true],
                        ['text' => 'Switch', 'correct' => false],
                        ['text' => 'Printer', 'correct' => false],
                        ['text' => 'Monitor', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which address type is used for a single device on a network?',
                    'options' => [
                        ['text' => 'Unicast', 'correct' => true],
                        ['text' => 'Broadcast', 'correct' => false],
                        ['text' => 'Multicast', 'correct' => false],
                        ['text' => 'Anycast', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'A LAN connects devices within a small area.',
                    'options' => [
                        ['text' => 'True', 'correct' => true],
                        ['text' => 'False', 'correct' => false],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which protocol is used to translate domain names to IP addresses?',
                    'options' => [
                        ['text' => 'DNS', 'correct' => true],
                        ['text' => 'HTTP', 'correct' => false],
                        ['text' => 'FTP', 'correct' => false],
                        ['text' => 'SMTP', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which topology uses a central hub or switch?',
                    'options' => [
                        ['text' => 'Star', 'correct' => true],
                        ['text' => 'Ring', 'correct' => false],
                        ['text' => 'Bus', 'correct' => false],
                        ['text' => 'Mesh', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'Bandwidth measures the capacity of a network connection.',
                    'options' => [
                        ['text' => 'True', 'correct' => true],
                        ['text' => 'False', 'correct' => false],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What is a MAC address used for?',
                    'options' => [
                        ['text' => 'Identifying a network device on a local network', 'correct' => true],
                        ['text' => 'Encrypting web traffic', 'correct' => false],
                        ['text' => 'Storing website cookies', 'correct' => false],
                        ['text' => 'Measuring download speed', 'correct' => false],
                    ],
                    'points' => 5,
                ],
            ];
        }

        if (str_contains($name, 'system architecture') || str_contains($name, 'computer systems')) {
            return [
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What is the main purpose of a motherboard?',
                    'options' => [
                        ['text' => 'Connects all computer components together', 'correct' => true],
                        ['text' => 'Stores data permanently', 'correct' => false],
                        ['text' => 'Executes software programs', 'correct' => false],
                        ['text' => 'Provides wireless networking', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'RAM is volatile memory.',
                    'options' => [
                        ['text' => 'True', 'correct' => true],
                        ['text' => 'False', 'correct' => false],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which component performs calculations in a computer?',
                    'options' => [
                        ['text' => 'CPU', 'correct' => true],
                        ['text' => 'GPU', 'correct' => false],
                        ['text' => 'HDD', 'correct' => false],
                        ['text' => 'PSU', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What stores firmware and system settings?',
                    'options' => [
                        ['text' => 'BIOS/UEFI', 'correct' => true],
                        ['text' => 'RAM', 'correct' => false],
                        ['text' => 'SSD', 'correct' => false],
                        ['text' => 'Keyboard', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'A power supply unit converts AC power to DC power.',
                    'options' => [
                        ['text' => 'True', 'correct' => true],
                        ['text' => 'False', 'correct' => false],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which device is used for long-term data storage?',
                    'options' => [
                        ['text' => 'Hard Drive', 'correct' => true],
                        ['text' => 'RAM', 'correct' => false],
                        ['text' => 'CPU', 'correct' => false],
                        ['text' => 'Monitor', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What is the role of the GPU?',
                    'options' => [
                        ['text' => 'Render graphics and images', 'correct' => true],
                        ['text' => 'Manage network traffic', 'correct' => false],
                        ['text' => 'Store files', 'correct' => false],
                        ['text' => 'Provide audio output', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'The CPU is often called the brain of the computer.',
                    'options' => [
                        ['text' => 'True', 'correct' => true],
                        ['text' => 'False', 'correct' => false],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What does ROM stand for?',
                    'options' => [
                        ['text' => 'Read-Only Memory', 'correct' => true],
                        ['text' => 'Random Operating Module', 'correct' => false],
                        ['text' => 'Rapid Output Memory', 'correct' => false],
                        ['text' => 'Real-time Operating Method', 'correct' => false],
                    ],
                    'points' => 5,
                ],
            ];
        }

        if (str_contains($name, 'digital literacy')) {
            return [
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What is a web browser used for?',
                    'options' => [
                        ['text' => 'Accessing websites', 'correct' => true],
                        ['text' => 'Writing documents', 'correct' => false],
                        ['text' => 'Managing databases', 'correct' => false],
                        ['text' => 'Designing hardware', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'Using strong passwords helps protect personal information.',
                    'options' => [
                        ['text' => 'True', 'correct' => true],
                        ['text' => 'False', 'correct' => false],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which software is commonly used to create text documents?',
                    'options' => [
                        ['text' => 'Word processor', 'correct' => true],
                        ['text' => 'Spreadsheet', 'correct' => false],
                        ['text' => 'Database', 'correct' => false],
                        ['text' => 'Browser', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What is an email attachment?',
                    'options' => [
                        ['text' => 'A file sent with an email message', 'correct' => true],
                        ['text' => 'A link to a website', 'correct' => false],
                        ['text' => 'A password', 'correct' => false],
                        ['text' => 'A type of virus', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'Public Wi-Fi is always safe for banking.',
                    'options' => [
                        ['text' => 'True', 'correct' => false],
                        ['text' => 'False', 'correct' => true],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which action is important for digital safety?',
                    'options' => [
                        ['text' => 'Keeping passwords private', 'correct' => true],
                        ['text' => 'Sharing accounts with friends', 'correct' => false],
                        ['text' => 'Clicking unknown links', 'correct' => false],
                        ['text' => 'Ignoring software updates', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What does a search engine do?',
                    'options' => [
                        ['text' => 'Find information on the internet', 'correct' => true],
                        ['text' => 'Send emails', 'correct' => false],
                        ['text' => 'Edit photos', 'correct' => false],
                        ['text' => 'Store files offline', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'A strong password should be easy to guess.',
                    'options' => [
                        ['text' => 'True', 'correct' => false],
                        ['text' => 'False', 'correct' => true],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What is a safe way to protect your personal data online?',
                    'options' => [
                        ['text' => 'Use two-factor authentication', 'correct' => true],
                        ['text' => 'Post passwords publicly', 'correct' => false],
                        ['text' => 'Disable antivirus software', 'correct' => false],
                        ['text' => 'Store passwords in plain text', 'correct' => false],
                    ],
                    'points' => 5,
                ],
            ];
        }

        if (str_contains($name, 'web') || str_contains($name, 'frontend') || str_contains($name, 'full stack')) {
            return [
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which language structures web page content?',
                    'options' => [
                        ['text' => 'HTML', 'correct' => true],
                        ['text' => 'CSS', 'correct' => false],
                        ['text' => 'JavaScript', 'correct' => false],
                        ['text' => 'SQL', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'CSS is used to style the appearance of web pages.',
                    'options' => [
                        ['text' => 'True', 'correct' => true],
                        ['text' => 'False', 'correct' => false],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which language adds interactivity to web pages?',
                    'options' => [
                        ['text' => 'JavaScript', 'correct' => true],
                        ['text' => 'HTML', 'correct' => false],
                        ['text' => 'CSS', 'correct' => false],
                        ['text' => 'PHP', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which tag creates a hyperlink in HTML?',
                    'options' => [
                        ['text' => '<a>', 'correct' => true],
                        ['text' => '<div>', 'correct' => false],
                        ['text' => '<span>', 'correct' => false],
                        ['text' => '<img>', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'Responsive design helps pages display well on mobile devices.',
                    'options' => [
                        ['text' => 'True', 'correct' => true],
                        ['text' => 'False', 'correct' => false],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What CSS property controls element layout direction?',
                    'options' => [
                        ['text' => 'display:flex', 'correct' => true],
                        ['text' => 'font-size', 'correct' => false],
                        ['text' => 'color', 'correct' => false],
                        ['text' => 'border', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which protocol is used to request web pages from a server?',
                    'options' => [
                        ['text' => 'HTTP', 'correct' => true],
                        ['text' => 'SMTP', 'correct' => false],
                        ['text' => 'FTP', 'correct' => false],
                        ['text' => 'SSH', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'Using semantic HTML improves accessibility.',
                    'options' => [
                        ['text' => 'True', 'correct' => true],
                        ['text' => 'False', 'correct' => false],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What is the role of a frontend developer?',
                    'options' => [
                        ['text' => 'Build the user interface and client-side features', 'correct' => true],
                        ['text' => 'Manage database servers only', 'correct' => false],
                        ['text' => 'Install network cables', 'correct' => false],
                        ['text' => 'Write mobile operating systems', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What does full-stack development include?',
                    'options' => [
                        ['text' => 'Both frontend and backend development', 'correct' => true],
                        ['text' => 'Only mobile apps', 'correct' => false],
                        ['text' => 'Only database backups', 'correct' => false],
                        ['text' => 'Only system administration', 'correct' => false],
                    ],
                    'points' => 5,
                ],
            ];
        }

        if (str_contains($name, 'database') || str_contains($name, 'sql') || str_contains($name, 'administration')) {
            return [
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which key uniquely identifies each record in a table?',
                    'options' => [
                        ['text' => 'Primary Key', 'correct' => true],
                        ['text' => 'Foreign Key', 'correct' => false],
                        ['text' => 'Composite Key', 'correct' => false],
                        ['text' => 'Candidate Key', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'Normalization reduces data redundancy.',
                    'options' => [
                        ['text' => 'True', 'correct' => true],
                        ['text' => 'False', 'correct' => false],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What SQL command retrieves data?',
                    'options' => [
                        ['text' => 'SELECT', 'correct' => true],
                        ['text' => 'INSERT', 'correct' => false],
                        ['text' => 'UPDATE', 'correct' => false],
                        ['text' => 'DELETE', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which relationship allows one record to relate to many?',
                    'options' => [
                        ['text' => 'One-to-Many', 'correct' => true],
                        ['text' => 'One-to-One', 'correct' => false],
                        ['text' => 'Many-to-Many', 'correct' => false],
                        ['text' => 'None-to-Many', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'A foreign key enforces referential integrity.',
                    'options' => [
                        ['text' => 'True', 'correct' => true],
                        ['text' => 'False', 'correct' => false],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What is the purpose of indexes in databases?',
                    'options' => [
                        ['text' => 'Improve search performance', 'correct' => true],
                        ['text' => 'Store backup data', 'correct' => false],
                        ['text' => 'Encrypt data', 'correct' => false],
                        ['text' => 'Compress files', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which normal form is the third level of normalization?',
                    'options' => [
                        ['text' => '3NF', 'correct' => true],
                        ['text' => '2NF', 'correct' => false],
                        ['text' => '1NF', 'correct' => false],
                        ['text' => 'BCNF', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What does GROUP BY clause do?',
                    'options' => [
                        ['text' => 'Groups rows by column values', 'correct' => true],
                        ['text' => 'Sorts data', 'correct' => false],
                        ['text' => 'Joins tables', 'correct' => false],
                        ['text' => 'Deletes rows', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'A NULL value in SQL is equivalent to zero.',
                    'options' => [
                        ['text' => 'True', 'correct' => false],
                        ['text' => 'False', 'correct' => true],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What is ACID in database transactions?',
                    'options' => [
                        ['text' => 'A set of properties for reliable transactions', 'correct' => true],
                        ['text' => 'A type of database', 'correct' => false],
                        ['text' => 'A query language', 'correct' => false],
                        ['text' => 'A backup method', 'correct' => false],
                    ],
                    'points' => 5,
                ],
            ];
        }

        if (str_contains($name, 'programming')) {
            return [
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What is the time complexity of a linear search algorithm?',
                    'options' => [
                        ['text' => 'O(n)', 'correct' => true],
                        ['text' => 'O(1)', 'correct' => false],
                        ['text' => 'O(log n)', 'correct' => false],
                        ['text' => 'O(n²)', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'A recursion must have a base case to prevent infinite loops.',
                    'options' => [
                        ['text' => 'True', 'correct' => true],
                        ['text' => 'False', 'correct' => false],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which data structure uses LIFO (Last In, First Out) principle?',
                    'options' => [
                        ['text' => 'Stack', 'correct' => true],
                        ['text' => 'Queue', 'correct' => false],
                        ['text' => 'Array', 'correct' => false],
                        ['text' => 'Tree', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What will print(2 * 5 + 3) output?',
                    'options' => [
                        ['text' => '13', 'correct' => true],
                        ['text' => '16', 'correct' => false],
                        ['text' => '25', 'correct' => false],
                        ['text' => '30', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'Pointers store memory addresses.',
                    'options' => [
                        ['text' => 'True', 'correct' => true],
                        ['text' => 'False', 'correct' => false],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'Which sorting algorithm is most efficient for large datasets?',
                    'options' => [
                        ['text' => 'Merge Sort', 'correct' => true],
                        ['text' => 'Bubble Sort', 'correct' => false],
                        ['text' => 'Insertion Sort', 'correct' => false],
                        ['text' => 'Selection Sort', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What is polymorphism in OOP?',
                    'options' => [
                        ['text' => 'Objects can take multiple forms', 'correct' => true],
                        ['text' => 'Creating multiple objects', 'correct' => false],
                        ['text' => 'Storing multiple data types', 'correct' => false],
                        ['text' => 'Multiple inheritance', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_TRUE_FALSE,
                    'question' => 'Encapsulation helps protect data from unwanted access.',
                    'options' => [
                        ['text' => 'True', 'correct' => true],
                        ['text' => 'False', 'correct' => false],
                    ],
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What does an abstract class provide?',
                    'options' => [
                        ['text' => 'A blueprint for other classes', 'correct' => true],
                        ['text' => 'Instance creation', 'correct' => false],
                        ['text' => 'Type casting', 'correct' => false],
                        ['text' => 'Memory management', 'correct' => false],
                    ],
                    'points' => 5,
                ],
                [
                    'type' => Question::TYPE_MCQ,
                    'question' => 'What is the purpose of interfaces?',
                    'options' => [
                        ['text' => 'Define contracts for classes', 'correct' => true],
                        ['text' => 'Create objects', 'correct' => false],
                        ['text' => 'Manage memory', 'correct' => false],
                        ['text' => 'Compile code', 'correct' => false],
                    ],
                    'points' => 5,
                ],
            ];
        }

        // Default general questions
        return [
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'What is the primary purpose of learning information technology?',
                'options' => [
                    ['text' => 'To solve problems and improve efficiency', 'correct' => true],
                    ['text' => 'To have fun only', 'correct' => false],
                    ['text' => 'To use social media', 'correct' => false],
                    ['text' => 'To play video games', 'correct' => false],
                ],
                'points' => 5,
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'Technology continues to evolve rapidly.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which is essential for cybersecurity?',
                'options' => [
                    ['text' => 'Strong passwords', 'correct' => true],
                    ['text' => 'Sharing credentials', 'correct' => false],
                    ['text' => 'Clicking suspicious links', 'correct' => false],
                    ['text' => 'Weak authentication', 'correct' => false],
                ],
                'points' => 5,
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'Data backup is important for system reliability.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'What does API stand for?',
                'options' => [
                    ['text' => 'Application Programming Interface', 'correct' => true],
                    ['text' => 'Application Processing Information', 'correct' => false],
                    ['text' => 'Automated Programming Integration', 'correct' => false],
                    ['text' => 'Advanced Protocol Integration', 'correct' => false],
                ],
                'points' => 5,
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'Machine learning is a subset of artificial intelligence.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'What is the purpose of version control systems?',
                'options' => [
                    ['text' => 'Track changes in code over time', 'correct' => true],
                    ['text' => 'Compile code', 'correct' => false],
                    ['text' => 'Execute programs', 'correct' => false],
                    ['text' => 'Design user interfaces', 'correct' => false],
                ],
                'points' => 5,
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'What does JSON stand for?',
                'options' => [
                    ['text' => 'JavaScript Object Notation', 'correct' => true],
                    ['text' => 'Java System Object Name', 'correct' => false],
                    ['text' => 'JavaScript Operating Network', 'correct' => false],
                    ['text' => 'Joint Syntax Object Notation', 'correct' => false],
                ],
                'points' => 5,
            ],
        ];
    }
}
