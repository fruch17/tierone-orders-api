<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SimpleAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user can register successfully.
     * Verifies the complete registration flow
     */
    public function test_user_can_register(): void
    {
        $userData = [
            'name' => 'John Doe',
            'company_name' => 'ACME Corp',
            'email' => 'john@acme.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'user' => [
                        'id',
                        'name',
                        'company_name',
                        'email',
                        'role',
                        'client_id'
                    ],
                    'token',
                    'token_type',
                    'status_code'
                ]);

        // Verify user was created in database
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@acme.com',
            'role' => 'admin',
            'client_id' => 0
        ]);
    }

    /**
     * Test user can login with valid credentials.
     * Verifies authentication flow and token generation
     */
    public function test_user_can_login(): void
    {
        // Create a user first
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'client_id' => 0
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role'
                    ],
                    'token',
                    'token_type',
                    'status_code'
                ]);

        $this->assertEquals('Login successful', $response->json('message'));
    }

    /**
     * Test user cannot login with invalid credentials.
     * Verifies security validation
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $loginData = [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Invalid credentials',
                    'status_code' => 401
                ]);
    }
}
