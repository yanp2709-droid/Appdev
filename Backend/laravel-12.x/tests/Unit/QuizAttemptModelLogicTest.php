<?php

namespace Tests\Unit;

use App\Models\Quiz_attempt;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizAttemptModelLogicTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_detects_when_attempt_is_expired(): void
    {
        Carbon::setTestNow('2026-04-02 10:00:00');

        $attempt = new Quiz_attempt([
            'status' => 'in_progress',
            'expires_at' => Carbon::parse('2026-04-02 09:59:59'),
        ]);

        $this->assertTrue($attempt->isExpired());
        $this->assertFalse($attempt->isActive());
    }

    public function test_it_reports_active_attempt_when_not_submitted_and_not_expired(): void
    {
        Carbon::setTestNow('2026-04-02 10:00:00');

        $attempt = new Quiz_attempt([
            'status' => 'in_progress',
            'expires_at' => Carbon::parse('2026-04-02 10:05:00'),
        ]);

        $this->assertFalse($attempt->isExpired());
        $this->assertTrue($attempt->isActive());
    }

    public function test_it_returns_remaining_seconds_until_expiration(): void
    {
        Carbon::setTestNow('2026-04-02 10:00:00');

        $attempt = new Quiz_attempt([
            'expires_at' => Carbon::parse('2026-04-02 10:02:30'),
        ]);

        $this->assertSame(150, $attempt->getRemainingSeconds());
    }

    public function test_it_returns_attempt_duration_in_minutes(): void
    {
        $attempt = new Quiz_attempt([
            'started_at' => Carbon::parse('2026-04-02 10:00:00'),
            'expires_at' => Carbon::parse('2026-04-02 10:15:00'),
        ]);

        $this->assertSame(15, $attempt->getDurationMinutes());
    }
}

