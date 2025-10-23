# API Testing Guide - JSON Responses Fixed

## ✅ Problem Solved

The issue where API endpoints returned HTML error pages instead of JSON has been fixed.

## Changes Made

### 1. Middleware Configuration
- Added `ForceJsonResponse` middleware to API routes
- Forces `Accept: application/json` header for all API requests
- Ensures consistent JSON responses

### 2. Exception Handling
- Configured custom exception handlers for API routes
- Method Not Allowed (405) now returns JSON
- Not Found (404) now returns JSON
- All API errors now return JSON format

## Testing Examples

### ✅ Correct API Usage

#### Register (POST)
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John Doe",
    "company_name": "ACME Logistics",
    "email": "john@acme.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

#### Login (POST)
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "john@acme.com",
    "password": "password123"
  }'
```

### ❌ Wrong Method (Now Returns JSON)

#### Wrong: GET /api/auth/login
```bash
curl -X GET http://localhost:8000/api/auth/login \
  -H "Accept: application/json"
```

**Response (JSON):**
```json
{
  "message": "Method not allowed",
  "error": "The requested method is not allowed for this endpoint"
}
```

### ❌ Wrong Endpoint (Now Returns JSON)

#### Wrong: GET /api/nonexistent
```bash
curl -X GET http://localhost:8000/api/nonexistent \
  -H "Accept: application/json"
```

**Response (JSON):**
```json
{
  "message": "Not found",
  "error": "The requested endpoint was not found"
}
```

## Expected JSON Responses

### Success Responses
```json
{
  "message": "User registered successfully",
  "user": { ... },
  "token": "...",
  "token_type": "Bearer"
}
```

### Error Responses
```json
{
  "message": "Method not allowed",
  "error": "The requested method is not allowed for this endpoint"
}
```

```json
{
  "message": "Invalid credentials"
}
```

```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

## Testing Checklist

- ✅ All API endpoints return JSON
- ✅ Method Not Allowed (405) returns JSON
- ✅ Not Found (404) returns JSON
- ✅ Validation errors return JSON
- ✅ Authentication errors return JSON
- ✅ Success responses return JSON

## Postman Configuration

When testing with Postman:

1. **Headers Tab:**
   - `Content-Type: application/json`
   - `Accept: application/json`

2. **Body Tab (for POST requests):**
   - Select "raw"
   - Select "JSON" from dropdown
   - Enter JSON data

3. **Authorization Tab (for protected routes):**
   - Type: Bearer Token
   - Token: Your API token

## Common Issues Fixed

### Before (HTML Response)
```html
<!DOCTYPE html>
<html>
<head>
    <title>Method Not Allowed</title>
</head>
<body>
    <h1>Method Not Allowed</h1>
    <p>Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException</p>
</body>
</html>
```

### After (JSON Response)
```json
{
  "message": "Method not allowed",
  "error": "The requested method is not allowed for this endpoint"
}
```

## Next Steps

Now that JSON responses are working correctly, you can:

1. Test all authentication endpoints
2. Verify error handling
3. Proceed with Order API implementation
4. Write comprehensive API tests

---

**Status:** ✅ JSON responses fixed for all API endpoints
**Last Updated:** 2025-10-22

