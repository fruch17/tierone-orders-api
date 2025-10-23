# Order Audit Trail Implementation

## Overview

The TierOne Orders API now includes **audit trail functionality** by tracking both the **client ownership** and **user creation** for each order. This provides complete traceability and meets enterprise-level requirements.

## Database Schema

### Orders Table Structure
```sql
CREATE TABLE orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id BIGINT UNSIGNED NOT NULL,           -- Multi-tenancy: which client owns the order
    user_id BIGINT UNSIGNED NOT NULL,             -- Audit trail: which user created the order
    order_number VARCHAR(255) UNIQUE NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    tax DECIMAL(10,2) DEFAULT 0 NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX orders_client_id_index (client_id),
    INDEX orders_user_id_index (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Field Purposes

### `client_id` - Multi-Tenancy
- **Purpose**: Determines which client the order belongs to
- **Logic**: 
  - Admin users: `client_id = admin_id` (their own ID)
  - Staff users: `client_id = admin_id` (their admin's ID)
- **Usage**: Data isolation and access control

### `user_id` - Audit Trail
- **Purpose**: Tracks which specific user created the order
- **Logic**: Always the ID of the authenticated user who created the order
- **Usage**: Audit trail, reporting, and accountability

## Implementation Examples

### Scenario 1: Admin Creates Order
```php
// Admin User (ID: 1, role: admin, client_id: 0)
// Creates an order

Order::create([
    'client_id' => 1,  // Admin's effective client ID (their own ID)
    'user_id' => 1,     // Admin's user ID (who created it)
    'tax' => 10.00,
    'notes' => 'Order created by admin',
    // ... other fields
]);
```

### Scenario 2: Staff Creates Order
```php
// Staff User (ID: 2, role: staff, client_id: 1)
// Creates an order for the same client as admin

Order::create([
    'client_id' => 1,  // Staff's effective client ID (admin's ID)
    'user_id' => 2,     // Staff's user ID (who created it)
    'tax' => 5.00,
    'notes' => 'Order created by staff',
    // ... other fields
]);
```

## API Response Examples

### Order Response with Audit Information
```json
{
    "id": 1,
    "order_number": "ORD-20251022-ABCD",
    "client_id": 1,
    "user_id": 2,
    "subtotal": 50.00,
    "tax": 5.00,
    "total": 55.00,
    "notes": "Order created by staff",
    "items": [
        {
            "id": 1,
            "product_name": "Product A",
            "quantity": 2,
            "unit_price": 25.00,
            "subtotal": 50.00
        }
    ],
    "client": {
        "id": 1,
        "name": "Admin User",
        "role": "admin",
        "client_id": 0
    },
    "user": {
        "id": 2,
        "name": "Staff User",
        "role": "staff",
        "client_id": 1
    },
    "created_at": "2025-10-22 14:30:00",
    "updated_at": "2025-10-22 14:30:00"
}
```

## Business Logic

### Order Creation Flow
1. **User Authentication**: User logs in and gets token
2. **Order Creation**: User creates order via API
3. **Auto-Assignment**: System automatically assigns:
   - `client_id` = `auth()->user()->getEffectiveClientId()`
   - `user_id` = `auth()->id()`
4. **Multi-Tenancy**: Order belongs to the client
5. **Audit Trail**: Order tracks who created it

### Access Control
- **Admin**: Can see all orders for their client (`client_id = admin_id`)
- **Staff**: Can see all orders for their client (`client_id = admin_id`)
- **Both**: Can see who created each order (`user_id`)

## Use Cases

### 1. Multi-Tenancy Data Isolation
```sql
-- Get all orders for a specific client
SELECT * FROM orders WHERE client_id = 1;
```

### 2. Audit Trail Reporting
```sql
-- Get all orders created by a specific user
SELECT * FROM orders WHERE user_id = 2;
```

### 3. Combined Queries
```sql
-- Get orders for client 1 created by user 2
SELECT * FROM orders WHERE client_id = 1 AND user_id = 2;

-- Get orders for client 1 created by any staff member
SELECT o.*, u.name as creator_name 
FROM orders o 
JOIN users u ON o.user_id = u.id 
WHERE o.client_id = 1 AND u.role = 'staff';
```

## Model Relationships

### Order Model
```php
class Order extends Model
{
    // Multi-tenancy relationship
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id', 'id');
    }
    
    // Audit trail relationship
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
```

### User Model
```php
class User extends Model
{
    // Orders owned by this client (multi-tenancy)
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'client_id', 'id');
    }
    
    // Orders created by this user (audit trail)
    public function createdOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id', 'id');
    }
}
```

## Testing Examples

### Test Order Creation by Admin
```bash
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

**Expected Result**:
- `client_id` = admin's ID
- `user_id` = admin's ID

### Test Order Creation by Staff
```bash
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

**Expected Result**:
- `client_id` = admin's ID (same as admin)
- `user_id` = staff's ID (different from admin)

## Benefits

1. **Complete Traceability**: Know exactly who created each order
2. **Multi-Tenancy**: Proper data isolation by client
3. **Audit Compliance**: Meet enterprise audit requirements
4. **Reporting**: Generate reports by creator or client
5. **Accountability**: Track user actions for security
6. **Flexibility**: Support complex business scenarios

## Security Considerations

- **Data Isolation**: Users can only access orders for their client
- **Audit Trail**: All order creation is tracked
- **Foreign Key Constraints**: Ensure data integrity
- **Indexes**: Optimize query performance
- **Cascade Deletion**: Maintain referential integrity

This implementation provides enterprise-level audit trail functionality while maintaining the multi-tenancy architecture, making it perfect for a technical challenge demonstration!
