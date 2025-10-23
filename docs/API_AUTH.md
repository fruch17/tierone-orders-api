# Authentication API Documentation

## Overview

The TierOne Orders API uses **Laravel Sanctum** for token-based authentication. The system implements **role-based multi-tenancy** with a **client-user separation** model:

- **Clients**: Represent companies/organizations (with `company_name` and `company_email`)
- **Users**: Represent individual people (with personal `email` and `name`)
- **Admin**: Full system access, can register staff members, belongs to their own client
- **Staff**: Limited access to their own data, belongs to the same client as their admin

### Key Features

- **Client-User Separation**: Companies and individuals are separate entities
- **Automatic Client Creation**: When an admin registers, a client is automatically created
- **Multi-tenancy**: All data is scoped by `client_id` for proper isolation
- **Role-based Access**: Different permissions based on user role
- **Token-based Authentication**: Secure API access using Laravel Sanctum

## Endpoints

### 1. Register Admin User

**Endpoint**: `POST /api/auth/register`

**Description**: Register a new admin user. This creates both a **client** (company) and a **user** (admin) record. The user is automatically linked to the created client.

**Request Body**:
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

**Response** (201 Created):
```json
{
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "name": "John Admin",
    "email": "admin@tierone.com",
    "role": "admin",
    "client_id": 1,
    "email_verified_at": null,
    "created_at": "2025-10-22 13:08:38",
    "updated_at": "2025-10-22 13:08:38"
  },
  "client": {
    "id": 1,
    "company_name": "TierOne Corp",
    "company_email": "contact@tierone.com",
    "created_at": "2025-10-22 13:08:38",
    "updated_at": "2025-10-22 13:08:38"
  },
  "token": "1|abc123def456ghi789jkl012mno345pqr678stu901vwx234yz",
  "token_type": "Bearer",
  "status_code": 201
}
```

**cURL Example**:
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Admin",
    "company_name": "TierOne Corp",
    "company_email": "contact@tierone.com",
    "email": "admin@tierone.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### 2. Register Staff User (Admin Only)

**Endpoint**: `POST /api/auth/register-staff`

**Description**: Register a new staff user. **Requires admin authentication**. The staff user will belong to the same client as the admin who registers them.

**Headers**:
```
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**Request Body**:
```json
{
  "name": "Jane Staff",
  "email": "staff@tierone.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response** (201 Created):
```json
{
  "message": "Staff member registered successfully",
  "staff": {
    "id": 2,
    "name": "Jane Staff",
    "email": "staff@tierone.com",
    "role": "staff",
    "client_id": 1,
    "email_verified_at": null,
    "created_at": "2025-10-22 13:08:38",
    "updated_at": "2025-10-22 13:08:38"
  },
  "status_code": 201
}
```

**cURL Example**:
```bash
curl -X POST http://localhost:8000/api/auth/register-staff \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -d '{
    "name": "Jane Staff",
    "email": "staff@tierone.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### 3. Login User

**Endpoint**: `POST /api/auth/login`

**Description**: Authenticate a user and return an access token.

**Request Body**:
```json
{
  "email": "admin@tierone.com",
  "password": "password123"
}
```

**Response** (200 OK):
```json
{
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "John Admin",
    "email": "admin@tierone.com",
    "role": "admin",
    "client_id": 1,
    "email_verified_at": null,
    "created_at": "2025-10-22 13:08:38",
    "updated_at": "2025-10-22 13:08:38"
  },
  "token": "2|def456ghi789jkl012mno345pqr678stu901vwx234yz567",
  "token_type": "Bearer",
  "status_code": 200
}
```

**cURL Example**:
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@tierone.com",
    "password": "password123"
  }'
```

### 4. Logout User

**Endpoint**: `POST /api/auth/logout`

**Description**: Revoke the current access token.

**Headers**:
```
Authorization: Bearer {token}
```

**Response** (200 OK):
```json
{
  "message": "Logged out successfully",
  "status_code": 200
}
```

**cURL Example**:
```bash
curl -X POST http://localhost:8000/api/auth/logout \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 5. Get Current User

**Endpoint**: `GET /api/auth/me`

**Description**: Get the current authenticated user's profile.

**Headers**:
```
Authorization: Bearer {token}
```

**Response** (200 OK):
```json
{
  "user": {
    "id": 1,
    "name": "John Admin",
    "email": "admin@tierone.com",
    "role": "admin",
    "client_id": 1,
    "email_verified_at": null,
    "created_at": "2025-10-22 13:08:38",
    "updated_at": "2025-10-22 13:08:38"
  },
  "status_code": 200
}
```

**cURL Example**:
```bash
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Error Responses

### Validation Errors (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password confirmation does not match."]
  },
  "status_code": 422
}
```

### Unauthorized (401)
```json
{
  "message": "Unauthenticated.",
  "status_code": 401
}
```

### Forbidden (403) - Admin Only
```json
{
  "message": "Forbidden. Admin access required.",
  "error": "You must be an admin to access this resource",
  "status_code": 403
}
```

### Method Not Allowed (405)
```json
{
  "message": "Method not allowed",
  "error": "The requested method is not allowed for this endpoint",
  "status_code": 405
}
```

## Data Structure Changes

### Client-User Separation

The API now implements a **client-user separation** model:

- **Clients**: Represent companies/organizations with `company_name` and `company_email`
- **Users**: Represent individual people with personal `email` and `name`
- **Relationship**: Users belong to clients via `client_id`

### Registration Process

When an admin registers:

1. **Client Creation**: A new client record is created with company information
2. **User Creation**: A new user record is created and linked to the client
3. **Response**: Both user and client data are returned

### Multi-tenancy

- **Admin Users**: Belong to their own client (the one they created)
- **Staff Users**: Belong to the same client as the admin who registered them
- **Data Isolation**: All orders and data are scoped by `client_id`

## Role-Based Access Control

### Admin Users
- Can register new staff members
- Can access all system endpoints
- Can view all orders across the system
- Full CRUD operations on orders

### Staff Users
- Can only access their own data
- Can create orders for themselves
- Cannot register other users
- Cannot access admin-only endpoints

## Security Features

1. **Token-based Authentication**: Uses Laravel Sanctum for secure API access
2. **Role-based Authorization**: Different access levels based on user role
3. **Input Validation**: All inputs validated using FormRequest classes
4. **Password Hashing**: Passwords automatically hashed using bcrypt
5. **CSRF Protection**: Built-in CSRF protection for web routes
6. **Rate Limiting**: Built-in rate limiting for API endpoints

## Usage Flow

1. **Register Admin**: First user becomes admin automatically
2. **Login Admin**: Get authentication token
3. **Register Staff**: Admin can register staff members
4. **Login Staff**: Staff can login with their credentials
5. **Access Resources**: Use token to access protected endpoints

## Testing

### Test Admin Registration
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test Admin",
    "company_name": "Test Corp",
    "company_email": "contact@testcorp.com",
    "email": "admin@test.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### Test Staff Registration (Admin Required)
```bash
# First login as admin
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@test.com",
    "password": "password123"
  }'

# Use admin token to register staff
curl -X POST http://localhost:8000/api/auth/register-staff \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -d '{
    "name": "Test Staff",
    "email": "staff@test.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

This authentication system demonstrates proper **multi-tenancy**, **role-based access control**, and **security** practices in a Laravel application.