# Order Management API Documentation

## Base URL
```
http://localhost:8000/api
```

## Authentication
All order endpoints require authentication via Bearer token obtained from `/api/auth/login`.

**Header Required:**
```
Authorization: Bearer {token}
```

---

## Endpoints

### 1. Create Order (Protected)

**Endpoint:** `POST /api/orders`

**Description:** Create a new order with one or more items

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "tax": 15.50,
  "notes": "Urgent delivery to warehouse 5",
  "items": [
    {
      "product_name": "Laptop Dell XPS 13",
      "quantity": 2,
      "unit_price": 1200.00
    },
    {
      "product_name": "Wireless Mouse",
      "quantity": 5,
      "unit_price": 25.99
    }
  ]
}
```

**Success Response (201):**
```json
{
  "message": "Order created successfully",
  "order": {
    "id": 1,
    "order_number": "ORD-20251022-XY7A",
    "client_id": 1,
    "user_id": 1,
    "subtotal": 2529.95,
    "tax": 15.50,
    "total": 2545.45,
    "notes": "Urgent delivery to warehouse 5",
    "items": [
      {
        "id": 1,
        "product_name": "Laptop Dell XPS 13",
        "quantity": 2,
        "unit_price": 1200.00,
        "formatted_unit_price": "$1,200.00",
        "subtotal": 2400.00,
        "formatted_subtotal": "$2,400.00",
        "created_at": "2025-10-22 12:00:00",
        "updated_at": "2025-10-22 12:00:00"
      },
      {
        "id": 2,
        "product_name": "Wireless Mouse",
        "quantity": 5,
        "unit_price": 25.99,
        "formatted_unit_price": "$25.99",
        "subtotal": 129.95,
        "formatted_subtotal": "$129.95",
        "created_at": "2025-10-22 12:00:00",
        "updated_at": "2025-10-22 12:00:00"
      }
    ],
    "client": {
      "id": 1,
      "company_name": "TierOne Corp",
      "company_email": "contact@tierone.com",
      "created_at": "2025-10-22 12:00:00",
      "updated_at": "2025-10-22 12:00:00"
    },
    "user": {
      "id": 1,
      "name": "John Admin",
      "email": "admin@tierone.com",
      "role": "admin",
      "client_id": 1,
      "created_at": "2025-10-22 12:00:00",
      "updated_at": "2025-10-22 12:00:00"
    },
    "created_at": "2025-10-22 12:00:00",
    "updated_at": "2025-10-22 12:00:00"
  },
  "status_code": 201
}
```

**Validation Errors (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "tax": [
      "The tax field is required."
    ],
    "items": [
      "At least one item is required."
    ],
    "items.0.product_name": [
      "Product name is required for each item."
    ],
    "items.0.quantity": [
      "Quantity must be at least 1."
    ]
  },
  "status_code": 422
}
```

---

### 2. Get Single Order (Protected)

**Endpoint:** `GET /api/orders/{id}`

**Description:** Retrieve a single order and its items (only if it belongs to authenticated user)

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Success Response (200):**
```json
{
  "order": {
    "id": 1,
    "order_number": "ORD-20251022-XY7A",
    "client_id": 1,
    "user_id": 1,
    "subtotal": 2529.95,
    "tax": 15.50,
    "total": 2545.45,
    "notes": "Urgent delivery to warehouse 5",
    "items": [
      {
        "id": 1,
        "product_name": "Laptop Dell XPS 13",
        "quantity": 2,
        "unit_price": 1200.00,
        "formatted_unit_price": "$1,200.00",
        "subtotal": 2400.00,
        "formatted_subtotal": "$2,400.00",
        "created_at": "2025-10-22 12:00:00",
        "updated_at": "2025-10-22 12:00:00"
      }
    ],
    "client": {
      "id": 1,
      "company_name": "TierOne Corp",
      "company_email": "contact@tierone.com",
      "created_at": "2025-10-22 12:00:00",
      "updated_at": "2025-10-22 12:00:00"
    },
    "user": {
      "id": 1,
      "name": "John Admin",
      "email": "admin@tierone.com",
      "role": "admin",
      "client_id": 1,
      "created_at": "2025-10-22 12:00:00",
      "updated_at": "2025-10-22 12:00:00"
    },
    "created_at": "2025-10-22 12:00:00",
    "updated_at": "2025-10-22 12:00:00"
  },
  "status_code": 200
}
```

**Not Found Response (404):**
```json
{
  "message": "Order not found",
  "error": "The requested order was not found or you do not have access to it",
  "status_code": 404
}
```

---

### 3. List All Orders (Protected)

**Endpoint:** `GET /api/orders`

