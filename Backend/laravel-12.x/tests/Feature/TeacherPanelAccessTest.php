<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TeacherPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_access_filament_panel(): void
    {
        $teacher = User::factory()->teacher()->create();
        $panel = Mockery::mock(Panel::class);

        $this->assertTrue($teacher->canAccessPanel($panel));
    }

    public function test_student_cannot_access_filament_panel(): void
    {
        $student = User::factory()->student()->create();
        $panel = Mockery::mock(Panel::class);

        $this->assertFalse($student->canAccessPanel($panel));
    }
}
