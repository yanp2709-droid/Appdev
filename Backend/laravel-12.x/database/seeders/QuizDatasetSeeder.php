<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuizDatasetSeeder extends Seeder
{
    private const QUIZZES_PER_SUBJECT = 20;
    private const QUESTIONS_PER_QUIZ = 10;

    private const SUBJECTS = [
        ['name' => 'Programming Basics', 'description' => 'Learn fundamental programming concepts'],
        ['name' => 'Computer Hardware', 'description' => 'Understand computer components and architecture'],
        ['name' => 'Networking Basics', 'description' => 'Introduction to computer networks'],
        ['name' => 'General IT Knowledge', 'description' => 'General information technology concepts'],
        ['name' => 'Cybersecurity Basics', 'description' => 'Foundational security concepts and best practices'],
        ['name' => 'App Development', 'description' => 'Building mobile and desktop applications'],
        ['name' => 'Basic Programming', 'description' => 'Programming fundamentals and problem solving'],
        ['name' => 'Multimedia', 'description' => 'Digital media, graphics, audio, and video concepts'],
        ['name' => 'Database Systems', 'description' => 'Relational databases and data management'],
        ['name' => 'Web Development', 'description' => 'Frontend and backend web application concepts'],
        ['name' => 'Operating Systems', 'description' => 'Core operating system concepts and processes'],
        ['name' => 'Data Structures', 'description' => 'Arrays, lists, stacks, queues, trees, and graphs'],
        ['name' => 'Algorithms', 'description' => 'Problem solving using efficient algorithm design'],
        ['name' => 'Software Engineering', 'description' => 'Software development practices and lifecycle'],
        ['name' => 'Mobile Development', 'description' => 'Creating apps for smartphones and tablets'],
        ['name' => 'Cloud Computing', 'description' => 'Internet-based computing and deployment models'],
        ['name' => 'Computer Graphics', 'description' => 'Visual representation and rendering concepts'],
        ['name' => 'Human Computer Interaction', 'description' => 'Designing usable and accessible interfaces'],
        ['name' => 'Information Systems', 'description' => 'People, processes, and technology in organizations'],
        ['name' => 'Office Productivity', 'description' => 'Productivity software and workplace tools'],
        ['name' => 'IT Ethics', 'description' => 'Ethics, privacy, and responsible technology use'],
        ['name' => 'Computer Maintenance', 'description' => 'Troubleshooting and maintaining computer systems'],
        ['name' => 'Computer Peripheral', 'description' => 'External devices and accessories for computers'],
        ['name' => 'Troubleshooting', 'description' => 'Finding and fixing technical issues'],
        ['name' => 'Network Security', 'description' => 'Protecting networks from threats and attacks'],
        ['name' => 'System Analysis', 'description' => 'Understanding requirements and system design'],
        ['name' => 'Programming Logic', 'description' => 'Logic, flow control, and structured thinking'],
        ['name' => 'Version Control', 'description' => 'Tracking changes in source code and teamwork'],
        ['name' => 'UI UX Design', 'description' => 'User interface and user experience design'],
        ['name' => 'Python Fundamentals', 'description' => 'Python syntax, basics, and core programming ideas'],
    ];

    public function run(): void
    {
        $teacher = $this->resolveTeacher();

        foreach (self::SUBJECTS as $subjectData) {
            $category = Category::updateOrCreate(
                ['name' => $subjectData['name']],
                [
                    'description' => $subjectData['description'],
                    'is_published' => true,
                    'time_limit_minutes' => 15,
                ]
            );

            $bank = $this->questionBankForSubject($category->name);

            for ($quizNumber = 1; $quizNumber <= self::QUIZZES_PER_SUBJECT; $quizNumber++) {
                $quiz = Quiz::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'title' => 'Quiz ' . $quizNumber,
                    ],
                    [
                        'teacher_id' => $teacher->id,
                        'difficulty' => $this->difficultyFor($quizNumber),
                        'duration_minutes' => 10,
                        'timer_enabled' => true,
                        'shuffle_questions' => true,
                        'shuffle_options' => true,
                        'max_attempts' => null,
                        'allow_review_before_submit' => true,
                        'show_score_immediately' => true,
                        'show_answers_after_submit' => true,
                        'show_correct_answers_after_submit' => true,
                        'is_active' => true,
                    ]
                );

                $this->seedQuizQuestions($quiz, $category->name, $quizNumber, $bank);
            }
        }
    }

    private function resolveTeacher(): User
    {
        return User::query()
            ->where('role', 'teacher')
            ->first()
            ?? User::updateOrCreate(
                ['email' => 'teacher@techquiz.com'],
                [
                    'name' => 'Default Teacher',
                    'email_verified_at' => now(),
                    'password' => 'password',
                    'role' => 'teacher',
                    'is_protected' => false,
                    'is_active' => true,
                ]
            );
    }

    private function difficultyFor(int $quizNumber): string
    {
        return match ($quizNumber % 3) {
            1 => 'Easy',
            2 => 'Medium',
            default => 'Hard',
        };
    }

    private function seedQuizQuestions(Quiz $quiz, string $subjectName, int $quizNumber, array $bank): void
    {
        $bankSize = count($bank);

        for ($questionNumber = 1; $questionNumber <= self::QUESTIONS_PER_QUIZ; $questionNumber++) {
            $templateIndex = ($quizNumber + $questionNumber - 2) % $bankSize;
            $template = $bank[$templateIndex];

            $questionText = $template['question'];
            $questionType = $template['type'];

            $question = Question::updateOrCreate(
                [
                    'quiz_id' => $quiz->id,
                    'question_text' => $questionText,
                ],
                [
                    'category_id' => $quiz->category_id,
                    'question_type' => $questionType,
                    'points' => $template['points'] ?? 5,
                    'answer_key' => $questionType === Question::TYPE_SHORT_ANSWER
                        ? ($template['answer_key'] ?? null)
                        : null,
                ]
            );

            $question->options()->delete();

            $this->seedOptions($question, $template);
        }
    }

    private function seedOptions(Question $question, array $template): void
    {
        if ($question->question_type === Question::TYPE_SHORT_ANSWER) {
            return;
        }

        $options = $template['options'] ?? [];
        foreach ($options as $index => $option) {
            QuestionOption::create([
                'question_id' => $question->id,
                'option_text' => $option['text'],
                'is_correct' => $option['correct'] ?? false,
                'order_index' => $index,
            ]);
        }
    }

    private function questionBankForSubject(string $subjectName): array
    {
        $name = strtolower($subjectName);

        if (str_contains($name, 'app development') || str_contains($name, 'mobile development')) {
            return $this->appDevelopmentBank();
        }

        if (str_contains($name, 'basic programming') || str_contains($name, 'programming basics') || str_contains($name, 'programming logic') || str_contains($name, 'python')) {
            return $this->programmingBank();
        }

        if (str_contains($name, 'multimedia') || str_contains($name, 'computer graphics') || str_contains($name, 'ui ux design')) {
            return $this->multimediaBank();
        }

        if (str_contains($name, 'database')) {
            return $this->databaseBank();
        }

        if (str_contains($name, 'web development')) {
            return $this->webBank();
        }

        if (str_contains($name, 'hardware') || str_contains($name, 'peripheral') || str_contains($name, 'maintenance')) {
            return $this->hardwareBank();
        }

        if (str_contains($name, 'network')) {
            return $this->networkBank();
        }

        if (str_contains($name, 'security') || str_contains($name, 'ethics')) {
            return $this->securityBank();
        }

        if (str_contains($name, 'operating system')) {
            return $this->osBank();
        }

        if (str_contains($name, 'data structures') || str_contains($name, 'algorithms')) {
            return $this->dsaBank();
        }

        if (str_contains($name, 'software engineering') || str_contains($name, 'system analysis')) {
            return $this->softwareBank();
        }

        return $this->generalBank();
    }

    private function appDevelopmentBank(): array
    {
        return [
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'In Android development, which lifecycle method is called when an activity becomes visible to the user?',
                'options' => [
                    ['text' => 'onResume()', 'correct' => true],
                    ['text' => 'onCreate()', 'correct' => false],
                    ['text' => 'onStart()', 'correct' => false],
                    ['text' => 'onPause()', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which Flutter widget would you use to create a scrollable list that efficiently handles large datasets?',
                'options' => [
                    ['text' => 'ListView.builder', 'correct' => true],
                    ['text' => 'Column', 'correct' => false],
                    ['text' => 'Container', 'correct' => false],
                    ['text' => 'Stack', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_MULTI_SELECT,
                'question' => 'Which of the following are valid ways to handle state management in Flutter? (Select all that apply)',
                'options' => [
                    ['text' => 'setState() method', 'correct' => true],
                    ['text' => 'Provider package', 'correct' => true],
                    ['text' => 'Bloc pattern', 'correct' => true],
                    ['text' => 'SharedPreferences', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'In Android, which permission is required to access the device\'s location?',
                'options' => [
                    ['text' => 'ACCESS_FINE_LOCATION', 'correct' => true],
                    ['text' => 'INTERNET', 'correct' => false],
                    ['text' => 'READ_EXTERNAL_STORAGE', 'correct' => false],
                    ['text' => 'CAMERA', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'In Flutter, hot reload preserves the app state while updating the UI.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What is the primary purpose of the AndroidManifest.xml file?',
                'answer_key' => 'define app components and permissions',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which of the following is NOT a valid Flutter layout widget?',
                'options' => [
                    ['text' => 'LinearLayout', 'correct' => true],
                    ['text' => 'Row', 'correct' => false],
                    ['text' => 'Column', 'correct' => false],
                    ['text' => 'Wrap', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'In Android development, what does the term "Activity" represent?',
                'options' => [
                    ['text' => 'A single screen with a user interface', 'correct' => true],
                    ['text' => 'A background service', 'correct' => false],
                    ['text' => 'A database table', 'correct' => false],
                    ['text' => 'A network request', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'Flutter widgets are immutable by default.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What Flutter command creates a new project?',
                'answer_key' => 'flutter create',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which Android component is used for long-running operations in the background?',
                'options' => [
                    ['text' => 'Service', 'correct' => true],
                    ['text' => 'Activity', 'correct' => false],
                    ['text' => 'Fragment', 'correct' => false],
                    ['text' => 'BroadcastReceiver', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_MULTI_SELECT,
                'question' => 'Which of the following are benefits of using Flutter for cross-platform development? (Select all that apply)',
                'options' => [
                    ['text' => 'Single codebase for multiple platforms', 'correct' => true],
                    ['text' => 'Native performance', 'correct' => true],
                    ['text' => 'Hot reload for faster development', 'correct' => true],
                    ['text' => 'Direct access to platform-specific APIs without plugins', 'correct' => false],
                ],
            ],
        ];
    }

    private function programmingBank(): array
    {
        return [
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'What will be the output of: print(2 + 3 * 4)?',
                'options' => [
                    ['text' => '20', 'correct' => false],
                    ['text' => '14', 'correct' => true],
                    ['text' => '24', 'correct' => false],
                    ['text' => '11', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which data structure follows Last In, First Out (LIFO) principle?',
                'options' => [
                    ['text' => 'Stack', 'correct' => true],
                    ['text' => 'Queue', 'correct' => false],
                    ['text' => 'Array', 'correct' => false],
                    ['text' => 'Linked List', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'In most programming languages, array indices start from 1.',
                'options' => [
                    ['text' => 'True', 'correct' => false],
                    ['text' => 'False', 'correct' => true],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What is the time complexity of binary search algorithm?',
                'answer_key' => 'O(log n)',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which of the following is NOT a primitive data type in Java?',
                'options' => [
                    ['text' => 'String', 'correct' => true],
                    ['text' => 'int', 'correct' => false],
                    ['text' => 'boolean', 'correct' => false],
                    ['text' => 'char', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_MULTI_SELECT,
                'question' => 'Which of the following are object-oriented programming principles? (Select all that apply)',
                'options' => [
                    ['text' => 'Encapsulation', 'correct' => true],
                    ['text' => 'Inheritance', 'correct' => true],
                    ['text' => 'Polymorphism', 'correct' => true],
                    ['text' => 'Compilation', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'What does the acronym SQL stand for?',
                'options' => [
                    ['text' => 'Structured Query Language', 'correct' => true],
                    ['text' => 'Simple Query Language', 'correct' => false],
                    ['text' => 'Standard Query Language', 'correct' => false],
                    ['text' => 'System Query Language', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'A recursive function must have a base case to prevent infinite recursion.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What programming paradigm focuses on "what to solve" rather than "how to solve"?',
                'answer_key' => 'declarative',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which sorting algorithm has the best average case time complexity?',
                'options' => [
                    ['text' => 'Quick Sort', 'correct' => true],
                    ['text' => 'Bubble Sort', 'correct' => false],
                    ['text' => 'Insertion Sort', 'correct' => false],
                    ['text' => 'Selection Sort', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'In Python, what does the "len()" function return?',
                'options' => [
                    ['text' => 'The length of a sequence', 'correct' => true],
                    ['text' => 'The last element of a list', 'correct' => false],
                    ['text' => 'The sum of all elements', 'correct' => false],
                    ['text' => 'The maximum value', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'Exception handling allows a program to continue executing even when errors occur.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
        ];
    }

    private function multimediaBank(): array
    {
        return [
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which file format is commonly used for lossless images?',
                'options' => [
                    ['text' => 'PNG', 'correct' => true],
                    ['text' => 'MP3', 'correct' => false],
                    ['text' => 'TXT', 'correct' => false],
                    ['text' => 'CSV', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'JPEG is commonly used for photographic images.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What do you call the measurement of an image in pixels horizontally and vertically?',
                'answer_key' => 'resolution',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which audio file format is widely used for compressed music files?',
                'options' => [
                    ['text' => 'MP3', 'correct' => true],
                    ['text' => 'GIF', 'correct' => false],
                    ['text' => 'DOCX', 'correct' => false],
                    ['text' => 'BMP', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'Video editing can involve trimming clips and adding transitions.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What term describes the sequence of frames shown quickly to create motion?',
                'answer_key' => 'frame rate',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which tool is commonly used to edit raster images?',
                'options' => [
                    ['text' => 'Photoshop', 'correct' => true],
                    ['text' => 'Excel', 'correct' => false],
                    ['text' => 'PowerPoint', 'correct' => false],
                    ['text' => 'Notepad', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'Vector graphics are made of pixels.',
                'options' => [
                    ['text' => 'True', 'correct' => false],
                    ['text' => 'False', 'correct' => true],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What file extension is commonly used for animated graphics on the web?',
                'answer_key' => 'gif',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'What does "audio mixing" usually refer to?',
                'options' => [
                    ['text' => 'Combining and adjusting sound tracks', 'correct' => true],
                    ['text' => 'Editing code files', 'correct' => false],
                    ['text' => 'Changing file permissions', 'correct' => false],
                    ['text' => 'Compressing images', 'correct' => false],
                ],
            ],
        ];
    }

    private function databaseBank(): array
    {
        return [
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which normal form eliminates transitive dependencies?',
                'options' => [
                    ['text' => 'Third Normal Form (3NF)', 'correct' => true],
                    ['text' => 'First Normal Form (1NF)', 'correct' => false],
                    ['text' => 'Second Normal Form (2NF)', 'correct' => false],
                    ['text' => 'Boyce-Codd Normal Form (BCNF)', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'What type of relationship exists between a Customer and Orders tables where one customer can have many orders?',
                'options' => [
                    ['text' => 'One-to-Many', 'correct' => true],
                    ['text' => 'Many-to-Many', 'correct' => false],
                    ['text' => 'One-to-One', 'correct' => false],
                    ['text' => 'Many-to-One', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'A primary key can contain NULL values.',
                'options' => [
                    ['text' => 'True', 'correct' => false],
                    ['text' => 'False', 'correct' => true],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What SQL clause is used to filter records before grouping?',
                'answer_key' => 'WHERE',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which of the following is NOT a valid SQL JOIN type?',
                'options' => [
                    ['text' => 'CROSS JOIN', 'correct' => false],
                    ['text' => 'INNER JOIN', 'correct' => false],
                    ['text' => 'OUTER JOIN', 'correct' => false],
                    ['text' => 'STRAIGHT JOIN', 'correct' => true],
                ],
            ],
            [
                'type' => Question::TYPE_MULTI_SELECT,
                'question' => 'Which of the following are ACID properties of database transactions? (Select all that apply)',
                'options' => [
                    ['text' => 'Atomicity', 'correct' => true],
                    ['text' => 'Consistency', 'correct' => true],
                    ['text' => 'Isolation', 'correct' => true],
                    ['text' => 'Durability', 'correct' => true],
                ],
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'What does the GROUP BY clause do in SQL?',
                'options' => [
                    ['text' => 'Groups rows that have the same values in specified columns', 'correct' => true],
                    ['text' => 'Sorts the result set in ascending order', 'correct' => false],
                    ['text' => 'Limits the number of rows returned', 'correct' => false],
                    ['text' => 'Joins tables based on a condition', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'In a relational database, each table must have a primary key.',
                'options' => [
                    ['text' => 'True', 'correct' => false],
                    ['text' => 'False', 'correct' => true],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What is the purpose of a foreign key in a database?',
                'answer_key' => 'establish relationship between tables',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which SQL statement is used to modify existing records in a table?',
                'options' => [
                    ['text' => 'UPDATE', 'correct' => true],
                    ['text' => 'INSERT', 'correct' => false],
                    ['text' => 'SELECT', 'correct' => false],
                    ['text' => 'DELETE', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'What is a stored procedure?',
                'options' => [
                    ['text' => 'A prepared SQL code that can be saved and reused', 'correct' => true],
                    ['text' => 'A temporary table created during query execution', 'correct' => false],
                    ['text' => 'A type of database index', 'correct' => false],
                    ['text' => 'A database backup file', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'Views in SQL are virtual tables that can be queried like regular tables.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
        ];
    }

    private function webBank(): array
    {
        return [
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which HTTP status code indicates a successful response?',
                'options' => [
                    ['text' => '200 OK', 'correct' => true],
                    ['text' => '404 Not Found', 'correct' => false],
                    ['text' => '500 Internal Server Error', 'correct' => false],
                    ['text' => '301 Moved Permanently', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'What does the CSS property "display: flex" do?',
                'options' => [
                    ['text' => 'Creates a flexible layout container', 'correct' => true],
                    ['text' => 'Hides the element', 'correct' => false],
                    ['text' => 'Makes text bold', 'correct' => false],
                    ['text' => 'Centers the element horizontally', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'JavaScript is a server-side programming language.',
                'options' => [
                    ['text' => 'True', 'correct' => false],
                    ['text' => 'False', 'correct' => true],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What is the purpose of the alt attribute in an img tag?',
                'answer_key' => 'provide alternative text for accessibility',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which of the following is NOT a valid CSS position value?',
                'options' => [
                    ['text' => 'center', 'correct' => true],
                    ['text' => 'relative', 'correct' => false],
                    ['text' => 'absolute', 'correct' => false],
                    ['text' => 'fixed', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_MULTI_SELECT,
                'question' => 'Which HTML5 semantic elements help with SEO and accessibility? (Select all that apply)',
                'options' => [
                    ['text' => '<header>', 'correct' => true],
                    ['text' => '<nav>', 'correct' => true],
                    ['text' => '<article>', 'correct' => true],
                    ['text' => '<div>', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'What does the JavaScript method "addEventListener" do?',
                'options' => [
                    ['text' => 'Attaches an event handler to an element', 'correct' => true],
                    ['text' => 'Creates a new HTML element', 'correct' => false],
                    ['text' => 'Changes CSS styles', 'correct' => false],
                    ['text' => 'Makes an HTTP request', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'The "defer" attribute on a script tag delays execution until the document is parsed.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What CSS property is used to create rounded corners?',
                'answer_key' => 'border-radius',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which HTTP method is used to update existing resources?',
                'options' => [
                    ['text' => 'PUT', 'correct' => true],
                    ['text' => 'GET', 'correct' => false],
                    ['text' => 'POST', 'correct' => false],
                    ['text' => 'DELETE', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'What is the purpose of a CSS media query?',
                'options' => [
                    ['text' => 'Apply styles based on device characteristics', 'correct' => true],
                    ['text' => 'Query a database', 'correct' => false],
                    ['text' => 'Make HTTP requests', 'correct' => false],
                    ['text' => 'Validate HTML', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'Local storage persists data even after the browser is closed.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
        ];
    }

    private function hardwareBank(): array
    {
        return [
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which component stores data permanently?',
                'options' => [
                    ['text' => 'SSD', 'correct' => true],
                    ['text' => 'RAM', 'correct' => false],
                    ['text' => 'CPU', 'correct' => false],
                    ['text' => 'Cache', 'correct' => false],
                ],
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
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What does CPU stand for?',
                'answer_key' => 'central processing unit',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which device is an input device?',
                'options' => [
                    ['text' => 'Keyboard', 'correct' => true],
                    ['text' => 'Monitor', 'correct' => false],
                    ['text' => 'Speaker', 'correct' => false],
                    ['text' => 'Printer', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'A monitor is an output device.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What component is often called the brain of the computer?',
                'answer_key' => 'cpu',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which component converts AC power to DC power inside a PC?',
                'options' => [
                    ['text' => 'Power Supply Unit', 'correct' => true],
                    ['text' => 'Motherboard', 'correct' => false],
                    ['text' => 'Heat Sink', 'correct' => false],
                    ['text' => 'RAM', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'The motherboard connects the major components of a computer.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What hardware component is used to cool the CPU?',
                'answer_key' => 'cooling fan',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which peripheral is used to produce hard copies of documents?',
                'options' => [
                    ['text' => 'Printer', 'correct' => true],
                    ['text' => 'Mouse', 'correct' => false],
                    ['text' => 'Webcam', 'correct' => false],
                    ['text' => 'Scanner', 'correct' => false],
                ],
            ],
        ];
    }

    private function networkBank(): array
    {
        return [
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'What does LAN stand for?',
                'options' => [
                    ['text' => 'Local Area Network', 'correct' => true],
                    ['text' => 'Large Area Node', 'correct' => false],
                    ['text' => 'Long Access Network', 'correct' => false],
                    ['text' => 'Linked Application Node', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'A router connects different networks together.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What does IP stand for?',
                'answer_key' => 'internet protocol',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which device is used to connect computers within a LAN?',
                'options' => [
                    ['text' => 'Switch', 'correct' => true],
                    ['text' => 'Monitor', 'correct' => false],
                    ['text' => 'Speaker', 'correct' => false],
                    ['text' => 'Keyboard', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'Wi-Fi is a wireless networking technology.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What device provides internet access to a home network?',
                'answer_key' => 'modem',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which protocol is commonly used to browse websites?',
                'options' => [
                    ['text' => 'HTTP', 'correct' => true],
                    ['text' => 'SMTP', 'correct' => false],
                    ['text' => 'SNMP', 'correct' => false],
                    ['text' => 'ARP', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'An IP address identifies a device on a network.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What is the default port number for HTTPS?',
                'answer_key' => '443',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which network device filters traffic based on rules?',
                'options' => [
                    ['text' => 'Firewall', 'correct' => true],
                    ['text' => 'Projector', 'correct' => false],
                    ['text' => 'Mouse', 'correct' => false],
                    ['text' => 'Scanner', 'correct' => false],
                ],
            ],
        ];
    }

    private function securityBank(): array
    {
        return [
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'What is the strongest type of password?',
                'options' => [
                    ['text' => 'A long password with mixed characters', 'correct' => true],
                    ['text' => 'Your birthday', 'correct' => false],
                    ['text' => '12345678', 'correct' => false],
                    ['text' => 'Your name', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'Phishing is a technique used to trick people into revealing sensitive information.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What is the term for software designed to damage or steal data?',
                'answer_key' => 'malware',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which of the following is a security best practice?',
                'options' => [
                    ['text' => 'Using two-factor authentication', 'correct' => true],
                    ['text' => 'Sharing passwords with friends', 'correct' => false],
                    ['text' => 'Writing passwords on paper visibly', 'correct' => false],
                    ['text' => 'Disabling all updates', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'Antivirus software helps detect and remove malicious software.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What do we call a secret word or phrase used to verify identity?',
                'answer_key' => 'password',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which term refers to pretending to be a trusted source in order to steal data?',
                'options' => [
                    ['text' => 'Phishing', 'correct' => true],
                    ['text' => 'Debugging', 'correct' => false],
                    ['text' => 'Formatting', 'correct' => false],
                    ['text' => 'Indexing', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'Regular software updates can improve security.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What is the practice of checking user identity called?',
                'answer_key' => 'authentication',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'What should you do with suspicious email links?',
                'options' => [
                    ['text' => 'Avoid clicking them', 'correct' => true],
                    ['text' => 'Share them with everyone', 'correct' => false],
                    ['text' => 'Enter your password immediately', 'correct' => false],
                    ['text' => 'Send them to random contacts', 'correct' => false],
                ],
            ],
        ];
    }

    private function osBank(): array
    {
        return [
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which of the following is an operating system?',
                'options' => [
                    ['text' => 'Windows', 'correct' => true],
                    ['text' => 'Microsoft Word', 'correct' => false],
                    ['text' => 'Google Chrome', 'correct' => false],
                    ['text' => 'VLC Player', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'An operating system manages hardware and software resources.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What is the command line interface in Linux often called?',
                'answer_key' => 'terminal',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which task is usually handled by the OS?',
                'options' => [
                    ['text' => 'Memory management', 'correct' => true],
                    ['text' => 'Writing essays', 'correct' => false],
                    ['text' => 'Printing money', 'correct' => false],
                    ['text' => 'Drawing charts only', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'A file system organizes and stores files on a device.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What is the process of loading the OS when a computer starts called?',
                'answer_key' => 'booting',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which of these is a Linux distribution?',
                'options' => [
                    ['text' => 'Ubuntu', 'correct' => true],
                    ['text' => 'Excel', 'correct' => false],
                    ['text' => 'Photoshop', 'correct' => false],
                    ['text' => 'PowerPoint', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'Task Manager can be used to monitor running processes in Windows.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What is the background program that helps the OS manage hardware called?',
                'answer_key' => 'driver',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which feature allows multiple applications to run at once?',
                'options' => [
                    ['text' => 'Multitasking', 'correct' => true],
                    ['text' => 'Formatting', 'correct' => false],
                    ['text' => 'Compression', 'correct' => false],
                    ['text' => 'Partitioning', 'correct' => false],
                ],
            ],
        ];
    }

    private function dsaBank(): array
    {
        return [
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which data structure works on a First In, First Out basis?',
                'options' => [
                    ['text' => 'Queue', 'correct' => true],
                    ['text' => 'Stack', 'correct' => false],
                    ['text' => 'Tree', 'correct' => false],
                    ['text' => 'Graph', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'A stack follows Last In, First Out behavior.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What is the term for checking each item in a structure one by one?',
                'answer_key' => 'traversal',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which structure is best for modeling hierarchical data?',
                'options' => [
                    ['text' => 'Tree', 'correct' => true],
                    ['text' => 'Queue', 'correct' => false],
                    ['text' => 'Stack', 'correct' => false],
                    ['text' => 'Array', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'An algorithm is a step-by-step procedure to solve a problem.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What term describes the time required by an algorithm as input size grows?',
                'answer_key' => 'time complexity',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which search method repeatedly divides the search space in half?',
                'options' => [
                    ['text' => 'Binary search', 'correct' => true],
                    ['text' => 'Linear search', 'correct' => false],
                    ['text' => 'Bubble sort', 'correct' => false],
                    ['text' => 'Depth-first search', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'A graph consists of vertices and edges.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What term describes how much memory an algorithm uses?',
                'answer_key' => 'space complexity',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which sorting algorithm repeatedly swaps adjacent items?',
                'options' => [
                    ['text' => 'Bubble sort', 'correct' => true],
                    ['text' => 'Binary search', 'correct' => false],
                    ['text' => 'Merge sort', 'correct' => false],
                    ['text' => 'Selection sort', 'correct' => false],
                ],
            ],
        ];
    }

    private function softwareBank(): array
    {
        return [
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which phase usually comes first in the software development life cycle?',
                'options' => [
                    ['text' => 'Requirements analysis', 'correct' => true],
                    ['text' => 'Deployment', 'correct' => false],
                    ['text' => 'Maintenance', 'correct' => false],
                    ['text' => 'Testing', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'Testing helps find defects before software is released.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What document describes what the software should do?',
                'answer_key' => 'requirements specification',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'What is a common benefit of version control?',
                'options' => [
                    ['text' => 'Tracking changes over time', 'correct' => true],
                    ['text' => 'Deleting source code', 'correct' => false],
                    ['text' => 'Slowing development', 'correct' => false],
                    ['text' => 'Removing teamwork', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'Maintenance can involve fixing bugs after release.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What do we call a small piece of software code that improves an existing system?',
                'answer_key' => 'patch',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which practice helps developers manage work in teams?',
                'options' => [
                    ['text' => 'Source control branching', 'correct' => true],
                    ['text' => 'Ignoring bugs', 'correct' => false],
                    ['text' => 'Copying files manually only', 'correct' => false],
                    ['text' => 'Never testing code', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'A prototype can help validate an idea early.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What is the stage where software is delivered to users called?',
                'answer_key' => 'deployment',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which term describes making changes to improve code quality without changing behavior?',
                'options' => [
                    ['text' => 'Refactoring', 'correct' => true],
                    ['text' => 'Formatting', 'correct' => false],
                    ['text' => 'Compiling', 'correct' => false],
                    ['text' => 'Uploading', 'correct' => false],
                ],
            ],
        ];
    }

    private function generalBank(): array
    {
        return [
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which of the following is an example of software?',
                'options' => [
                    ['text' => 'Microsoft Word', 'correct' => true],
                    ['text' => 'Monitor', 'correct' => false],
                    ['text' => 'Keyboard', 'correct' => false],
                    ['text' => 'Mouse pad', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'A browser is used to access websites.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What do we call the physical parts of a computer?',
                'answer_key' => 'hardware',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which of the following is an input device?',
                'options' => [
                    ['text' => 'Mouse', 'correct' => true],
                    ['text' => 'Projector', 'correct' => false],
                    ['text' => 'Speaker', 'correct' => false],
                    ['text' => 'Printer', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'Cloud storage lets you save files online.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What do we call a set of instructions that tells a computer what to do?',
                'answer_key' => 'program',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which file format is typically used for spreadsheet data?',
                'options' => [
                    ['text' => 'CSV', 'correct' => true],
                    ['text' => 'MP4', 'correct' => false],
                    ['text' => 'JPG', 'correct' => false],
                    ['text' => 'EXE', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_TRUE_FALSE,
                'question' => 'An email address is used to send and receive messages over the internet.',
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'type' => Question::TYPE_SHORT_ANSWER,
                'question' => 'What device is often used to point and click on a computer screen?',
                'answer_key' => 'mouse',
            ],
            [
                'type' => Question::TYPE_MCQ,
                'question' => 'Which one is a storage device?',
                'options' => [
                    ['text' => 'Hard drive', 'correct' => true],
                    ['text' => 'Monitor', 'correct' => false],
                    ['text' => 'Speaker', 'correct' => false],
                    ['text' => 'Microphone', 'correct' => false],
                ],
            ],
        ];
    }
}
