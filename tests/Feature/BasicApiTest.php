<?php

namespace Tests\Feature;

use Tests\TestCase;

class BasicApiTest extends TestCase
{
    /**
     * Test API returns JSON responses.
     * Verifies basic API functionality
     */
    public function test_api_returns_json_responses(): void
    {
        // Test that API endpoints return JSON
        $response = $this->getJson('/api/auth/me');
        
        // Should return 401 (unauthenticated) but with JSON format
        $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Unauthenticated.',
                    'status_code' => 401
                ]);
    }

    /**
     * Test registration endpoint structure.
     * Verifies endpoint exists and returns proper structure
     */
    public function test_registration_endpoint_exists(): void
    {
        $userData = [
            'name' => 'Test User',
            'company_name' => 'Test Corp',
            'company_email' => 'contact' . time() . '@testcorp.com',
            'email' => 'test' . time() . '@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        // Should succeed (201) since database is properly set up
        $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'user',
                    'token',
                    'token_type',
                    'status_code'
                ]);
    }

    /**
     * Test login endpoint structure.
     * Verifies login endpoint exists
     */
    public function test_login_endpoint_exists(): void
    {
        $loginData = [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        // Should return 401 with proper JSON structure
        $response->assertStatus(401)
                ->assertJsonStructure([
                    'message',
                    'status_code'
                ]);
    }

    /**
     * Test orders endpoint requires authentication.
     * Verifies authentication middleware works
     */
    public function test_orders_endpoint_requires_auth(): void
    {
        $response = $this->getJson('/api/orders');

        $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Unauthenticated.',
                    'status_code' => 401
                ]);
    }

    /**
     * Test admin middleware works.
     * Verifies role-based access control
     */
    public function test_admin_endpoint_requires_admin(): void
    {
        $staffData = [
            'name' => 'Test Staff',
            'email' => 'staff@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register-staff', $staffData);

        // Should return 401 (unauthenticated) or 403 (not admin)
        $this->assertContains($response->status(), [401, 403]);
        $response->assertJsonStructure([
            'message',
            'status_code'
        ]);
    }
}
