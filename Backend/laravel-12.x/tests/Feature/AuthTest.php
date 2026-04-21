<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user can login with valid credentials.
     */
    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => ['token']
                 ]);
    }

    /**
     * Test user can logout.
     */
    public function test_user_can_logout()
    {
        $user = User::factory()->create();

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader(
            'Authorization',
            "Bearer $token"
        )->postJson('/api/logout');

        $response->assertStatus(200);
    }

    /**
     * Test user can change password.
     */
    public function test_user_can_change_password()
    {
        $user = User::factory()->create([
            'password' => bcrypt('oldpassword')
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader(
            'Authorization',
            "Bearer $token"
        )->postJson('/api/auth/change-password', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Password updated successfully'
                 ]);

        // Verify password was actually changed
        $user->refresh();
        $this->assertTrue(password_verify('newpassword123', $user->password));
    }

    /**
     * Test change password fails with wrong current password.
     */
    public function test_change_password_fails_with_wrong_current_password()
    {
        $user = User::factory()->create([
            'password' => bcrypt('oldpassword')
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader(
            'Authorization',
            "Bearer $token"
        )->postJson('/api/auth/change-password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'error' => [
                         'code' => 'invalid_current_password',
                         'message' => 'Current password is incorrect'
                     ]
                 ]);
    }
}
