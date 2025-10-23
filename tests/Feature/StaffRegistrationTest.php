<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test admin can register staff successfully.
     */
    public function test_admin_can_register_staff(): void
    {
        // Create a client first
        $client = Client::create([
            'company_name' => 'Test Company',
            'company_email' => 'contact@testcompany.com',
        ]);

        // Create an admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'client_id' => $client->id,
        ]);

        // Login as admin to get token
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('token');

        // Register staff using admin token
        $staffData = [
            'name' => 'Staff User',
            'email' => 'staff@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/register-staff', $staffData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'staff' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'client_id',
                        'created_at',
                        'updated_at',
                    ],
                    'status_code',
                ]);

        // Verify staff was created with correct client_id
        $this->assertDatabaseHas('users', [
            'name' => 'Staff User',
            'email' => 'staff@test.com',
            'role' => 'staff',
            'client_id' => $client->id, // Should be same as admin's client_id
        ]);

        // Verify staff user data
        $staffUser = User::where('email', 'staff@test.com')->first();
        $this->assertEquals($client->id, $staffUser->client_id);
        $this->assertEquals('staff', $staffUser->role);
    }
}
