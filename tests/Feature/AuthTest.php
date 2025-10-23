<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
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

    /**
     * Test admin can register staff member.
     * Verifies role-based access control
     */
    public function test_admin_can_register_staff(): void
    {
        // Create admin user
        $admin = User::factory()->create([
            'role' => 'admin',
            'client_id' => 0
        ]);

        $staffData = [
            'name' => 'Jane Staff',
            'email' => 'staff@acme.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->actingAs($admin)
                         ->postJson('/api/auth/register-staff', $staffData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'staff' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'client_id'
                    ],
                    'status_code'
                ]);

        // Verify staff was created with correct client_id
        $this->assertDatabaseHas('users', [
            'name' => 'Jane Staff',
            'email' => 'staff@acme.com',
            'role' => 'staff',
            'client_id' => $admin->id
        ]);
    }

    /**
     * Test staff cannot register other staff.
     * Verifies access control security
     */
    public function test_staff_cannot_register_staff(): void
    {
        // Create staff user
        $staff = User::factory()->create([
            'role' => 'staff',
            'client_id' => 1
        ]);

        $staffData = [
            'name' => 'Another Staff',
            'email' => 'another@acme.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->actingAs($staff)
                         ->postJson('/api/auth/register-staff', $staffData);

        $response->assertStatus(403)
                ->assertJson([
                    'message' => 'Forbidden. Admin access required.',
                    'status_code' => 403
                ]);
    }
}