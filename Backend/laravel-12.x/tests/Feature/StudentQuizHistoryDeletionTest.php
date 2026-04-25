<?php

namespace Tests\Feature;

use App\Models\Quiz_attempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentQuizHistoryDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_with_quiz_attempts_cannot_be_deleted(): void
    {
        $student = User::factory()->student()->create();
        Quiz_attempt::factory()->create(['student_id' => $student->id]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('This student cannot be deleted because they already have quiz records.');

        $student->delete();
    }

    public function test_student_without_quiz_attempts_can_be_deleted(): void
    {
        $student = User::factory()->student()->create();

        $student->delete();

        $this->assertDatabaseMissing('users', [
            'id' => $student->id,
        ]);
    }

    public function test_admin_api_blocks_deletion_of_student_with_quiz_attempts(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        Quiz_attempt::factory()->create(['student_id' => $student->id]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/admin/users/{$student->id}");

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'This student cannot be deleted because they already have quiz records.',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
        ]);
    }

    public function test_admin_api_allows_deletion_of_student_without_quiz_attempts(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/admin/users/{$student->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'User deleted successfully',
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $student->id,
        ]);
    }

    public function test_admin_can_deactivate_student_via_api(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create(['is_active' => true]);

        Sanctum::actingAs($admin);

        $response = $this->patchJson("/api/admin/users/{$student->id}/deactivate");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'User deactivated successfully',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_activate_student_via_api(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create(['is_active' => false]);

        Sanctum::actingAs($admin);

        $response = $this->patchJson("/api/admin/users/{$student->id}/activate");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'User activated successfully',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'is_active' => true,
        ]);
    }
}

