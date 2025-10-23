<?php

namespace App\Services;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\RegisterStaffRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\ClientResource;
use App\Models\User;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Auth Service
 * Handles all business logic related to user authentication and registration
 * Following Single Responsibility Principle: only handles authentication operations
 * Following Dependency Inversion: depends on abstractions (models, resources)
 */
class AuthService
{
    /**
     * Register a new user (admin).
     * Handles user creation and token generation
     * Following Single Responsibility: one method, one purpose
     *
     * @param RegisterRequest $request
     * @return array
     */
    public function registerUser(RegisterRequest $request): array
    {
        $validated = $request->validated();

        // Create client first
        $client = Client::create([
            'company_name' => $validated['company_name'],
            'company_email' => $validated['company_email'],
        ]);

        // Create new user (admin) and link to client
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'admin',
            'client_id' => $client->id, // Link user to client
        ]);

        // Generate API token for immediate authentication
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => new UserResource($user),
            'client' => new ClientResource($client),
            'token' => $token,
            'token_type' => 'Bearer'
        ];
    }

    /**
     * Register a new staff member.
     * Only admin users can register staff
     * Following Single Responsibility: one method, one purpose
     *
     * @param RegisterStaffRequest $request
     * @return UserResource
     */
    public function registerStaff(RegisterStaffRequest $request): UserResource
    {
        $validatedData = $request->validated();

        // Create staff user with role 'staff'
        $staff = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'role' => 'staff', // Staff role assigned
            'client_id' => auth()->user()->client_id, // Staff belongs to the same client as admin
        ]);

        return new UserResource($staff);
    }

    /**
     * Authenticate a user and generate token.
     * Handles login logic and token generation
     * Following Single Responsibility: one method, one purpose
     *
     * @param array $credentials
     * @return array|null
     */
    public function authenticateUser(array $credentials): ?array
    {
        // Attempt authentication
        if (!Auth::attempt($credentials)) {
            return null;
        }

        // Get authenticated user
        $user = Auth::user();

        // Revoke existing tokens to ensure only one active token per device/session (Security best practice)
        $user->tokens()->delete();

        // Generate a new Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer'
        ];
    }

    /**
     * Logout the authenticated user.
     * Revokes the current access token
     * Following Single Responsibility: one method, one purpose
     *
     * @return bool
     */
    public function logoutUser(): bool
    {
        try {
            // Revoke the token that was used to authenticate the current request
            auth()->user()->currentAccessToken()->delete();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the authenticated user's profile.
     * Returns current user information
     * Following Single Responsibility: one method, one purpose
     *
     * @return UserResource
     */
    public function getCurrentUser(): UserResource
    {
        return new UserResource(auth()->user());
    }
}
