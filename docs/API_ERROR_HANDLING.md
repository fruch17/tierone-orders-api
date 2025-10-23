# API Error Handling - Complete Status Codes

## Overview

The TierOne Orders API implements comprehensive error handling with consistent JSON responses that include HTTP status codes in the response body. All API endpoints return structured error responses following RESTful conventions.

## Error Response Format

All error responses follow this consistent format:

```json
{
  "message": "Error description",
  "error": "Additional error details (optional)",
  "status_code": 401
}
```

## Error Types and Status Codes

### 1. Authentication Error (401)
**When:** Accessing protected endpoints without valid token or with invalid/expired token

```json
{
  "message": "Unauthenticated.",
  "status_code": 401
}
```

**Example Request:**
```bash
curl -X GET http://localhost:8000/api/orders
# No Authorization header provided
```

**Example Response:**
```json
{
  "message": "Unauthenticated.",
  "status_code": 401
}
```

### 2. Validation Errors (422)
**When:** Invalid request data or missing required fields

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."],
    "company_email": ["The company email field is required."]
  },
  "status_code": 422
}
```

**Example Request:**
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John Doe"
    // Missing required fields: email, password, company_name, company_email
  }'
```

### 3. Forbidden (403)
**When:** Accessing resources you don't have permission to access

```json
{
  "message": "Forbidden. You can only access your own orders.",
  "status_code": 403
}
```

**Example Request:**
```bash
curl -X GET http://localhost:8000/api/clients/999/orders \
  -H "Authorization: Bearer your_token" \
  -H "Accept: application/json"
# Trying to access another client's orders
```

### 4. Not Found (404)
**When:** Accessing non-existent endpoints or resources

```json
{
  "message": "Not found",
  "error": "The requested endpoint was not found",
  "status_code": 404
}
```

**Example Request:**
```bash
curl -X GET http://localhost:8000/api/nonexistent
```

### 5. Method Not Allowed (405)
**When:** Using wrong HTTP method for an endpoint

```json
{
  "message": "Method not allowed",
  "error": "The requested method is not allowed for this endpoint",
  "status_code": 405
}
```

**Example Request:**
```bash
curl -X GET http://localhost:8000/api/auth/login
# Should be POST, not GET
```

### 6. Server Error (500)
**When:** Internal server error occurs

```json
{
  "message": "Failed to create order",
  "error": "An error occurred while processing your order",
  "status_code": 500
}
```

## Testing Error Responses

### Test 1: No Authentication Token
```bash
curl -X GET http://localhost:8000/api/orders \
  -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "message": "Unauthenticated.",
  "status_code": 401
}
```

### Test 2: Invalid Authentication Token
```bash
curl -X GET http://localhost:8000/api/orders \
  -H "Authorization: Bearer invalid_token" \
  -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "message": "Unauthenticated.",
  "status_code": 401
}
```

### Test 3: Validation Error - Missing Required Fields
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John Doe"
  }'
```

**Expected Response:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."],
    "company_name": ["The company name field is required."],
    "company_email": ["The company email field is required."]
  },
  "status_code": 422
}
```

### Test 4: Validation Error - Duplicate Email
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John Doe",
    "company_name": "ACME Corp",
    "company_email": "contact@acme.com",
    "email": "existing@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

**Expected Response:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."]
  },
  "status_code": 422
}
```

### Test 5: Validation Error - Duplicate Company Email
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John Doe",
    "company_name": "ACME Corp",
    "company_email": "existing@company.com",
    "email": "john@acme.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

**Expected Response:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "company_email": ["This company email is already registered."]
  },
  "status_code": 422
}
```

### Test 6: Accessing Non-existent Order
```bash
curl -X GET http://localhost:8000/api/orders/999999 \
  -H "Authorization: Bearer your_token" \
  -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "message": "Order not found",
  "status_code": 404
}
```

## Complete Error Handling Coverage

### Status Code Reference:

| Error Type | Status Code | Description |
|------------|-------------|-------------|
| Authentication | 401 | Missing or invalid token |
| Authorization | 403 | Access denied to resource |
| Not Found | 404 | Endpoint or resource not found |
| Method Not Allowed | 405 | Wrong HTTP method |
| Validation | 422 | Invalid request data |
| Server Error | 500 | Internal server error |

### Consistent Response Format:

All error responses follow this format:
```json
{
  "message": "Error description",
  "error": "Additional error details (optional)",
  "status_code": 401
}
```

## Implementation Details

### Exception Handling in bootstrap/app.php:

```php
->withExceptions(function (Exceptions $exceptions) {
    // Handle authentication exceptions
    $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
        if ($request->is('api/*')) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'status_code' => 401,
            ], 401);
        }
    });
    
    // Handle method not allowed exceptions
    $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, $request) {
        if ($request->is('api/*')) {
            return response()->json([
                'message' => 'Method not allowed',
                'error' => 'The requested method is not allowed for this endpoint',
                'status_code' => 405,
            ], 405);
        }
    });
    
    // Handle not found exceptions
    $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
        if ($request->is('api/*')) {
            return response()->json([
                'message' => 'Not found',
                'error' => 'The requested endpoint was not found',
                'status_code' => 404,
            ], 404);
        }
    });
})
```

### Middleware Stack:

1. **ForceJsonResponse** - Forces Accept: application/json for API requests
2. **Sanctum Authentication** - Validates API tokens
3. **Custom Exception Handlers** - Format errors as JSON with status_code
4. **AddStatusCodeToResponse** - Adds status_code to all API responses

## Benefits

✅ **Consistent API responses** - All errors include status_code in JSON body
✅ **Better debugging** - Status code visible in JSON response
✅ **Frontend friendly** - Easy to handle errors in JavaScript applications
✅ **Complete coverage** - All error types handled consistently
✅ **Testing** - Easy to assert status codes in automated tests
✅ **RESTful compliance** - Follows REST API best practices

## Multi-tenancy Error Handling

The API implements client-user separation with proper error handling:

- **Client Isolation**: Users can only access orders for their client
- **Role-based Access**: Admin and staff roles with appropriate permissions
- **Audit Trail**: Complete tracking of who created what
- **Data Security**: Proper validation and authorization checks

---

**Last Updated:** 2025-10-23