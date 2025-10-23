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
- **Purpose**: Register a new admin user and create associated client
- **Returns**: Array with user data, client data, and token
- **Business Logic**: Client creation, user creation, password hashing, token generation
- **Multi-tenancy**: Creates both Client and User records, links them via client_id

### 2. `registerStaff(RegisterStaffRequest $request): UserResource`
- **Purpose**: Register a new staff member (admin only)
- **Returns**: UserResource of created staff
- **Business Logic**: Staff creation with proper client_id assignment from admin's client
- **Multi-tenancy**: Staff belongs to the same client as the admin who registers them

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

## Client-User Separation Architecture

### New Multi-Tenancy Model

The AuthService now implements a **client-user separation** model:

#### **Client Model**
- Represents companies/organizations
- Contains `company_name` and `company_email`
- Acts as the tenant boundary for multi-tenancy

#### **User Model**
- Represents individual people
- Contains personal `email` and `name`
- Belongs to a client via `client_id`
- Has roles: `admin` or `staff`

#### **Registration Process**
1. **Admin Registration**: Creates both Client and User records
2. **Staff Registration**: Creates User record linked to admin's client
3. **Data Isolation**: All data scoped by `client_id`

### Benefits of Client-User Separation

#### **1. Clear Data Boundaries**
```php
// Admin and staff share the same client
$admin = User::where('role', 'admin')->where('client_id', 1)->first();
$staff = User::where('role', 'staff')->where('client_id', 1)->first();

// Both can access orders for client_id = 1
$orders = Order::where('client_id', 1)->get();
```

#### **2. Scalable Multi-Tenancy**
- Each client is completely isolated
- Easy to add new clients without affecting existing ones
- Clear ownership of data and resources

#### **3. Role-Based Access Control**
- Admin: Full access to their client's data
- Staff: Limited access to their client's data
- Cross-client access is prevented by design

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
                'client' => $result['client'],
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

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => new UserResource($user),
            'client' => new ClientResource($client),
            'token' => $token,
            'token_type' => 'Bearer'
        ];
    }

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
- **Client-User Separation** for proper multi-tenancy
- **Scalable Architecture** supporting multiple clients and users

### Key Architectural Improvements

1. **Service Layer Pattern**: AuthService handles all business logic
2. **Client-User Model**: Clear separation between companies and individuals
3. **Multi-Tenancy**: Proper data isolation via client_id
4. **Role-Based Access**: Admin and staff roles with appropriate permissions
5. **Automatic Client Creation**: Seamless client creation during admin registration

The API remains fully functional with the same endpoints and responses, but now with a much cleaner, more maintainable, and scalable architecture! 🚀
