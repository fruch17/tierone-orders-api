# AuthService Implementation

## Overview

The TierOne Orders API now includes a dedicated **AuthService** that handles all authentication business logic, following the same architectural pattern as OrderService. This refactoring improves code organization, testability, and maintainability.

## Architecture Changes

### Before (Inconsistent)
```php
// ❌ AuthController had business logic mixed with HTTP concerns
class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        // Business logic directly in controller
        $user = User::create([...]);
        $token = $user->createToken('auth_token')->plainTextToken;
        // ...
    }
}

// ✅ OrderController properly used Service layer
class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}
    
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->createOrder($request);
        // ...
    }
}
```

### After (Consistent)
```php
// ✅ AuthController now follows same pattern as OrderController
class AuthController extends Controller
{
    public function __construct(private AuthService $authService) {}
    
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->registerUser($request);
        // ...
    }
}
```

## AuthService Methods

### 1. `registerUser(RegisterRequest $request): array`
- **Purpose**: Register a new admin user
- **Returns**: Array with user data and token
- **Business Logic**: User creation, password hashing, token generation

### 2. `registerStaff(RegisterStaffRequest $request): UserResource`
- **Purpose**: Register a new staff member (admin only)
- **Returns**: UserResource of created staff
- **Business Logic**: Staff creation with proper client_id assignment

### 3. `authenticateUser(array $credentials): ?array`
- **Purpose**: Authenticate user and generate token
- **Returns**: Array with user data and token, or null if failed
- **Business Logic**: Credential validation, token generation, cleanup

### 4. `logoutUser(): bool`
- **Purpose**: Logout authenticated user
- **Returns**: Success status
- **Business Logic**: Token revocation

### 5. `getCurrentUser(): UserResource`
- **Purpose**: Get current authenticated user
- **Returns**: UserResource of current user
- **Business Logic**: User data formatting

## Benefits of Refactoring

### 1. **Consistency**
- All controllers now follow the same Service pattern
- Uniform architecture across the application

### 2. **Single Responsibility Principle**
- **AuthController**: Only handles HTTP request/response
- **AuthService**: Only handles authentication business logic

### 3. **Testability**
```php
// Easy to test business logic separately
class AuthServiceTest extends TestCase
{
    public function test_register_user_creates_correctly()
    {
        $request = new RegisterRequest();
        $result = $this->authService->registerUser($request);
        
        $this->assertInstanceOf(UserResource::class, $result['user']);
        $this->assertNotEmpty($result['token']);
    }
}
```

### 4. **Reusability**
```php
// AuthService can be used from multiple places
class ConsoleCommand extends Command
{
    public function handle(AuthService $authService)
    {
        // Can use AuthService in console commands
        $user = $authService->getCurrentUser();
    }
}
```

### 5. **Maintainability**
- Changes to authentication logic only affect AuthService
- Controller remains stable for HTTP concerns
- Easy to add new authentication features

## Code Examples

### AuthController (After Refactoring)
```php
class AuthController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
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

    public function login(LoginRequest $request): JsonResponse
    {
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
}
```

### AuthService Implementation
```php
class AuthService
{
    public function registerUser(RegisterRequest $request): array
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'company_name' => $validated['company_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'] ?? 'admin',
            'client_id' => $validated['client_id'] ?? 0,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer'
        ];
    }

    public function authenticateUser(array $credentials): ?array
    {
        if (!Auth::attempt($credentials)) {
            return null;
        }

        $user = Auth::user();
        $user->tokens()->delete(); // Security: revoke existing tokens
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer'
        ];
    }
}
```

## API Endpoints (Unchanged)

The API endpoints remain exactly the same:

- `POST /api/auth/register` - Register new admin user
- `POST /api/auth/login` - Authenticate user
- `POST /api/auth/logout` - Logout user
- `GET /api/auth/me` - Get current user
- `POST /api/auth/register-staff` - Register staff (admin only)

## Testing Benefits

### Before (Hard to Test)
```php
// Had to test HTTP concerns mixed with business logic
class AuthControllerTest extends TestCase
{
    public function test_register_creates_user()
    {
        // Complex setup with HTTP mocks
        // Hard to isolate business logic
    }
}
```

### After (Easy to Test)
```php
// Can test business logic separately
class AuthServiceTest extends TestCase
{
    public function test_register_user_creates_correctly()
    {
        // Clean, focused tests
        // No HTTP dependencies
    }
}

// Can test HTTP concerns separately
class AuthControllerTest extends TestCase
{
    public function test_register_returns_correct_response()
    {
        // Mock AuthService
        // Test only HTTP response format
    }
}
```

## SOLID Principles Compliance

### Single Responsibility Principle ✅
- **AuthController**: HTTP request/response handling
- **AuthService**: Authentication business logic

### Open/Closed Principle ✅
- Easy to extend AuthService with new methods
- Controller remains closed for modification

### Liskov Substitution Principle ✅
- AuthService can be easily mocked for testing
- Interface remains consistent

### Interface Segregation Principle ✅
- AuthService has focused, specific methods
- No unnecessary dependencies

### Dependency Inversion Principle ✅
- Controller depends on AuthService abstraction
- Easy to swap implementations

## Summary

This refactoring brings the authentication system in line with the rest of the application architecture, providing:

- **Consistency** across all controllers
- **Better testability** with separated concerns
- **Improved maintainability** with clear responsibilities
- **Enhanced reusability** of authentication logic
- **SOLID compliance** throughout the codebase

The API remains fully functional with the same endpoints and responses, but now with a much cleaner and more maintainable architecture! 🚀
