# Role-Based Multi-Tenancy System

## Overview

The TierOne Orders API implements a **role-based multi-tenancy system** where users have different roles with varying levels of access and permissions. This demonstrates proper **authorization** and **multi-tenancy** concepts in a Laravel application.

## User Roles

### 1. Admin Role (`admin`)
- **Description**: Full system access and management capabilities
- **Permissions**:
  - Can register new staff members for their client
  - Can view all orders for their client
  - Can manage staff accounts for their client
  - Full CRUD operations on orders for their client
  - Automatic client creation during registration
- **Registration**: First user registered becomes admin automatically

### 2. Staff Role (`staff`)
- **Description**: Limited access to their client's data
- **Permissions**:
  - Can view orders for their client (same as admin)
  - Can create orders for their client
  - Cannot register other users
  - Cannot access admin-only endpoints
  - Belongs to the same client as their admin
- **Registration**: Created by admin users only

## Role Assignment Logic

### Public Registration (`/api/auth/register`)
```json
{
  "name": "John Admin",
  "company_name": "TierOne Corp",
  "company_email": "contact@tierone.com",
  "email": "admin@tierone.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Result**: 
- User automatically gets `role: "admin"`
- Client is created with `company_name` and `company_email`
- User is linked to the created client via `client_id`

### Staff Registration (`/api/auth/register-staff`)
**Requires**: Admin authentication token

```json
{
  "name": "Jane Staff",
  "email": "staff@tierone.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Result**: 
- User gets `role: "staff"`
- User is linked to the same client as the admin via `client_id`

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

### Client-User Separation Model
- **Clients**: Represent companies/organizations with `company_name` and `company_email`
- **Users**: Represent individual people with personal `email` and `name`
- **Relationship**: Users belong to clients via `client_id`

### Data Isolation
- **Orders**: Scoped by `client_id` (client ownership) and `user_id` (audit trail)
- **Order Items**: Inherit tenant scope from parent order
- **Users**: Each user belongs to a client (tenant)

### Access Control
- **Staff**: Can only access data for their client
- **Admin**: Can access all data for their client (same as staff)
- **Both**: Can see who created each order (`user_id`)

## Example Usage Flow

### 1. Register Admin
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Admin User",
    "company_name": "TierOne Corp",
    "company_email": "contact@tierone.com",
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

### Clients Table
```sql
CREATE TABLE clients (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    company_email VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX clients_company_email_index (company_email)
);
```

### Users Table
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') DEFAULT 'admin' NOT NULL,
    client_id BIGINT UNSIGNED NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX users_client_id_index (client_id),
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);
```

## Benefits of This Approach

1. **Security**: Role-based access control prevents unauthorized access
2. **Scalability**: Easy to add new roles and permissions
3. **Multi-tenancy**: Clear data isolation between clients
4. **Maintainability**: Clean separation of concerns
5. **Flexibility**: Admin can manage staff without system access
6. **Client-User Separation**: Clear distinction between companies and individuals
7. **Audit Trail**: Complete tracking of who created what

## Testing the System

### Test Admin Registration
```bash
# Register first user (becomes admin)
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Admin","company_name":"TierOne","company_email":"contact@tierone.com","email":"admin@test.com","password":"password123","password_confirmation":"password123"}'
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
  -d '{"name":"Staff","email":"staff@test.com","password":"password123","password_confirmation":"password123"}'
```

### Test Role Enforcement
```bash
# Try to register staff without admin token (should fail)
curl -X POST http://localhost:8000/api/auth/register-staff \
  -H "Content-Type: application/json" \
  -d '{"name":"Staff","email":"staff2@test.com","password":"password123","password_confirmation":"password123"}'
```

This role-based system demonstrates proper **multi-tenancy**, **authorization**, and **security** practices in a Laravel application, making it perfect for a technical challenge presentation.
