# Role-Based Multi-Tenancy System

## Overview

The TierOne Orders API implements a **role-based multi-tenancy system** where users have different roles with varying levels of access and permissions. This demonstrates proper **authorization** and **multi-tenancy** concepts in a Laravel application.

## User Roles

### 1. Admin Role (`admin`)
- **Description**: Full system access and management capabilities
- **Permissions**:
  - Can register new staff members
  - Can view all orders across the system
  - Can manage user accounts
  - Full CRUD operations on orders
- **Registration**: First user registered becomes admin automatically

### 2. Staff Role (`staff`)
- **Description**: Limited access to their own data
- **Permissions**:
  - Can only view their own orders
  - Can create orders for themselves
  - Cannot register other users
  - Cannot access admin-only endpoints
- **Registration**: Created by admin users only

## Role Assignment Logic

### Public Registration (`/api/auth/register`)
```json
{
  "name": "John Admin",
  "company_name": "TierOne Corp",
  "email": "admin@tierone.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Result**: User automatically gets `role: "admin"`

### Staff Registration (`/api/auth/register-staff`)
**Requires**: Admin authentication token

```json
{
  "name": "Jane Staff",
  "company_name": "TierOne Corp",
  "email": "staff@tierone.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Result**: User gets `role: "staff"`

## API Endpoints by Role

### Public Endpoints (No Authentication)
- `POST /api/auth/register` - Register as admin
- `POST /api/auth/login` - Login any user

### Authenticated Endpoints (Any Role)
- `POST /api/auth/logout` - Logout
- `GET /api/auth/me` - Get current user profile
- `GET /api/orders` - List user's orders
- `POST /api/orders` - Create order
- `GET /api/orders/{id}` - Get specific order
- `GET /api/clients/{id}/orders` - Get orders for specific client

### Admin-Only Endpoints
- `POST /api/auth/register-staff` - Register new staff member

## Security Implementation

### 1. Middleware Protection
```php
// Admin-only routes
Route::post('/register-staff', [AuthController::class, 'registerStaff'])
    ->middleware(['auth:sanctum', 'admin']);
```

### 2. Role Verification
```php
// In EnsureAdminRole middleware
if (!auth()->user()->isAdmin()) {
    return response()->json([
        'message' => 'Forbidden. Admin access required.',
        'status_code' => 403,
    ], 403);
}
```

### 3. Model Methods
```php
// User model helper methods
public function isAdmin(): bool
public function isStaff(): bool
public function canManageUsers(): bool
```

## Multi-Tenancy Implementation

### Data Isolation
- **Orders**: Scoped by `user_id` (tenant isolation)
- **Order Items**: Inherit tenant scope from parent order
- **Users**: Each user represents a tenant/client

### Access Control
- **Staff**: Can only access their own data
- **Admin**: Can access all data (for management purposes)

## Example Usage Flow

### 1. Register Admin
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Admin User",
    "company_name": "TierOne Corp",
    "email": "admin@tierone.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### 2. Login as Admin
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@tierone.com",
    "password": "password123"
  }'
```

### 3. Register Staff (Admin Only)
```bash
curl -X POST http://localhost:8000/api/auth/register-staff \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -d '{
    "name": "Staff User",
    "company_name": "TierOne Corp",
    "email": "staff@tierone.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### 4. Login as Staff
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "staff@tierone.com",
    "password": "password123"
  }'
```

## Error Responses

### Unauthorized (401)
```json
{
  "message": "Unauthenticated.",
  "status_code": 401
}
```

### Forbidden (403)
```json
{
  "message": "Forbidden. Admin access required.",
  "error": "You must be an admin to access this resource",
  "status_code": 403
}
```

## Database Schema

### Users Table
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    company_name VARCHAR(255) NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') DEFAULT 'staff' NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

## Benefits of This Approach

1. **Security**: Role-based access control prevents unauthorized access
2. **Scalability**: Easy to add new roles and permissions
3. **Multi-tenancy**: Clear data isolation between users
4. **Maintainability**: Clean separation of concerns
5. **Flexibility**: Admin can manage staff without system access

## Testing the System

### Test Admin Registration
```bash
# Register first user (becomes admin)
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Admin","company_name":"TierOne","email":"admin@test.com","password":"password123","password_confirmation":"password123"}'
```

### Test Staff Registration
```bash
# Login as admin first
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@test.com","password":"password123"}'

# Use token to register staff
curl -X POST http://localhost:8000/api/auth/register-staff \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"name":"Staff","company_name":"TierOne","email":"staff@test.com","password":"password123","password_confirmation":"password123"}'
```

### Test Role Enforcement
```bash
# Try to register staff without admin token (should fail)
curl -X POST http://localhost:8000/api/auth/register-staff \
  -H "Content-Type: application/json" \
  -d '{"name":"Staff","email":"staff2@test.com","password":"password123","password_confirmation":"password123"}'
```

This role-based system demonstrates proper **multi-tenancy**, **authorization**, and **security** practices in a Laravel application, making it perfect for a technical challenge presentation.