**Description:** Retrieve all orders for the authenticated user

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Success Response (200):**
```json
{
  "orders": [
    {
      "id": 1,
      "order_number": "ORD-20251022-XY7A",
      "client_id": 1,
      "user_id": 1,
      "subtotal": 2529.95,
      "tax": 15.50,
      "total": 2545.45,
      "notes": "Urgent delivery to warehouse 5",
      "items": [...],
      "client": {
        "id": 1,
        "company_name": "TierOne Corp",
        "company_email": "contact@tierone.com",
        "created_at": "2025-10-22 12:00:00",
        "updated_at": "2025-10-22 12:00:00"
      },
      "user": {
        "id": 1,
        "name": "John Admin",
        "email": "admin@tierone.com",
        "role": "admin",
        "client_id": 1,
        "created_at": "2025-10-22 12:00:00",
        "updated_at": "2025-10-22 12:00:00"
      },
      "created_at": "2025-10-22 12:00:00",
      "updated_at": "2025-10-22 12:00:00"
    },
    {
      "id": 2,
      "order_number": "ORD-20251021-AB3C",
      "client_id": 1,
      "user_id": 2,
      "subtotal": 1200.00,
      "tax": 10.00,
      "total": 1210.00,
      "notes": null,
      "items": [...],
      "client": {
        "id": 1,
        "company_name": "TierOne Corp",
        "company_email": "contact@tierone.com",
        "created_at": "2025-10-22 12:00:00",
        "updated_at": "2025-10-22 12:00:00"
      },
      "user": {
        "id": 2,
        "name": "Jane Staff",
        "email": "staff@tierone.com",
        "role": "staff",
        "client_id": 1,
        "created_at": "2025-10-22 12:00:00",
        "updated_at": "2025-10-22 12:00:00"
      },
      "created_at": "2025-10-21 10:30:00",
      "updated_at": "2025-10-21 10:30:00"
    }
  ],
  "count": 2,
  "status_code": 200
}
```

---

### 4. Get Client Orders (Protected)

**Endpoint:** `GET /api/clients/{id}/orders`

**Description:** Retrieve all orders for a specific client. Both admin and staff users can access orders for their client.

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Success Response (200):**
```json
{
  "client": {
    "id": 1,
    "company_name": "TierOne Corp",
    "company_email": "contact@tierone.com",
    "created_at": "2025-10-22 12:00:00",
    "updated_at": "2025-10-22 12:00:00"
  },
  "orders": [
    {
      "id": 1,
      "order_number": "ORD-20251022-XY7A",
      "client_id": 1,
      "user_id": 1,
      "subtotal": 2529.95,
      "tax": 15.50,
      "total": 2545.45,
      "notes": "Urgent delivery to warehouse 5",
      "items": [...],
      "client": {
        "id": 1,
        "company_name": "TierOne Corp",
        "company_email": "contact@tierone.com",
        "created_at": "2025-10-22 12:00:00",
        "updated_at": "2025-10-22 12:00:00"
      },
      "user": {
        "id": 1,
        "name": "John Admin",
        "email": "admin@tierone.com",
        "role": "admin",
        "client_id": 1,
        "created_at": "2025-10-22 12:00:00",
        "updated_at": "2025-10-22 12:00:00"
      },
      "created_at": "2025-10-22 12:00:00",
      "updated_at": "2025-10-22 12:00:00"
    }
  ],
  "count": 1,
  "status_code": 200
}
```

**Forbidden Response (403):**
```json
{
  "message": "Forbidden. You can only access your own orders.",
  "status_code": 403
}
```

**Client Not Found Response (404):**
```json
{
  "message": "Client not found",
  "error": "The requested client was not found",
  "status_code": 404
}
```

---

## Testing Examples

### Create Order
```bash
curl -X POST http://localhost:8000/api/orders \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "tax": 15.50,
    "notes": "Urgent delivery",
    "items": [
      {
        "product_name": "Laptop",
        "quantity": 2,
        "unit_price": 1200.00
      }
    ]
  }'
```

### Get Single Order
```bash
curl -X GET http://localhost:8000/api/orders/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### List All Orders
```bash
curl -X GET http://localhost:8000/api/orders \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### Get Client Orders
```bash
curl -X GET http://localhost:8000/api/clients/1/orders \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

---

## Business Logic Features

### ✅ Automatic Calculations
- **Subtotal:** Automatically calculated as sum of (quantity × unit_price) for all items
- **Total:** Automatically calculated as subtotal + tax
- **Order Number:** Auto-generated in format `ORD-YYYYMMDD-XXXX`

### ✅ Multi-Tenancy Security
- **Client Isolation:** Orders are scoped by `client_id` for proper multi-tenancy
- **User Tracking:** Each order tracks both `client_id` (for multi-tenancy) and `user_id` (for audit trail)
- **Role-based Access:** Admin and staff users can access orders for their client
- **Access Control:** All endpoints enforce client-based access control
- **Data Separation:** Complete isolation between different clients

### ✅ Background Processing
- **Invoice Generation:** When an order is created, `GenerateInvoiceJob` is dispatched
- **Asynchronous:** Invoice generation happens in background without blocking response
- **Logging:** Job progress and results are logged for monitoring

### ✅ Data Validation
- **Input Validation:** Comprehensive validation for all order data
- **Business Rules:** Enforces minimum quantities, valid prices, etc.
- **Error Messages:** Clear, user-friendly error messages

---

## Error Handling

### Authentication Errors (401)
```json
{
  "message": "Unauthenticated.",
  "status_code": 401
}
```

### Validation Errors (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": ["Error message"]
  },
  "status_code": 422
}
```

### Server Errors (500)
```json
{
  "message": "Failed to create order",
  "error": "An error occurred while processing your order",
  "status_code": 500
}
```

---

## Notes

- All monetary values are handled as decimals with 2 decimal places
- Order numbers are unique and auto-generated
- Items are automatically linked to their parent order
- Background job for invoice generation is triggered on order creation
- All timestamps are in `Y-m-d H:i:s` format
- **Multi-tenancy:** Orders are scoped by `client_id` for proper data isolation
- **Audit Trail:** Each order tracks `user_id` to know who created it
- **Client-User Model:** Admin and staff users belong to clients and share access to client orders
- **Data Relationships:** Orders include both `client` and `user` information in responses

---

**Last Updated:** 2025-10-22
**Status:** ✅ Order Management APIs fully implemented

