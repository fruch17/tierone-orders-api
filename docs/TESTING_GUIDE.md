# Testing Guide - Practical Examples

## Quick Start Testing Guide

This guide provides practical examples for running and understanding the tests in the TierOne Orders API challenge.

## Prerequisites

### 1. Environment Setup
```bash
# Ensure you have the project running
cd tierone-orders-api

# Check if testing environment is configured
php artisan env
```

### 2. Database Configuration
The tests use MySQL database (same as development), so ensure your MySQL server is running and the database is accessible.

#### MySQL Setup for Testing
```bash
# Ensure MySQL is running
# Check if database exists
mysql -u [YOUR_DB_USER] -p -e "SHOW DATABASES LIKE '[YOUR_DB_NAME]';"

# If database doesn't exist, create it
mysql -u [YOUR_DB_USER] -p -e "CREATE DATABASE IF NOT EXISTS [YOUR_DB_NAME];"
```

#### Test Database Configuration
- **Connection**: MySQL (same as development)
- **Database**: `[YOUR_DB_NAME]` (from your .env file)
- **User**: `[YOUR_DB_USER]` (from your .env file)
- **Password**: `[YOUR_DB_PASSWORD]` (from your .env file)
- **Host**: `[YOUR_DB_HOST]` (from your .env file)
- **Port**: `[YOUR_DB_PORT]` (from your .env file)

**Note**: Replace the placeholders with your actual database credentials from your `.env` file.

#### How to Get Your Database Credentials
```bash
# Check your .env file for database configuration
cat .env | grep DB_

# Example output:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=your_database_name
# DB_USERNAME=your_username
# DB_PASSWORD=your_password
```

### 3. Database Migrations Setup
**IMPORTANT**: Before running tests, you must execute the database migrations to create the required tables.

#### Step 1: Check Migration Status
```bash
# Check current migration status
php artisan migrate:status
```

#### Step 2: Run Migrations
```bash
# Run all pending migrations
php artisan migrate

# Or reset and run all migrations (if you want a clean start)
php artisan migrate:fresh
```

#### Step 3: Verify Tables Created
```bash
# Connect to MySQL and verify tables
mysql -u [YOUR_DB_USER] -p [YOUR_DB_NAME] -e "SHOW TABLES;"
```

**Expected Tables:**
- `clients` - Client companies/organizations
- `users` - User authentication and multi-tenancy
- `orders` - Order management
- `order_items` - Order line items
- `password_reset_tokens` - Password reset functionality
- `sessions` - Session management
- `jobs` - Queue jobs
- `cache` - Cache storage
- `migrations` - Migration tracking

#### Step 4: Verify Migration Success
```bash
# Check migration status again
php artisan migrate:status

# All migrations should show as "Ran"
```

#### Step 5: Test Database Connection
```bash
# Test database connection
php artisan tinker
# In tinker, run:
# DB::connection()->getPdo();
# exit
```

## Running Tests Step by Step

### Prerequisites Check
Before running any tests, ensure:
1. ✅ MySQL server is running
2. ✅ Database `[YOUR_DB_NAME]` exists
3. ✅ Migrations have been executed
4. ✅ All tables are created

### Step 1: Verify Database Setup
```bash
# Check if migrations are up to date
php artisan migrate:status

# If any migrations are pending, run them
php artisan migrate
```

### Step 2: Basic API Tests (No Database Required)
```bash
# Run basic API functionality tests
php artisan test --filter BasicApiTest
```

**Expected Output:**
```
✓ api returns json responses
✓ registration endpoint exists
✓ login endpoint exists
✓ orders endpoint requires auth
✓ admin endpoint requires admin

Tests:    26 passed (190 assertions)
Duration: 8.43s (localhost) / 29.92s (Docker)
Database: MySQL (optimized migrations)
```

**What this shows:**
- ✅ Core API functionality works
- ✅ Authentication middleware works
- ✅ Role-based access control works
- ✅ All tests passing with MySQL database

### Step 3: Individual Test Files
```bash
# Test authentication functionality
php artisan test tests/Feature/AuthTest.php

# Test order management functionality  
php artisan test tests/Feature/OrderTest.php

# Test service layer business logic
php artisan test tests/Unit/OrderServiceTest.php
```

### Step 3: Run All Tests
```bash
# Run complete test suite
php artisan test

# Run with verbose output for detailed information
php artisan test --debug
```

## Test Examples Explained

### Example 1: User Registration Test
```php
public function test_user_can_register(): void
{
    $userData = [
        'name' => 'John Doe',
        'company_name' => 'ACME Corp',
        'company_email' => 'contact@acme.com',
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
                    'email',
                    'role',
                    'client_id'
                ],
                'client' => [
                    'id',
                    'company_name',
                    'company_email',
                    'created_at',
                    'updated_at'
                ],
                'token',
                'token_type',
                'status_code'
            ]);
}
```

