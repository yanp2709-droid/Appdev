<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\Quiz_attempt;
use App\Models\User;
use App\Models\Category;
use App\Services\QuizStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizStatisticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected QuizStatisticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QuizStatisticsService();
    }

    /** @test */
    public function it_returns_zero_statistics_for_empty_database()
    {
        $stats = $this->service->getOverallStatistics();

        $this->assertEquals(0, $stats['total_students']);
        $this->assertEquals(0, $stats['total_attempts']);
        $this->assertEquals(0, $stats['submitted_attempts']);
        $this->assertEquals(0, $stats['average_score']);
    }

    /** @test */
    public function it_calculates_overall_statistics_correctly()
    {
        // Create test data
        $students = User::factory()->count(3)->create(['role' => 'student']);
        $category = Category::factory()->create();
        $quiz = Quiz::factory()->create(['category_id' => $category->id]);

        // Create quiz attempts with various scores
        Quiz_attempt::factory()->create([
            'student_id' => $students[0]->id,
            'quiz_id' => $quiz->id,
            'status' => 'submitted',
            'score_percent' => 80.00,
        ]);

        Quiz_attempt::factory()->create([
            'student_id' => $students[1]->id,
            'quiz_id' => $quiz->id,
            'status' => 'submitted',
            'score_percent' => 90.00,
        ]);

        Quiz_attempt::factory()->create([
            'student_id' => $students[2]->id,
            'quiz_id' => $quiz->id,
            'status' => 'submitted',
            'score_percent' => 70.00,
        ]);

        // Add an in-progress attempt (should not count)
        Quiz_attempt::factory()->create([
            'student_id' => $students[0]->id,
            'quiz_id' => $quiz->id,
            'status' => 'in_progress',
            'score_percent' => 0,
        ]);

        $stats = $this->service->getOverallStatistics();

        $this->assertEquals(3, $stats['total_students']);
        $this->assertEquals(4, $stats['total_attempts']);
        $this->assertEquals(3, $stats['submitted_attempts']);
        $this->assertEquals(1, $stats['in_progress_attempts']);
        $this->assertEquals(80.00, $stats['average_score']); // (80 + 90 + 70) / 3 = 80
        $this->assertEquals(90.00, $stats['highest_score']);
        $this->assertEquals(70.00, $stats['lowest_score']);
        $this->assertEquals(75.00, $stats['completion_rate']); // 3 / 4 * 100
    }

    /** @test */
    public function it_calculates_student_statistics_correctly()
    {
        $student = User::factory()->create(['role' => 'student']);
        $category = Category::factory()->create();
        $quiz = Quiz::factory()->create(['category_id' => $category->id]);

        // Create attempts for the student
        Quiz_attempt::factory()->create([
            'student_id' => $student->id,
            'quiz_id' => $quiz->id,
            'status' => 'submitted',
            'score_percent' => 85.00,
        ]);

        Quiz_attempt::factory()->create([
            'student_id' => $student->id,
            'quiz_id' => $quiz->id,
            'status' => 'submitted',
            'score_percent' => 95.00,
        ]);

        Quiz_attempt::factory()->create([
            'student_id' => $student->id,
            'quiz_id' => $quiz->id,
            'status' => 'in_progress',
            'score_percent' => 0,
        ]);

        $stats = $this->service->getStudentStatistics($student->id);

        $this->assertEquals($student->id, $stats['student_id']);
        $this->assertEquals($student->name, $stats['student_name']);
        $this->assertEquals(3, $stats['total_attempts']);
        $this->assertEquals(2, $stats['submitted_attempts']);
        $this->assertEquals(1, $stats['in_progress_attempts']);
        $this->assertEquals(90.00, $stats['average_score']); // (85 + 95) / 2
        $this->assertEquals(95.00, $stats['highest_score']);
        $this->assertEquals(85.00, $stats['lowest_score']);
    }

    /** @test */
    public function it_returns_empty_array_for_invalid_student()
    {
        $stats = $this->service->getStudentStatistics(999);

        $this->assertEmpty($stats);
    }

    /** @test */
    public function it_returns_empty_array_for_non_student_user()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $stats = $this->service->getStudentStatistics($admin->id);

        $this->assertEmpty($stats);
    }

    /** @test */
    public function it_calculates_category_statistics_correctly()
    {
        $category1 = Category::factory()->create(['name' => 'Programming']);
        $category2 = Category::factory()->create(['name' => 'Database']);

        $quiz1 = Quiz::factory()->create(['category_id' => $category1->id]);
        $quiz2 = Quiz::factory()->create(['category_id' => $category2->id]);

        $student = User::factory()->create(['role' => 'student']);

        // Programming category attempts
        Quiz_attempt::factory()->create([
            'student_id' => $student->id,
            'quiz_id' => $quiz1->id,
            'status' => 'submitted',
            'score_percent' => 80.00,
        ]);

        Quiz_attempt::factory()->create([
            'student_id' => $student->id,
            'quiz_id' => $quiz1->id,
            'status' => 'submitted',
            'score_percent' => 90.00,
        ]);

        // Database category attempts
        Quiz_attempt::factory()->create([
            'student_id' => $student->id,
            'quiz_id' => $quiz2->id,
            'status' => 'submitted',
            'score_percent' => 75.00,
        ]);

        $stats = $this->service->getCategoryStatistics();

        $this->assertCount(2, $stats);

        // Check Programming category (should have highest average)
        $programmingStats = collect($stats)->firstWhere('category_id', $category1->id);
        $this->assertEquals('Programming', $programmingStats['category_name']);
        $this->assertEquals(2, $programmingStats['total_attempts']);
        $this->assertEquals(85.00, $programmingStats['average_score']);

        // Check Database category
        $databaseStats = collect($stats)->firstWhere('category_id', $category2->id);
        $this->assertEquals('Database', $databaseStats['category_name']);
        $this->assertEquals(1, $databaseStats['total_attempts']);
        $this->assertEquals(75.00, $databaseStats['average_score']);
    }

    /** @test */
    public function it_calculates_performance_distribution_correctly()
    {
        $category = Category::factory()->create();
        $quiz = Quiz::factory()->create(['category_id' => $category->id]);
        $student = User::factory()->create(['role' => 'student']);

        // Create attempts in different grade ranges
        Quiz_attempt::factory()->create([
            'student_id' => $student->id,
            'quiz_id' => $quiz->id,
            'status' => 'submitted',
            'score_percent' => 95.00, // A
        ]);

        Quiz_attempt::factory()->create([
            'student_id' => $student->id,
            'quiz_id' => $quiz->id,
            'status' => 'submitted',
            'score_percent' => 85.00, // B
        ]);

        Quiz_attempt::factory()->create([
            'student_id' => $student->id,
            'quiz_id' => $quiz->id,
            'status' => 'submitted',
            'score_percent' => 72.00, // C
        ]);

        Quiz_attempt::factory()->create([
            'student_id' => $student->id,
            'quiz_id' => $quiz->id,
            'status' => 'submitted',
            'score_percent' => 65.00, // D
        ]);

        Quiz_attempt::factory()->create([
            'student_id' => $student->id,
            'quiz_id' => $quiz->id,
            'status' => 'submitted',
            'score_percent' => 45.00, // F
        ]);

        $distribution = $this->service->getPerformanceDistribution();

        $this->assertEquals(1, $distribution['A']['count']);
        $this->assertEquals(1, $distribution['B']['count']);
        $this->assertEquals(1, $distribution['C']['count']);
        $this->assertEquals(1, $distribution['D']['count']);
        $this->assertEquals(1, $distribution['F']['count']);
    }

    /** @test */
    public function it_calculates_difficulty_analysis_correctly()
    {
        $category = Category::factory()->create();
        
        $easyQuiz = Quiz::factory()->create([
            'category_id' => $category->id,
            'difficulty' => 'easy'
        ]);
        
        $hardQuiz = Quiz::factory()->create([
            'category_id' => $category->id,
            'difficulty' => 'hard'
        ]);
        
        $student = User::factory()->create(['role' => 'student']);

        // Easy quiz attempts
        Quiz_attempt::factory()->create([
            'student_id' => $student->id,
            'quiz_id' => $easyQuiz->id,
            'status' => 'submitted',
            'score_percent' => 95.00,
        ]);

        // Hard quiz attempts
        Quiz_attempt::factory()->create([
            'student_id' => $student->id,
            'quiz_id' => $hardQuiz->id,
            'status' => 'submitted',
            'score_percent' => 60.00,
        ]);

        $analysis = $this->service->getDifficultyAnalysis();

        $this->assertArrayHasKey('easy', $analysis);
        $this->assertArrayHasKey('hard', $analysis);
        
        // Easy quizzes should have higher average
        $this->assertTrue($analysis['easy']['average_score'] > $analysis['hard']['average_score']);
    }

    /** @test */
    public function it_handles_empty_categories_gracefully()
    {
        // No attempts created
        $stats = $this->service->getCategoryStatistics();

        $this->assertIsArray($stats);
        $this->assertEmpty($stats);
    }

    /** @test */
    public function it_handles_students_with_no_attempts()
    {
        $student = User::factory()->create(['role' => 'student']);

        $stats = $this->service->getStudentStatistics($student->id);

        $this->assertEquals(0, $stats['total_attempts']);
        $this->assertEquals(0, $stats['submitted_attempts']);
        $this->assertEquals(0, $stats['average_score']);
        $this->assertEquals(0, $stats['completion_rate']);
    }

    /** @test */
    public function it_filters_category_card_statistics_by_date_range()
    {
        $category = Category::factory()->create();
        $quiz = Quiz::factory()->create(['category_id' => $category->id]);
        $student = User::factory()->create(['role' => 'student']);

        // Create attempts on different dates
        Quiz_attempt::factory()->create([
            'student_id' => $student->id,
            'quiz_id' => $quiz->id,
            'status' => 'submitted',
            'score_percent' => 80.00,
            'created_at' => '2024-01-15 10:00:00', // Within range
        ]);

        Quiz_attempt::factory()->create([
            'student_id' => $student->id,
            'quiz_id' => $quiz->id,
            'status' => 'submitted',
            'score_percent' => 90.00,
            'created_at' => '2024-02-15 10:00:00', // Outside range
        ]);

        // Test with date range that includes only the first attempt
        $stats = $this->service->getCategoryCardStatistics('2024-01-01', '2024-01-31');

        $this->assertCount(1, $stats);
        $this->assertEquals(1, $stats[0]['total_attempts']);
        $this->assertEquals(80.00, $stats[0]['highest_score']);
    }

    /** @test */
    public function it_filters_category_detail_statistics_by_date_range()
    {
        $category = Category::factory()->create();
        $quiz = Quiz::factory()->create(['category_id' => $category->id]);
        $student = User::factory()->create(['role' => 'student']);

        // Create attempts on different dates
        Quiz_attempt::factory()->create([
            'student_id' => $student->id,
            'quiz_id' => $quiz->id,
            'status' => 'submitted',
            'score_percent' => 80.00,
            'created_at' => '2024-01-15 10:00:00', // Within range
        ]);

        Quiz_attempt::factory()->create([
            'student_id' => $student->id,
            'quiz_id' => $quiz->id,
            'status' => 'submitted',
            'score_percent' => 90.00,
            'created_at' => '2024-02-15 10:00:00', // Outside range
        ]);

        // Test with date range that includes only the first attempt
        $detail = $this->service->getCategoryDetailStatistics($category->id, '2024-01-01', '2024-01-31');

        $this->assertCount(1, $detail['users']);
        $this->assertEquals(1, $detail['users'][0]['total_attempts']);
        $this->assertEquals(80.00, $detail['users'][0]['best_score']);
    }
}
