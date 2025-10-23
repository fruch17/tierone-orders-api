# API Responses with Status Codes - Updated

## ✅ Status Code Added to All Responses

All API responses now include the `status_code` field in the JSON body for better debugging and consistency.

## Updated Response Examples

### 1. Register Success (201)
```json
{
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "name": "John Doe",
    "company_name": "ACME Logistics",
    "email": "john@acme.com",
    "email_verified_at": null,
    "created_at": "2025-10-22 12:00:00",
    "updated_at": "2025-10-22 12:00:00"
  },
  "token": "1|abc123def456...",
  "token_type": "Bearer",
  "status_code": 201
}
```

### 2. Login Success (200)
```json
{
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "John Doe",
    "company_name": "ACME Logistics",
    "email": "john@acme.com",
    "email_verified_at": null,
    "created_at": "2025-10-22 12:00:00",
    "updated_at": "2025-10-22 12:00:00"
  },
  "token": "2|xyz789ghi012...",
  "token_type": "Bearer",
  "status_code": 200
}
```

### 3. Login Error (401)
```json
{
  "message": "Invalid credentials",
  "status_code": 401
}
```

### 4. Method Not Allowed (405)
```json
{
  "message": "Method not allowed",
  "error": "The requested method is not allowed for this endpoint",
  "status_code": 405
}
```

### 5. Not Found (404)
```json
{
  "message": "Not found",
  "error": "The requested endpoint was not found",
  "status_code": 404
}
```

### 6. Logout Success (200)
```json
{
  "message": "Logged out successfully",
  "status_code": 200
}
```

### 7. Get Current User (200)
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "company_name": "ACME Logistics",
    "email": "john@acme.com",
    "email_verified_at": null,
    "created_at": "2025-10-22 12:00:00",
    "updated_at": "2025-10-22 12:00:00"
  },
  "status_code": 200
}
```

### 8. Validation Errors (422)
```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": [
      "The email has already been taken."
    ]
  },
  "status_code": 422
}
```

## Testing Examples

### Test Method Not Allowed
```bash
curl -X GET http://localhost:8000/api/auth/login \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "message": "Method not allowed",
  "error": "The requested method is not allowed for this endpoint",
  "status_code": 405
}
```

### Test Login Success
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "john@acme.com",
    "password": "password123"
  }'
```

**Response:**
```json
{
  "message": "Login successful",
  "user": { ... },
  "token": "...",
  "token_type": "Bearer",
  "status_code": 200
}
```

## Implementation Details

### Middleware Stack
1. **ForceJsonResponse** - Forces Accept: application/json
2. **AddStatusCodeToResponse** - Adds status_code to JSON responses
3. **Sanctum Middleware** - Handles authentication

### Automatic Status Code Addition
- All JSON responses automatically include `status_code`
- Status code matches the HTTP response code
- Applied to all `/api/*` routes
- Works for both success and error responses

## Benefits

✅ **Consistent API responses** - All responses include status_code
✅ **Better debugging** - Status code visible in JSON body
✅ **Frontend friendly** - Easy to check status in JavaScript
✅ **API documentation** - Clear status codes in examples
✅ **Testing** - Easy to assert status codes in tests

---

**Status:** ✅ All API responses now include status_code field
**Last Updated:** 2025-10-22

