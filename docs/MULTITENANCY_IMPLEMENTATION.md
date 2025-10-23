# Multi-Tenancy Implementation Guide

## Overview

The TierOne Orders API implements a **true multi-tenancy system** using `client_id` for data isolation. This system allows both admin and staff users to work with the same client's data while maintaining proper security and data separation.

## Multi-Tenancy Architecture

## Multi-Tenancy Architecture

### Database Schema

#### Clients Table
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

#### Users Table
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

#### Orders Table
```sql
CREATE TABLE orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    order_number VARCHAR(255) UNIQUE NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    tax DECIMAL(10,2) DEFAULT 0 NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX orders_client_id_index (client_id),
    INDEX orders_user_id_index (user_id),
    INDEX orders_client_created_index (client_id, created_at),
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Multi-Tenancy Logic

#### Client-User Separation Model
The system implements a **client-user separation** model where:

- **Clients**: Represent companies/organizations with `company_name` and `company_email`
- **Users**: Represent individual people with personal `email` and `name`
- **Relationship**: Users belong to clients via `client_id`

#### Client ID Assignment
- **Admin Users**: Belong to their own client (created during registration)
- **Staff Users**: Belong to the same client as the admin who registered them

#### Data Isolation
All data is scoped by `client_id`:
- **Orders**: `client_id` determines which client owns the order
- **Users**: `client_id` determines which client the user belongs to
- **Access Control**: Users can only access data for their client

## User Roles and Permissions

## User Roles and Permissions

### Admin Users
- **Client ID**: Points to their own client (created during registration)
- **Permissions**:
  - Can register staff members for their client
  - Can create orders for their client
  - Can view all orders for their client
  - Can manage staff accounts
  - Full CRUD operations on orders
  - Automatic client creation during registration

### Staff Users
- **Client ID**: Points to the same client as their admin
- **Permissions**:
  - Can create orders for their client
  - Can view orders for their client
  - Cannot register other users
  - Cannot access admin-only endpoints
  - Limited to their client's data only

## Data Isolation Examples

### Scenario 1: Admin User Registration
```php
// When admin registers, both client and user are created
// Client record
{
    "id": 1,
    "company_name": "TierOne Corp",
    "company_email": "contact@tierone.com"
}

// User record
{
    "id": 1,
    "name": "Admin User",
    "role": "admin",
    "client_id": 1  // Points to the created client
}

// Can access orders where client_id = 1
```

### Scenario 2: Staff User Registration
```php
// Staff is registered by admin and belongs to same client
// User record
{
    "id": 2,
    "name": "Staff User",
    "role": "staff",
    "client_id": 1  // Same client as admin
}

// Can access orders where client_id = 1 (same as admin)
```

### Scenario 3: Multiple Staff for Same Client
```php
// Client (ID: 1)
{
    "id": 1,
    "company_name": "TierOne Corp",
    "company_email": "contact@tierone.com"
}

// Admin (ID: 1)
{
    "id": 1,
    "name": "Admin User",
    "role": "admin",
    "client_id": 1
}

// Staff 1 (ID: 2)
{
    "id": 2,
    "name": "Staff User 1",
    "role": "staff",
    "client_id": 1
}

// Staff 2 (ID: 3)
{
    "id": 3,
    "name": "Staff User 2",
    "role": "staff",
    "client_id": 1
}

