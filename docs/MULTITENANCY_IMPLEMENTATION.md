# Multi-Tenancy Implementation Guide

## Overview

The TierOne Orders API implements a **true multi-tenancy system** using `client_id` for data isolation. This system allows both admin and staff users to work with the same client's data while maintaining proper security and data separation.

## Multi-Tenancy Architecture

### Database Schema

#### Users Table
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    company_name VARCHAR(255) NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') DEFAULT 'staff' NOT NULL,
    client_id BIGINT UNSIGNED DEFAULT 0 NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

#### Orders Table
```sql
CREATE TABLE orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id BIGINT UNSIGNED NOT NULL,
    order_number VARCHAR(255) UNIQUE NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    tax DECIMAL(10,2) DEFAULT 0 NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX orders_client_id_index (client_id)
);
```

### Multi-Tenancy Logic

#### Client ID Assignment
- **Admin Users**: `client_id = 0` (they are their own client)
- **Staff Users**: `client_id = admin_id` (they belong to an admin's client)

#### Effective Client ID
The system uses `getEffectiveClientId()` method to determine which client's data a user can access:

```php
public function getEffectiveClientId(): int
{
    if ($this->isAdmin()) {
        return $this->id; // Admin is their own client
    }
    
    return $this->client_id; // Staff belongs to admin's client
}
```

## User Roles and Permissions

### Admin Users
- **Client ID**: `0` (stored in database)
- **Effective Client ID**: Their own user ID
- **Permissions**:
  - Can register staff members
  - Can create orders for their client
  - Can view all orders for their client
  - Can manage staff accounts
  - Full CRUD operations on orders

### Staff Users
- **Client ID**: Admin's user ID (stored in database)
- **Effective Client ID**: Admin's user ID (same as their client_id)
- **Permissions**:
  - Can create orders for their admin's client
  - Can view orders for their admin's client
  - Cannot register other users
  - Cannot access admin-only endpoints

## Data Isolation Examples

### Scenario 1: Admin User (ID: 1)
```php
// User record
{
    "id": 1,
    "name": "Admin User",
    "role": "admin",
    "client_id": 0
}

// Effective client ID = 1 (their own ID)
// Can access orders where client_id = 1
```

### Scenario 2: Staff User (ID: 2)
```php
// User record
{
    "id": 2,
    "name": "Staff User",
    "role": "staff",
    "client_id": 1  // Belongs to admin with ID 1
}

// Effective client ID = 1 (same as client_id)
// Can access orders where client_id = 1
```

### Scenario 3: Multiple Staff for Same Admin
```php
// Admin (ID: 1)
{
    "id": 1,
    "name": "Admin User",
    "role": "admin",
    "client_id": 0
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
    'client_id' => auth()->user()->getEffectiveClientId(),
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
        $clientId = auth()->user()->getEffectiveClientId();
        return $query->where('client_id', $clientId);
    }
    
    return $query;
}
```

### Security Checks
```php
// In ClientController::orders()
$effectiveClientId = auth()->user()->getEffectiveClientId();
if ($effectiveClientId !== $id) {
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
    "company_name": null,
    "email": "admin@tierone.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

**Response**:
```json
{
  "user": {
    "id": 1,
    "name": "Admin User",
    "role": "admin",
    "client_id": 0
  }
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
  "staff": {
    "id": 2,
    "name": "Staff User",
    "role": "staff",
    "client_id": 1
  }
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

**Result**: Order created with `client_id = 1` (admin's effective client ID)

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

**Result**: Order created with `client_id = 1` (staff's effective client ID, same as admin)

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

### Check User Roles and Client IDs
```sql
SELECT id, name, role, client_id FROM users;
```

### Check Orders by Client
```sql
SELECT id, order_number, client_id, total, created_at 
FROM orders 
ORDER BY client_id, created_at DESC;
```

### Check Multi-Tenancy Data
```sql
SELECT 
    u.id as user_id,
    u.name as user_name,
    u.role,
    u.client_id,
    CASE 
        WHEN u.role = 'admin' THEN u.id
        ELSE u.client_id
    END as effective_client_id,
    COUNT(o.id) as order_count
FROM users u
LEFT JOIN orders o ON (
    CASE 
        WHEN u.role = 'admin' THEN u.id
        ELSE u.client_id
    END = o.client_id
)
GROUP BY u.id, u.name, u.role, u.client_id
ORDER BY effective_client_id, u.role;
```

This multi-tenancy implementation demonstrates proper **data isolation**, **role-based access control**, and **scalable architecture** - perfect for a technical challenge presentation!
