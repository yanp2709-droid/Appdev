<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Teacher;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\Answer;

class TechQuizSeeder extends Seeder
{
    public function run()
    {
        // --- Categories ---
        $categories = ['Programming Basics', 'Computer Hardware', 'Networking Basics', 'General IT Knowledge'];
        foreach ($categories as $cat) {
            Category::create(['name' => $cat]);
        }

        // --- Teachers ---
        $teacher1 = Teacher::create([
            'name' => 'John Doe',
            'email' => 'teacher@example.com',
            'password' => bcrypt('password')
        ]);

        $teacher2 = Teacher::create([
            'name' => 'Jane Smith',
            'email' => 'teacher2@example.com',
            'password' => bcrypt('password')
        ]);

        // --- Quizzes ---
        $quizzes = [
            ['title'=>'Programming Basics Quiz','category_id'=>1,'teacher_id'=>$teacher1->id,'difficulty'=>'Easy'],
            ['title'=>'Computer Hardware Quiz','category_id'=>2,'teacher_id'=>$teacher2->id,'difficulty'=>'Medium'],
            ['title'=>'Networking Basics Quiz','category_id'=>3,'teacher_id'=>$teacher1->id,'difficulty'=>'Hard'],
            ['title'=>'General IT Knowledge Quiz','category_id'=>4,'teacher_id'=>$teacher2->id,'difficulty'=>'Easy'],
        ];

        foreach($quizzes as $q) {
            $quiz = Quiz::create($q);

            // --- Questions and Answers based on category ---
            switch($quiz->category_id) {
                case 1: // Programming Basics
                    $this->createProgrammingQuestions($quiz);
                    break;
                case 2: // Computer Hardware
                    $this->createHardwareQuestions($quiz);
                    break;
                case 3: // Networking Basics
                    $this->createNetworkingQuestions($quiz);
                    break;
                case 4: // General IT Knowledge
                    $this->createGeneralITQuestions($quiz);
                    break;
            }
        }
    }

    private function createProgrammingQuestions($quiz) {
        // MCQ
        $q1 = Question::create([
            'quiz_id'=>$quiz->id,'question_text'=>'What does HTML stand for?',
            'question_type'=>'MCQ','points'=>5
        ]);
        Answer::create(['question_id'=>$q1->id,'answer_text'=>'Hyper Text Markup Language','is_correct'=>true]);
        Answer::create(['question_id'=>$q1->id,'answer_text'=>'High Text Machine Language','is_correct'=>false]);
        Answer::create(['question_id'=>$q1->id,'answer_text'=>'Hyper Tabular Markup Language','is_correct'=>false]);
        Answer::create(['question_id'=>$q1->id,'answer_text'=>'None of the above','is_correct'=>false]);

        // Fill in the blank
        $q2 = Question::create([
            'quiz_id'=>$quiz->id,'question_text'=>'PHP is a ___ scripting language.',
            'question_type'=>'FillBlank','points'=>5
        ]);
        Answer::create(['question_id'=>$q2->id,'answer_text'=>'Server-side','is_correct'=>true]);

        // True/False
        $q3 = Question::create([
            'quiz_id'=>$quiz->id,'question_text'=>'JavaScript is a compiled language.',
            'question_type'=>'TrueFalse','points'=>5
        ]);
        Answer::create(['question_id'=>$q3->id,'answer_text'=>'True','is_correct'=>false]);
        Answer::create(['question_id'=>$q3->id,'answer_text'=>'False','is_correct'=>true]);

        // Guess the picture
        $q4 = Question::create([
            'quiz_id'=>$quiz->id,'question_text'=>'Identify the language from the PHP logo image.',
            'question_type'=>'Picture','points'=>5
        ]);
        Answer::create(['question_id'=>$q4->id,'answer_text'=>'PHP','is_correct'=>true]);

        // Matching
        $q5 = Question::create([
            'quiz_id'=>$quiz->id,'question_text'=>'Match HTML tags with their descriptions',
            'question_type'=>'Matching','points'=>5
        ]);
        Answer::create(['question_id'=>$q5->id,'answer_text'=>'<p>→paragraph','is_correct'=>true]);
        Answer::create(['question_id'=>$q5->id,'answer_text'=>'<a>→link','is_correct'=>true]);
        Answer::create(['question_id'=>$q5->id,'answer_text'=>'<h1>→header','is_correct'=>true]);
    }

    private function createHardwareQuestions($quiz){
        // MCQ
        $q1 = Question::create([
            'quiz_id'=>$quiz->id,'question_text'=>'Which of these is an input device?',
            'question_type'=>'MCQ','points'=>5
        ]);
        Answer::create(['question_id'=>$q1->id,'answer_text'=>'Keyboard','is_correct'=>true]);
        Answer::create(['question_id'=>$q1->id,'answer_text'=>'Monitor','is_correct'=>false]);
        Answer::create(['question_id'=>$q1->id,'answer_text'=>'Printer','is_correct'=>false]);
        Answer::create(['question_id'=>$q1->id,'answer_text'=>'Speaker','is_correct'=>false]);

        // Fill in the blank
        $q2 = Question::create([
            'quiz_id'=>$quiz->id,'question_text'=>'The brain of the computer is called the ___.',
            'question_type'=>'FillBlank','points'=>5
        ]);
        Answer::create(['question_id'=>$q2->id,'answer_text'=>'CPU','is_correct'=>true]);

        // True/False
        $q3 = Question::create([
            'quiz_id'=>$quiz->id,'question_text'=>'RAM is permanent memory.',
            'question_type'=>'TrueFalse','points'=>5
        ]);
        Answer::create(['question_id'=>$q3->id,'answer_text'=>'True','is_correct'=>false]);
        Answer::create(['question_id'=>$q3->id,'answer_text'=>'False','is_correct'=>true]);

        // Guess the picture
        $q4 = Question::create([
            'quiz_id'=>$quiz->id,'question_text'=>'Identify this hardware from the image (Hard Drive).',
            'question_type'=>'Picture','points'=>5
        ]);
        Answer::create(['question_id'=>$q4->id,'answer_text'=>'Hard Drive','is_correct'=>true]);

        // Matching
        $q5 = Question::create([
            'quiz_id'=>$quiz->id,'question_text'=>'Match hardware to function.',
            'question_type'=>'Matching','points'=>5
        ]);
        Answer::create(['question_id'=>$q5->id,'answer_text'=>'CPU→Processing','is_correct'=>true]);
        Answer::create(['question_id'=>$q5->id,'answer_text'=>'RAM→Temporary Storage','is_correct'=>true]);
        Answer::create(['question_id'=>$q5->id,'answer_text'=>'SSD→Permanent Storage','is_correct'=>true]);
    }

