<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Authentication Controller
 * Handles user registration, login, and logout
 * Following Single Responsibility Principle: only handles auth operations
 */
class AuthController extends Controller
{
    /**
     * Register a new client (user/tenant).
     * Creates a new user account and returns an API token
     * Following Dependency Injection: FormRequest validates input
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        // Validation already handled by RegisterRequest (SRP)
        $validated = $request->validated();

        // Create new user (client/tenant)
        $user = User::create([
            'name' => $validated['name'],
            'company_name' => $validated['company_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Generate API token for immediate authentication
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
            'status_code' => 201,
        ], 201);
    }

    /**
     * Authenticate a client and return API token.
     * Validates credentials and generates Sanctum token
     * Following Dependency Injection: FormRequest validates input
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // Validation already handled by LoginRequest (SRP)
        $credentials = $request->validated();

        // Attempt authentication
        if (!Auth::attempt($credentials)) {
        return response()->json([
            'message' => 'Invalid credentials',
            'status_code' => 401,
        ], 401);
        }

        // Get authenticated user
        $user = Auth::user();

        // Generate API token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
            'status_code' => 200,
        ], 200);
    }

    /**
     * Logout the authenticated user.
     * Revokes the current API token
     * Following Security best practices: explicit token revocation
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        // Revoke the token that was used to authenticate the current request
        auth()->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
            'status_code' => 200,
        ], 200);
    }

    /**
     * Get the authenticated user's profile.
     * Returns current user information
     * Useful for frontend to verify token validity
     *
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        return response()->json([
            'user' => new UserResource(auth()->user()),
            'status_code' => 200,
        ], 200);
    }
}
