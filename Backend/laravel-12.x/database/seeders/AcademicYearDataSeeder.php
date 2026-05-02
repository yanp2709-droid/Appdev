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

            for ($qNum = 1; $qNum <= self::QUESTIONS_PER_QUIZ; $qNum++) {
                $questionTypes = [Question::TYPE_MCQ, Question::TYPE_MCQ, Question::TYPE_TRUE_FALSE];
                $questionType = $questionTypes[array_rand($questionTypes)];
                $questionTimestamp = $this->getRandomTimestampWithinYear(
                    Carbon::parse($yearInfo['start']),
                    Carbon::parse($yearInfo['end']),
                );
                
                $questionText = "Sample question {$qNum} for {$subjectData['name']} - {$difficulty} level";
                
                $question = Question::updateOrCreate(
                    [
                        'quiz_id' => $quiz->id,
                        'question_text' => $questionText,
                    ],
                    [
                        'category_id' => $category->id,
                        'question_type' => $questionType,
                        'points' => 5,
                    ]
                );
                $this->applyTimestamps($question, $questionTimestamp);

                $question->options()->delete();

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
                    $options = [
                        ['text' => 'Option A', 'correct' => true],
                        ['text' => 'Option B', 'correct' => false],
                        ['text' => 'Option C', 'correct' => false],
                        ['text' => 'Option D', 'correct' => false],
                    ];
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
}
