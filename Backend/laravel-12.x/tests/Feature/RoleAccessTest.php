<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test student cannot access admin routes.
     */
    public function test_student_cannot_access_admin_routes()
    {
        $student = User::factory()->create([
            'role' => 'student'
        ]);

        $response = $this->actingAs($student, 'sanctum')
            ->postJson('/api/admin/categories', [
                'name' => 'Test'
            ]);

        $response->assertStatus(403);
    }
}