    private function createNetworkingQuestions($quiz){
        // similar pattern
        $q1 = Question::create(['quiz_id'=>$quiz->id,'question_text'=>'What does LAN stand for?','question_type'=>'MCQ','points'=>5]);
        Answer::create(['question_id'=>$q1->id,'answer_text'=>'Local Area Network','is_correct'=>true]);
        Answer::create(['question_id'=>$q1->id,'answer_text'=>'Large Area Node','is_correct'=>false]);
        Answer::create(['question_id'=>$q1->id,'answer_text'=>'Local Access Network','is_correct'=>false]);
        Answer::create(['question_id'=>$q1->id,'answer_text'=>'Light Area Network','is_correct'=>false]);

        $q2 = Question::create(['quiz_id'=>$quiz->id,'question_text'=>'The device that connects multiple networks together is called a ___.','question_type'=>'FillBlank','points'=>5]);
        Answer::create(['question_id'=>$q2->id,'answer_text'=>'Router','is_correct'=>true]);

        $q3 = Question::create(['quiz_id'=>$quiz->id,'question_text'=>'IP address identifies a device on a network.','question_type'=>'TrueFalse','points'=>5]);
        Answer::create(['question_id'=>$q3->id,'answer_text'=>'True','is_correct'=>true]);
        Answer::create(['question_id'=>$q3->id,'answer_text'=>'False','is_correct'=>false]);

        $q4 = Question::create(['quiz_id'=>$quiz->id,'question_text'=>'Identify this network from the Wi-Fi symbol image.','question_type'=>'Picture','points'=>5]);
        Answer::create(['question_id'=>$q4->id,'answer_text'=>'Wireless Network','is_correct'=>true]);

        $q5 = Question::create(['quiz_id'=>$quiz->id,'question_text'=>'Match network devices to function.','question_type'=>'Matching','points'=>5]);
        Answer::create(['question_id'=>$q5->id,'answer_text'=>'Router→Routing','is_correct'=>true]);
        Answer::create(['question_id'=>$q5->id,'answer_text'=>'Switch→Connecting devices','is_correct'=>true]);
        Answer::create(['question_id'=>$q5->id,'answer_text'=>'Modem→Internet Access','is_correct'=>true]);
    }

    private function createGeneralITQuestions($quiz){
        $q1 = Question::create(['quiz_id'=>$quiz->id,'question_text'=>'Which of these is an operating system?','question_type'=>'MCQ','points'=>5]);
        Answer::create(['question_id'=>$q1->id,'answer_text'=>'Windows','is_correct'=>true]);
        Answer::create(['question_id'=>$q1->id,'answer_text'=>'Word','is_correct'=>false]);
        Answer::create(['question_id'=>$q1->id,'answer_text'=>'Excel','is_correct'=>false]);
        Answer::create(['question_id'=>$q1->id,'answer_text'=>'Chrome','is_correct'=>false]);

        $q2 = Question::create(['quiz_id'=>$quiz->id,'question_text'=>'The main circuit board is called the ___.','question_type'=>'FillBlank','points'=>5]);
        Answer::create(['question_id'=>$q2->id,'answer_text'=>'Motherboard','is_correct'=>true]);

        $q3 = Question::create(['quiz_id'=>$quiz->id,'question_text'=>'Software is the physical component of a computer.','question_type'=>'TrueFalse','points'=>5]);
        Answer::create(['question_id'=>$q3->id,'answer_text'=>'True','is_correct'=>false]);
        Answer::create(['question_id'=>$q3->id,'answer_text'=>'False','is_correct'=>true]);

        $q4 = Question::create(['quiz_id'=>$quiz->id,'question_text'=>'Identify this OS from the Linux penguin logo.','question_type'=>'Picture','points'=>5]);
        Answer::create(['question_id'=>$q4->id,'answer_text'=>'Linux','is_correct'=>true]);

        $q5 = Question::create(['quiz_id'=>$quiz->id,'question_text'=>'Match terms to definition.','question_type'=>'Matching','points'=>5]);
        Answer::create(['question_id'=>$q5->id,'answer_text'=>'BIOS→Boot firmware','is_correct'=>true]);
        Answer::create(['question_id'=>$q5->id,'answer_text'=>'GPU→Graphics card','is_correct'=>true]);
        Answer::create(['question_id'=>$q5->id,'answer_text'=>'HDD→Storage device','is_correct'=>true]);
    }
}