**What this test does:**
1. **Arrange**: Creates test data for user registration
2. **Act**: Makes POST request to registration endpoint
3. **Assert**: Verifies response status (201) and JSON structure

### Example 2: Order Creation Test
```php
public function test_user_can_create_order(): void
{
    // Create client first
    $client = Client::factory()->create([
        'company_name' => 'TierOne Corp',
        'company_email' => 'contact@tierone.com'
    ]);

    // Create authenticated user
    $user = User::factory()->create([
        'role' => 'admin',
        'client_id' => $client->id
    ]);

    $orderData = [
        'tax' => 15.50,
        'notes' => 'Urgent delivery',
        'items' => [
            [
                'product_name' => 'Laptop',
                'quantity' => 2,
                'unit_price' => 1200.00
            ]
        ]
    ];

    $response = $this->actingAs($user)
                     ->postJson('/api/orders', $orderData);

    $response->assertStatus(201);
    
    // Verify calculations
    $this->assertEquals(2400.00, $response->json('order.subtotal')); // 2*1200
    $this->assertEquals(15.50, $response->json('order.tax'));
    $this->assertEquals(2415.50, $response->json('order.total')); // subtotal + tax
}
```

**What this test does:**
1. **Arrange**: Creates authenticated user and order data
2. **Act**: Makes authenticated POST request to create order
3. **Assert**: Verifies response and business logic calculations

### Example 3: Multi-tenancy Security Test
```php
public function test_user_cannot_access_other_user_orders(): void
{
    // Create two clients
    $client1 = Client::factory()->create(['company_name' => 'Client 1']);
    $client2 = Client::factory()->create(['company_name' => 'Client 2']);

    // Create two users for different clients
    $user1 = User::factory()->create(['role' => 'admin', 'client_id' => $client1->id]);
    $user2 = User::factory()->create(['role' => 'admin', 'client_id' => $client2->id]);

    // Create order for user2's client
    $order = Order::factory()->create([
        'client_id' => $client2->id,
        'user_id' => $user2->id
    ]);

    // Try to access user2's order as user1
    $response = $this->actingAs($user1)
                     ->getJson("/api/orders/{$order->id}");

    $response->assertStatus(404)
            ->assertJson([
                'message' => 'Order not found',
                'status_code' => 404
            ]);
}
```

**What this test does:**
1. **Arrange**: Creates two users and an order for user2
2. **Act**: User1 tries to access user2's order
3. **Assert**: Verifies user1 gets 404 (not found) - proving multi-tenancy works

## Understanding Test Results

### Successful Test Output
```
✓ user can register
✓ user can login
✓ user cannot login with invalid credentials
✓ admin can register staff
✓ staff cannot register staff

Tests:    5 passed (15 assertions)
Duration: 0.85s
```

### Failed Test Output
```
⨯ user can register
Failed asserting that 500 is identical to 201.

SQLSTATE[HY000]: General error: 1 no such table: users
```

**What this means:**
- All tests are passing with MySQL database
- Database tables are properly created and accessible
- Tests demonstrate complete functionality with client-user separation

## Test Categories

### 1. Feature Tests
**Purpose**: Test complete API functionality
**Files**: `AuthTest.php`, `OrderTest.php`
**What they test**:
- End-to-end API calls
- Authentication flows
- Order management
- Multi-tenancy
- Error handling

### 2. Unit Tests
**Purpose**: Test business logic in isolation
**Files**: `OrderServiceTest.php`
**What they test**:
- Service layer calculations
- Multi-tenancy logic
- Data access control
- Business rules

### 3. Basic API Tests
**Purpose**: Test core API behavior
**Files**: `BasicApiTest.php`
**What they test**:
- JSON response format
- Middleware functionality
- Endpoint availability
- Error responses

## Common Test Patterns

### 1. Authentication Testing
```php
// Test with authenticated user
$user = User::factory()->create();
$response = $this->actingAs($user)->getJson('/api/orders');

// Test without authentication
$response = $this->getJson('/api/orders');
$response->assertStatus(401);
```

### 2. Role-based Testing
```php
// Test admin access
$admin = User::factory()->create(['role' => 'admin']);
$response = $this->actingAs($admin)->postJson('/api/auth/register-staff', $data);

// Test staff access (should fail)
$staff = User::factory()->create(['role' => 'staff']);
$response = $this->actingAs($staff)->postJson('/api/auth/register-staff', $data);
$response->assertStatus(403);
```

