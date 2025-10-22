<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\RegisterStaffRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

/**
 * Authentication Controller
 * Handles HTTP requests for authentication operations
 * Following Single Responsibility Principle: only handles HTTP concerns
 * Following Dependency Inversion: depends on Service abstraction
 */
class AuthController extends Controller
{
    /**
     * Constructor with dependency injection.
     * Following Dependency Inversion Principle: depends on abstraction
     *
     * @param AuthService $authService
     */
    public function __construct(
        private AuthService $authService
    ) {
        // Service is injected automatically by Laravel's container
    }
    /**
     * Register a new user.
     * Delegates business logic to Service layer
     * Following Single Responsibility: only handles HTTP request/response
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            // Delegate business logic to service layer
            $result = $this->authService->registerUser($request);

            return response()->json([
                'message' => 'User registered successfully',
                'user' => $result['user'],
                'token' => $result['token'],
                'token_type' => $result['token_type'],
                'status_code' => 201,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed',
                'error' => 'An error occurred during registration',
                'status_code' => 500,
            ], 500);
        }
    }

    /**
     * Authenticate a user and return API token.
     * Delegates business logic to Service layer
     * Following Single Responsibility: only handles HTTP request/response
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // Delegate business logic to service layer
        $result = $this->authService->authenticateUser($request->validated());

        if (!$result) {
            return response()->json([
                'message' => 'Invalid credentials',
                'status_code' => 401,
            ], 401);
        }

        return response()->json([
            'message' => 'Login successful',
            'user' => $result['user'],
            'token' => $result['token'],
            'token_type' => $result['token_type'],
            'status_code' => 200,
        ], 200);
    }

    /**
     * Logout the authenticated user.
     * Delegates business logic to Service layer
     * Following Single Responsibility: only handles HTTP request/response
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        // Delegate business logic to service layer
        $success = $this->authService->logoutUser();

        if (!$success) {
            return response()->json([
                'message' => 'Logout failed',
                'error' => 'An error occurred during logout',
                'status_code' => 500,
            ], 500);
        }

        return response()->json([
            'message' => 'Logged out successfully',
            'status_code' => 200,
        ], 200);
    }

    /**
     * Get the authenticated user's profile.
     * Delegates business logic to Service layer
     * Following Single Responsibility: only handles HTTP request/response
     *
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        // Delegate business logic to service layer
        $user = $this->authService->getCurrentUser();

        return response()->json([
            'user' => $user,
            'status_code' => 200,
        ], 200);
    }

    /**
     * Register a new staff member.
     * Delegates business logic to Service layer
     * Following Single Responsibility: only handles HTTP request/response
     *
     * @param RegisterStaffRequest $request
     * @return JsonResponse
     */
    public function registerStaff(RegisterStaffRequest $request): JsonResponse
    {
        try {
            // Delegate business logic to service layer
            $staff = $this->authService->registerStaff($request);

            return response()->json([
                'message' => 'Staff member registered successfully',
                'staff' => $staff,
                'status_code' => 201,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to register staff member',
                'error' => 'An error occurred while registering the staff member',
                'status_code' => 500,
            ], 500);
        }
    }
}
