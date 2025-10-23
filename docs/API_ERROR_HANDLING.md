# API Error Handling - Complete Status Codes

## ✅ Authentication Errors Fixed

All API endpoints now return consistent JSON responses with `status_code` field, including authentication errors.

## Updated Error Responses

### 1. Authentication Error (401)
**When:** Accessing protected endpoints without valid token

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

### 2. Method Not Allowed (405)
**When:** Using wrong HTTP method

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

### 3. Not Found (404)
**When:** Accessing non-existent endpoint

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

### 4. Validation Errors (422)
**When:** Invalid request data

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": ["Error message"]
  },
  "status_code": 422
}
```

### 5. Forbidden (403)
**When:** Accessing resources you don't own

```json
{
  "message": "Forbidden. You can only access your own orders.",
  "status_code": 403
}
```

**Example Request:**
```bash
curl -X GET http://localhost:8000/api/clients/999/orders
# Trying to access another user's orders
```

### 6. Server Error (500)
**When:** Internal server error

```json
{
  "message": "Failed to create order",
  "error": "An error occurred while processing your order",
  "status_code": 500
}
```

## Testing Authentication Errors

### Test 1: No Token
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

### Test 2: Invalid Token
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

### Test 3: Expired Token
```bash
curl -X GET http://localhost:8000/api/orders \
  -H "Authorization: Bearer expired_token" \
  -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "message": "Unauthenticated.",
  "status_code": 401
}
```

## Complete Error Handling Coverage

### ✅ All Error Types Now Include Status Code:

| Error Type | Status Code | Description |
|------------|-------------|-------------|
| Authentication | 401 | Missing or invalid token |
| Authorization | 403 | Access denied to resource |
| Not Found | 404 | Endpoint or resource not found |
| Method Not Allowed | 405 | Wrong HTTP method |
| Validation | 422 | Invalid request data |
| Server Error | 500 | Internal server error |

### ✅ Consistent Response Format:

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
    
    // Handle other exceptions...
})
```

### Middleware Stack:

1. **ForceJsonResponse** - Forces Accept: application/json
2. **Sanctum Authentication** - Validates tokens
3. **Custom Exception Handlers** - Format errors as JSON with status_code
4. **AddStatusCodeToResponse** - Adds status_code to all responses

## Benefits

✅ **Consistent API responses** - All errors include status_code
✅ **Better debugging** - Status code visible in JSON body
✅ **Frontend friendly** - Easy to handle errors in JavaScript
✅ **Complete coverage** - All error types handled consistently
✅ **Testing** - Easy to assert status codes in tests

---

**Status:** ✅ All API errors now include status_code field
**Last Updated:** 2025-10-22

