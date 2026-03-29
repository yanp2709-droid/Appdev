<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProtectedUserDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_protected_user_cannot_be_deleted(): void
    {
        $user = User::factory()->admin()->protected()->create();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Protected users cannot be deleted.');

        $user->delete();
    }

    public function test_last_admin_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The last admin account cannot be deleted.');

        $admin->delete();
    }

    public function test_admin_can_be_deleted_when_another_admin_exists(): void
    {
        $adminToDelete = User::factory()->admin()->create();
        User::factory()->admin()->create();

        $adminToDelete->delete();

        $this->assertDatabaseMissing('users', [
            'id' => $adminToDelete->id,
        ]);
    }
}