### 3. Data Validation Testing
```php
// Test with valid data
$validData = ['name' => 'John', 'email' => 'john@test.com'];
$response = $this->postJson('/api/auth/register', $validData);
$response->assertStatus(201);

// Test with invalid data
$invalidData = ['name' => '', 'email' => 'invalid-email'];
$response = $this->postJson('/api/auth/register', $invalidData);
$response->assertStatus(422); // Validation error
```

## Debugging Tests

### 1. Verbose Output
```bash
php artisan test --debug
```

### 2. Specific Test Method
```bash
php artisan test --filter test_user_can_register
```

### 3. Stop on First Failure
```bash
php artisan test --stop-on-failure
```

### 4. Test Coverage
```bash
# Run tests with debug information
php artisan test --debug

# Note: --coverage requires Xdebug configuration
# For coverage analysis, use PHPUnit directly:
# ./vendor/bin/phpunit --coverage-html coverage/
```

## Troubleshooting

### Common Migration Issues

#### Issue 1: "No such table: users"
**Error**: `SQLSTATE[HY000]: General error: 1 no such table: users`

**Solution**:
```bash
# Run migrations
php artisan migrate

# Or reset and run all migrations
php artisan migrate:fresh
```

#### Issue 2: "Connection refused"
**Error**: `SQLSTATE[HY000] [2002] No se puede establecer una conexión`

**Solution**:
```bash
# Check if MySQL is running
# Start MySQL service
# Verify connection settings in .env file
```

#### Issue 3: "Access denied"
**Error**: `SQLSTATE[28000] [1045] Access denied for user '[YOUR_DB_USER]'@'localhost'`

**Solution**:
```bash
# Check MySQL user permissions
mysql -u root -p -e "GRANT ALL PRIVILEGES ON [YOUR_DB_NAME].* TO '[YOUR_DB_USER]'@'localhost';"
mysql -u root -p -e "FLUSH PRIVILEGES;"
```

#### Issue 4: "Database doesn't exist"
**Error**: `SQLSTATE[42000] [1049] Unknown database '[YOUR_DB_NAME]'`

**Solution**:
```bash
# Create database
mysql -u [YOUR_DB_USER] -p -e "CREATE DATABASE [YOUR_DB_NAME];"
```

### Migration Commands Reference
```bash
# Check migration status
php artisan migrate:status

# Run pending migrations
php artisan migrate

# Reset and run all migrations
php artisan migrate:fresh

# Rollback last migration
php artisan migrate:rollback

# Rollback all migrations
php artisan migrate:reset
```

### Quick Setup Checklist
Before running tests, verify:
- [ ] MySQL server is running
- [ ] Database `[YOUR_DB_NAME]` exists
- [ ] User `[YOUR_DB_USER]` has access to database
- [ ] All migrations have been executed
- [ ] All required tables exist
- [ ] Test environment is configured

## Factory Usage Examples

### User Factory
```php
// Create client first
$client = Client::factory()->create([
    'company_name' => 'TierOne Corp',
    'company_email' => 'contact@tierone.com'
]);

// Create admin user
$admin = User::factory()->create([
    'role' => 'admin',
    'client_id' => $client->id
]);

// Create staff user
$staff = User::factory()->create([
    'role' => 'staff',
    'client_id' => $client->id
]);

// Create multiple users
$users = User::factory()->count(5)->create();
```

### Order Factory
```php
// Create order for specific client
$order = Order::factory()
    ->forClient($client)
    ->createdBy($admin)
    ->create();

// Create multiple orders
$orders = Order::factory()->count(3)->create([
    'client_id' => $client->id
]);
```

## Best Practices Demonstrated

### 1. Test Naming
- `test_user_can_register` - Clear, descriptive
- `test_user_cannot_access_other_user_orders` - Specific behavior

### 2. Test Structure
- **Arrange**: Set up test data
- **Act**: Execute the action
- **Assert**: Verify the result

### 3. Assertions
- Status codes: `assertStatus(201)`
- JSON structure: `assertJsonStructure()`
- Database state: `assertDatabaseHas()`
- Calculations: `assertEquals()`

### 4. Test Isolation
- Each test is independent
- Uses `RefreshDatabase` trait
- No shared state between tests

## Conclusion

This testing implementation demonstrates:

1. **Professional Testing**: Comprehensive coverage of all functionality
2. **Security Testing**: Authentication, authorization, and multi-tenancy
3. **Business Logic Testing**: Service layer validation
4. **API Testing**: End-to-end functionality
5. **Error Handling**: Proper error response testing

The tests provide confidence in the system's reliability and demonstrate understanding of Laravel testing best practices suitable for a technical challenge.