// All three users can access the same orders (client_id = 1)
```

## API Implementation

### Order Creation
```php
// In OrderService::createOrder()
$order = Order::create([
    'client_id' => auth()->user()->client_id,  // User's client
    'user_id' => auth()->id(),                 // Who created the order
    'tax' => $request->tax,
    'notes' => $request->notes,
    // ... other fields
]);
```

### Order Filtering
```php
// In Order model scope
public function scopeForAuthClient($query)
{
    if (auth()->check()) {
        $clientId = auth()->user()->client_id;
        return $query->where('client_id', $clientId);
    }
    
    return $query;
}
```

### Security Checks
```php
// In ClientController::orders()
$userClientId = auth()->user()->client_id;
if ($userClientId !== $id) {
    return response()->json([
        'message' => 'Forbidden. You can only access orders for your client.',
        'status_code' => 403,
    ], 403);
}
```

## Testing Multi-Tenancy

### Test Admin Registration
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

**Response**:
```json
{
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "name": "Admin User",
    "role": "admin",
    "client_id": 1
  },
  "client": {
    "id": 1,
    "company_name": "TierOne Corp",
    "company_email": "contact@tierone.com"
  },
  "token": "...",
  "token_type": "Bearer",
  "status_code": 201
}
```

### Test Staff Registration
```bash
# Login as admin first
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@tierone.com",
    "password": "password123"
  }'

# Register staff using admin token
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

**Response**:
```json
{
  "message": "Staff member registered successfully",
  "staff": {
    "id": 2,
    "name": "Staff User",
    "role": "staff",
    "client_id": 1
  },
  "status_code": 201
}
```

### Test Order Creation
```bash
# Create order as admin
curl -X POST http://localhost:8000/api/orders \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -d '{
    "tax": 10.00,
    "notes": "Order created by admin",
    "items": [
      {
        "product_name": "Product 1",
        "quantity": 2,
        "unit_price": 25.00
      }
    ]
  }'
```

**Result**: Order created with `client_id = 1` and `user_id = 1` (admin's ID)

```bash
# Create order as staff
curl -X POST http://localhost:8000/api/orders \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer STAFF_TOKEN" \
  -d '{
    "tax": 5.00,
    "notes": "Order created by staff",
    "items": [
      {
        "product_name": "Product 2",
        "quantity": 1,
        "unit_price": 50.00
      }
    ]
  }'
```

**Result**: Order created with `client_id = 1` (same as admin) and `user_id = 2` (staff's ID)

### Test Order Access
```bash
# Both admin and staff can see the same orders
curl -X GET http://localhost:8000/api/orders \
  -H "Authorization: Bearer ADMIN_TOKEN"

curl -X GET http://localhost:8000/api/orders \
  -H "Authorization: Bearer STAFF_TOKEN"
```

**Result**: Both requests return the same orders (client_id = 1)

## Benefits of This Implementation

1. **True Multi-Tenancy**: Multiple users can work with the same client's data
2. **Data Isolation**: Users can only access their client's data
3. **Scalability**: Easy to add more staff members to existing clients
4. **Security**: Proper authorization checks at every level
5. **Flexibility**: Admin and staff can both create and manage orders
6. **Maintainability**: Clear separation of concerns and responsibilities

## Database Queries for Verification

### Check Clients and Users
```sql
SELECT 
    c.id as client_id,
    c.company_name,
    c.company_email,
    u.id as user_id,
    u.name as user_name,
    u.role,
    u.client_id
FROM clients c
LEFT JOIN users u ON c.id = u.client_id
ORDER BY c.id, u.role;
```

### Check Orders by Client
```sql
SELECT 
    o.id,
    o.order_number,
    o.client_id,
    o.user_id,
    u.name as created_by,
    o.total,
    o.created_at 
FROM orders o
JOIN users u ON o.user_id = u.id
ORDER BY o.client_id, o.created_at DESC;
```

### Check Multi-Tenancy Data
```sql
SELECT 
    c.id as client_id,
    c.company_name,
    COUNT(DISTINCT u.id) as user_count,
    COUNT(o.id) as order_count,
    SUM(o.total) as total_revenue
FROM clients c
LEFT JOIN users u ON c.id = u.client_id
LEFT JOIN orders o ON c.id = o.client_id
GROUP BY c.id, c.company_name
ORDER BY c.id;
```

This multi-tenancy implementation demonstrates proper **client-user separation**, **data isolation**, **role-based access control**, and **scalable architecture** - perfect for a technical challenge presentation!
