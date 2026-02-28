<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Categories;
use App\Models\Teacher;
use App\Models\Quizzes;
use App\Models\Question;
use App\Models\Answer;

class QuestionsSeeder extends Seeder
{
    public function run()
    {
        $teacher = Teacher::first();

        if (!$teacher) {
            throw new \Exception('No teacher found. Run AdminUserSeeder first.');
        }

        $categories = [
            'Programming Basics',
            'Computer Hardware',
            'Networking Basics',
            'General IT Knowledge'
        ];

        foreach ($categories as $cat) {
            Categories::firstOrCreate(['name' => $cat]);
        }

        $this->createProgrammingQuiz($teacher);
        $this->createHardwareQuiz($teacher);
        $this->createNetworkingQuiz($teacher);
        $this->createGeneralITQuiz($teacher);
    }

    /* ========================================================= */
    /* ================= PROGRAMMING BASICS ==================== */
    /* ========================================================= */

    private function createProgrammingQuiz($teacher)
    {
        $quiz = Quizzes::create([
            'title' => 'Programming Basics Quiz',
            'category_id' => 1,
            'teacher_id' => $teacher->id,
            'difficulty' => 'Easy',
        ]);

        // 1
        $this->mcq($quiz, 'What does HTML stand for?', [
            'Hyper Text Markup Language' => true,
            'High Text Machine Language' => false,
            'Hyper Tabular Markup Language' => false,
            'None of the above' => false,
        ]);

        // 2
        $this->fillBlank($quiz, 'PHP is a ___ scripting language.', 'Server-side');

        // 3
        $this->trueFalse($quiz, 'JavaScript is a compiled language.', false);

        // 4
        $this->mcq($quiz, 'Which symbol is used for variables in PHP?', [
            '$' => true,
            '#' => false,
            '@' => false,
            '&' => false,
        ]);

        // 5
        $this->trueFalse($quiz, 'CSS is used for styling web pages.', true);
    }

    /* ========================================================= */
    /* ================= COMPUTER HARDWARE ===================== */
    /* ========================================================= */

    private function createHardwareQuiz($teacher)
    {
        $quiz = Quizzes::create([
            'title' => 'Computer Hardware Quiz',
            'category_id' => 2,
            'teacher_id' => $teacher->id,
            'difficulty' => 'Easy',
        ]);

        $this->mcq($quiz, 'Which of these is an input device?', [
            'Keyboard' => true,
            'Monitor' => false,
            'Printer' => false,
            'Speaker' => false,
        ]);

        $this->fillBlank($quiz, 'The brain of the computer is called the ___.', 'CPU');

        $this->trueFalse($quiz, 'RAM is permanent memory.', false);

        $this->mcq($quiz, 'Which component stores data permanently?', [
            'SSD' => true,
            'RAM' => false,
            'CPU' => false,
            'GPU' => false,
        ]);

        $this->trueFalse($quiz, 'Monitor is an output device.', true);
    }

    /* ========================================================= */
    /* ================= NETWORKING BASICS ===================== */
    /* ========================================================= */

    private function createNetworkingQuiz($teacher)
    {
        $quiz = Quizzes::create([
            'title' => 'Networking Basics Quiz',
            'category_id' => 3,
            'teacher_id' => $teacher->id,
            'difficulty' => 'Easy',
        ]);

        $this->mcq($quiz, 'What does LAN stand for?', [
            'Local Area Network' => true,
            'Large Area Node' => false,
            'Local Access Network' => false,
            'Light Area Network' => false,
        ]);

        $this->fillBlank($quiz, 'The device that connects multiple networks is called a ___.', 'Router');

        $this->trueFalse($quiz, 'IP address identifies a device on a network.', true);

        $this->mcq($quiz, 'Which device connects computers in a local network?', [
            'Switch' => true,
            'Router' => false,
            'Modem' => false,
            'Printer' => false,
        ]);

        $this->trueFalse($quiz, 'Modem provides internet access.', true);
    }

    /* ========================================================= */
    /* ================= GENERAL IT KNOWLEDGE ================== */
    /* ========================================================= */

    private function createGeneralITQuiz($teacher)
    {
        $quiz = Quizzes::create([
            'title' => 'General IT Knowledge Quiz',
            'category_id' => 4,
            'teacher_id' => $teacher->id,
            'difficulty' => 'Easy',
        ]);

        $this->mcq($quiz, 'Which of these is an operating system?', [
            'Windows' => true,
            'Word' => false,
            'Excel' => false,
            'Chrome' => false,
        ]);

        $this->fillBlank($quiz, 'The main circuit board is called the ___.', 'Motherboard');

        $this->trueFalse($quiz, 'Software is the physical component of a computer.', false);

        $this->mcq($quiz, 'Which one is a programming language?', [
            'Python' => true,
            'Photoshop' => false,
            'Excel' => false,
            'PowerPoint' => false,
        ]);

        $this->trueFalse($quiz, 'Linux is an operating system.', true);
    }

    /* ========================================================= */
    /* ================= HELPER METHODS ======================== */
    /* ========================================================= */

    private function mcq($quiz, $text, $options)
    {
        $question = Question::create([
            'quiz_id' => $quiz->id,
            'question_text' => $text,
            'question_type' => 'MCQ',
            'points' => 5
        ]);

        foreach ($options as $answer => $correct) {
            Answer::create([
                'question_id' => $question->id,
                'answer_text' => $answer,
                'is_correct' => $correct
            ]);
        }
    }

    private function fillBlank($quiz, $text, $correctAnswer)
    {
        $question = Question::create([
            'quiz_id' => $quiz->id,
            'question_text' => $text,
            'question_type' => 'FillBlank',
            'points' => 5
        ]);

        Answer::create([
            'question_id' => $question->id,
            'answer_text' => $correctAnswer,
            'is_correct' => true
        ]);
    }

    private function trueFalse($quiz, $text, $correct)
    {
        $question = Question::create([
            'quiz_id' => $quiz->id,
            'question_text' => $text,
            'question_type' => 'TrueFalse',
            'points' => 5
        ]);

        Answer::create([
            'question_id' => $question->id,
            'answer_text' => 'True',
            'is_correct' => $correct === true
        ]);

        Answer::create([
            'question_id' => $question->id,
            'answer_text' => 'False',
            'is_correct' => $correct === false
        ]);
    }
}