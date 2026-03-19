<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class QuestionImportExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $teacher;
    private User $student;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->teacher = User::factory()->teacher()->create();
        $this->student = User::factory()->student()->create();
        $this->category = Category::factory()->create(['name' => 'Mathematics']);
    }

    /**
     * Test admin can export questions as JSON
     */
    public function test_admin_can_export_json()
    {
        // Create test questions
        $question1 = Question::factory()->create([
            'category_id' => $this->category->id,
            'question_type' => 'mcq',
            'points' => 5,
        ]);

        QuestionOption::factory()->create([
            'question_id' => $question1->id,
            'is_correct' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/questions/export/json');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'questions' => [
                         '*' => [
                             'id',
                             'question_text',
                             'question_type',
                             'category',
                             'options',
                             'correct_answer',
                             'points',
                         ],
                     ],
                 ]);

        $this->assertNotEmpty($response->json('questions'));
    }

    /**
     * Test admin can export questions as CSV
     */
    public function test_admin_can_export_csv()
    {
        Question::factory()->create([
            'category_id' => $this->category->id,
            'question_type' => 'mcq',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/questions/export/csv');

        $response->assertStatus(200)
                 ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    /**
     * Test teacher can export questions
     */
    public function test_teacher_can_export_questions()
    {
        $response = $this->actingAs($this->teacher)
            ->getJson('/api/admin/questions/export/json');

        $response->assertStatus(200);
    }

    /**
     * Test student cannot export questions
     */
    public function test_student_cannot_export_questions()
    {
        $response = $this->actingAs($this->student)
            ->getJson('/api/admin/questions/export/json');

        $response->assertStatus(403);
    }

    /**
     * Test unauthenticated user cannot export
     */
    public function test_unauthenticated_user_cannot_export()
    {
        $response = $this->getJson('/api/admin/questions/export/json');

        $response->assertStatus(401);
    }

    /**
     * Test import JSON with valid data
     */
    public function test_import_json_with_valid_data()
    {
        $payload = [
            'questions' => [
                [
                    'question_text' => 'What is 2+2?',
                    'category' => 'Mathematics',
                    'question_type' => 'mcq',
                    'options' => ['3', '4', '5'],
                    'correct_answer' => '4',
                    'points' => 5,
                    'answer_key' => null,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/questions/import/json', $payload);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'success')
                 ->assertJsonPath('imported_count', 1);

        $this->assertDatabaseHas('questions', [
            'question_text' => 'What is 2+2?',
        ]);
    }

    /**
     * Test import JSON rejects invalid question type
     */
    public function test_import_json_rejects_invalid_type()
    {
        $payload = [
            'questions' => [
                [
                    'question_text' => 'Test question',
                    'category' => 'Mathematics',
                    'question_type' => 'invalid_type',
                    'options' => ['A', 'B'],
                    'correct_answer' => 'A',
                    'points' => 5,
                    'answer_key' => null,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/questions/import/json', $payload);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'failed')
                 ->assertJsonPath('imported_count', 0);
    }

    /**
     * Test import JSON rejects missing required fields
     */
    public function test_import_json_rejects_missing_fields()
    {
        $payload = [
            'questions' => [
                [
                    // Missing question_text
                    'category' => 'Mathematics',
                    'question_type' => 'mcq',
                    'options' => ['A', 'B'],
                    'correct_answer' => 'A',
                    'points' => 5,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/questions/import/json', $payload);

        $response->assertStatus(200)
                 ->assertJsonPath('imported_count', 0)
                 ->assertJsonPath('status', 'failed');
    }

    /**
     * Test import JSON with non-existent category
     */
    public function test_import_json_rejects_nonexistent_category()
    {
        $payload = [
            'questions' => [
                [
                    'question_text' => 'Test',
                    'category' => 'NonExistentCategory',
                    'question_type' => 'mcq',
                    'options' => ['A', 'B'],
                    'correct_answer' => 'A',
                    'points' => 5,
                    'answer_key' => null,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/questions/import/json', $payload);

        $response->assertStatus(200)
                 ->assertJsonPath('imported_count', 0);
    }

    /**
     * Test import CSV with valid data
     */
    public function test_import_csv_with_valid_data()
    {
        $csvContent = "question_text,category,question_type,options,correct_answer,points,answer_key\n";
        $csvContent .= "\"What is 2+2?\",Mathematics,mcq,\"[\"\"3\"\",\"\"4\"\",\"\"5\"\"]\",4,5,\n";

        $tempPath = self::createTempFile($csvContent);
        $file = new UploadedFile(
            $tempPath,
            'questions.csv',
            'text/csv',
            null,
            true
        );

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/questions/import/csv', [
                'file' => $file,
            ]);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'success');
    }

    /**
     * Test import CSV rejects invalid file
     */
    public function test_import_csv_rejects_invalid_file()
    {
        $tempPath = self::createTempFile('not a valid csv');
        $file = new UploadedFile(
            $tempPath,
            'invalid.txt',
            'text/plain',
            null,
            true
        );

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/questions/import/csv', [
                'file' => $file,
            ]);

        $response->assertStatus(422);
    }

    /**
     * Test import CSV detects duplicate questions
     */
    public function test_import_csv_detects_duplicates()
    {
        // Create existing question
        Question::factory()->create([
            'category_id' => $this->category->id,
            'question_text' => 'What is 2+2?',
            'question_type' => 'mcq',
        ]);

        $csvContent = "question_text,category,question_type,options,correct_answer,points,answer_key\n";
        $csvContent .= "\"What is 2+2?\",Mathematics,mcq,\"[\"\"3\"\",\"\"4\"\",\"\"5\"\"]\",4,5,\n";

        $tempPath = self::createTempFile($csvContent);
        $file = new UploadedFile(
            $tempPath,
            'questions.csv',
            'text/csv',
            null,
            true
        );

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/questions/import/csv', [
                'file' => $file,
            ]);

        $response->assertStatus(200)
                 ->assertJsonPath('imported_count', 0);
    }

    /**
     * Test import short answer question
     */
    public function test_import_short_answer_question()
    {
        $payload = [
            'questions' => [
                [
                    'question_text' => 'What is the capital of France?',
                    'category' => 'Mathematics',
                    'question_type' => 'short_answer',
                    'options' => [],
                    'correct_answer' => null,
                    'points' => 5,
                    'answer_key' => 'Paris',
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/questions/import/json', $payload);

        $response->assertStatus(200)
                 ->assertJsonPath('imported_count', 1);

        $this->assertDatabaseHas('questions', [
            'question_text' => 'What is the capital of France?',
            'question_type' => 'short_answer',
            'answer_key' => 'Paris',
        ]);
    }

    /**
     * Test import short answer requires answer_key
     */
    public function test_import_short_answer_requires_answer_key()
    {
        $payload = [
            'questions' => [
                [
                    'question_text' => 'What is the capital of France?',
                    'category' => 'Mathematics',
                    'question_type' => 'short_answer',
                    'options' => [],
                    'correct_answer' => null,
                    'points' => 5,
                    'answer_key' => '', // Empty answer key
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/questions/import/json', $payload);

        $response->assertStatus(200)
                 ->assertJsonPath('imported_count', 0);
    }

    /**
     * Test import MCQ requires minimum 2 options
     */
    public function test_import_mcq_requires_minimum_options()
    {
        $payload = [
            'questions' => [
                [
                    'question_text' => 'Test?',
                    'category' => 'Mathematics',
                    'question_type' => 'mcq',
                    'options' => ['A'], // Only 1 option
                    'correct_answer' => 'A',
                    'points' => 5,
                    'answer_key' => null,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/questions/import/json', $payload);

        $response->assertStatus(200)
                 ->assertJsonPath('imported_count', 0);
    }

    /**
     * Test import true/false question
     */
    public function test_import_true_false_question()
    {
        $payload = [
            'questions' => [
                [
                    'question_text' => 'Is Paris the capital of France?',
                    'category' => 'Mathematics',
                    'question_type' => 'tf',
                    'options' => [],
                    'correct_answer' => 'True',
                    'points' => 5,
                    'answer_key' => null,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/questions/import/json', $payload);

        $response->assertStatus(200)
                 ->assertJsonPath('imported_count', 1);
    }

    /**
     * Test import fails with file size limit
     */
    public function test_import_file_exceeds_size_limit()
    {
        $largeContent = str_repeat('x', 6000000); // 6MB

        $tempPath = self::createTempFile($largeContent);
        $file = new UploadedFile(
            $tempPath,
            'large.csv',
            'text/csv',
            null,
            true
        );

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/questions/import/csv', [
                'file' => $file,
            ]);

        $response->assertStatus(422);
    }

    /**
     * Helper to create temporary file
     */
    private static function createTempFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($path, $content);
        return $path;
    }
}
