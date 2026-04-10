<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\Quiz_attempt;
use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStatisticsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function authenticate()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        return $this->actingAs($admin, 'sanctum');
    }

    /** @test */
    public function admin_can_access_dashboard_statistics()
    {
        $this->authenticate();

        $response = $this->getJson('/api/admin/statistics/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'statistics' => [
                        'total_students',
                        'total_attempts',
                        'submitted_attempts',
                        'average_score',
                    ]
                ]
            ]);
    }

    /** @test */
    public function non_admin_cannot_access_statistics_endpoints()
    {
        $student = User::factory()->create(['role' => 'student']);
        $this->actingAs($student, 'sanctum');

        $response = $this->getJson('/api/admin/statistics/dashboard');

        // Should be forbidden or unauthorized
        $this->assertContains($response->status(), [401, 403]);
    }

    /** @test */
    public function admin_can_get_all_students_with_statistics()
    {
        $this->authenticate();

        // Create test data
        $students = User::factory()->count(3)->create(['role' => 'student']);
        $category = Category::factory()->create();
        $quiz = Quiz::factory()->create(['category_id' => $category->id]);

        foreach ($students as $student) {
            Quiz_attempt::factory()->create([
                'student_id' => $student->id,
                'quiz_id' => $quiz->id,
                'status' => 'submitted',
                'score_percent' => fake()->numberBetween(50, 100),
            ]);
        }

        $response = $this->getJson('/api/admin/statistics/students');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'students' => [
                        '*' => [
                            'id',
                            'name',
                            'email',
                            'total_attempts',
                            'submitted_attempts',
                            'average_score',
                        ]
                    ],
                    'pagination' => [
                        'total',
                        'per_page',
                        'current_page',
                        'last_page',
                    ]
                ]
            ]);

        $this->assertEquals(3, $response->json('data.pagination.total'));
    }

    /** @test */
    public function admin_can_get_specific_student_statistics()
    {
        $this->authenticate();

        $student = User::factory()->create(['role' => 'student']);
        $category = Category::factory()->create();
        $quiz = Quiz::factory()->create(['category_id' => $category->id]);

        Quiz_attempt::factory()->count(3)->create([
            'student_id' => $student->id,
            'quiz_id' => $quiz->id,
            'status' => 'submitted',
        ]);

        $response = $this->getJson("/api/admin/statistics/student/{$student->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'student' => [
                        'id',
                        'name',
                        'email',
                        'total_attempts',
                        'submitted_attempts',
                        'average_score',
                        'highest_score',
                        'lowest_score',
                    ]
                ]
            ]);
    }

    /** @test */
    public function admin_can_filter_attempts_by_status()
    {
        $this->authenticate();

        $category = Category::factory()->create();
        $quiz = Quiz::factory()->create(['category_id' => $category->id]);
        $student = User::factory()->create(['role' => 'student']);

        Quiz_attempt::factory()->create([
            'student_id' => $student->id,
            'quiz_id' => $quiz->id,
            'status' => 'submitted',
        ]);

        Quiz_attempt::factory()->create([
            'student_id' => $student->id,
            'quiz_id' => $quiz->id,
            'status' => 'in_progress',
        ]);

        $response = $this->getJson('/api/admin/statistics/attempts?status=submitted');

        $response->assertStatus(200);
        $attempts = $response->json('data.attempts');
        
        $this->assertTrue(collect($attempts)->every(fn($attempt) => $attempt['status'] === 'submitted'));
    }

    /** @test */
    public function admin_can_get_category_statistics()
    {
        $this->authenticate();

        $category1 = Category::factory()->create(['name' => 'Programming']);
        $category2 = Category::factory()->create(['name' => 'Database']);

        $quiz1 = Quiz::factory()->create(['category_id' => $category1->id]);
        $quiz2 = Quiz::factory()->create(['category_id' => $category2->id]);

        $student = User::factory()->create(['role' => 'student']);

        Quiz_attempt::factory()->create([
            'student_id' => $student->id,
            'quiz_id' => $quiz1->id,
            'status' => 'submitted',
            'score_percent' => 85.00,
        ]);

        Quiz_attempt::factory()->create([
            'student_id' => $student->id,
            'quiz_id' => $quiz2->id,
            'status' => 'submitted',
            'score_percent' => 75.00,
        ]);

        $response = $this->getJson('/api/admin/statistics/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'statistics' => [
                        '*' => [
                            'category_id',
                            'category_name',
                            'total_attempts',
                            'average_score',
                            'highest_score',
                            'lowest_score',
                        ]
                    ]
                ]
            ]);

        $this->assertCount(2, $response->json('data.statistics'));
    }

    /** @test */
    public function statistics_returns_numeric_values()
    {
        $this->authenticate();

        $category = Category::factory()->create();
        $quiz = Quiz::factory()->create(['category_id' => $category->id]);
        $student = User::factory()->create(['role' => 'student']);

        Quiz_attempt::factory()->create([
            'student_id' => $student->id,
            'quiz_id' => $quiz->id,
            'status' => 'submitted',
            'score_percent' => 87.50,
        ]);

        $response = $this->getJson('/api/admin/statistics/dashboard');

        $stats = $response->json('data.statistics');
        
        // Verify all values are numeric
        $this->assertIsNumeric($stats['average_score']);
        $this->assertIsNumeric($stats['total_students']);
        $this->assertIsNumeric($stats['total_attempts']);
    }

    /** @test */
    public function empty_statistics_do_not_cause_errors()
    {
        $this->authenticate();

        // No quiz attempts created
        $response = $this->getJson('/api/admin/statistics/dashboard');

        $response->assertStatus(200);
        
        $stats = $response->json('data.statistics');
        $this->assertEquals(0, $stats['average_score']);
        $this->assertEquals(0, $stats['total_attempts']);
    }
}
