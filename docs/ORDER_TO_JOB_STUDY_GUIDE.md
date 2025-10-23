# Order Creation to Job Execution - Practical Study Guide

## 🎯 Hands-On Learning Commands

This guide provides practical commands to study and understand the complete order creation to job execution process.

---

## 📋 Prerequisites

### **1. Verify Environment Setup**
```bash
# Check Laravel version
php artisan --version

# Check database connection
php artisan migrate:status

# Check queue configuration
php artisan queue:work --help
```

### **2. Verify Database Tables**
```sql
-- Check if all required tables exist
SHOW TABLES;

-- Expected tables:
-- - users
-- - orders  
-- - order_items
-- - jobs
-- - failed_jobs
-- - personal_access_tokens
```

---

## 🚀 Step-by-Step Testing Process

### **Step 1: Create a Test User**
```bash
# Register a new user
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Test User",
    "company_name": "Test Company",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

**Expected Response:**
```json
{
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "name": "Test User",
    "company_name": "Test Company",
    "email": "test@example.com"
  },
  "token": "1|abc123def456...",
  "token_type": "Bearer",
  "status_code": 201
}
```

**Save the token for next steps!**

### **Step 2: Monitor Database Before Order Creation**
```sql
-- Check initial state
SELECT COUNT(*) as orders_count FROM orders;
SELECT COUNT(*) as order_items_count FROM order_items;
SELECT COUNT(*) as jobs_count FROM jobs;
```

**Expected Result:**
```
orders_count: 0
order_items_count: 0
jobs_count: 0
```

### **Step 3: Create an Order**
```bash
# Create order with items (replace TOKEN with actual token)
curl -X POST http://localhost:8000/api/orders \
  -H "Authorization: Bearer TOKEN_FROM_STEP_1" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "tax": 15.50,
    "notes": "Study test order",
    "items": [
      {
        "product_name": "Study Laptop",
        "quantity": 2,
        "unit_price": 1200.00
      },
      {
        "product_name": "Study Mouse",
        "quantity": 3,
        "unit_price": 25.99
      }
    ]
  }'
```

**Expected Response:**
```json
{
  "message": "Order created successfully",
  "order": {
    "id": 1,
    "order_number": "ORD-20251022-XY7A",
    "user_id": 1,
    "subtotal": 2451.97,
    "tax": 15.50,
    "total": 2467.47,
    "notes": "Study test order",
    "items": [
      {
        "id": 1,
        "product_name": "Study Laptop",
        "quantity": 2,
        "unit_price": 1200.00,
        "subtotal": 2400.00
      },
      {
        "id": 2,
        "product_name": "Study Mouse",
        "quantity": 3,
        "unit_price": 25.99,
        "subtotal": 51.97
      }
    ]
  },
  "status_code": 201
}
```

### **Step 4: Verify Database After Order Creation**
```sql
-- Check orders table
SELECT * FROM orders;

-- Check order_items table
SELECT * FROM order_items;

-- Check jobs table
SELECT id, queue, attempts, reserved_at, available_at, created_at FROM jobs;
```

**Expected Results:**
```sql
-- orders table
id: 1, user_id: 1, order_number: 'ORD-20251022-XY7A', subtotal: 2451.97, tax: 15.50, total: 2467.47

-- order_items table
id: 1, order_id: 1, product_name: 'Study Laptop', quantity: 2, unit_price: 1200.00, subtotal: 2400.00
id: 2, order_id: 1, product_name: 'Study Mouse', quantity: 3, unit_price: 25.99, subtotal: 51.97

-- jobs table
id: 1, queue: 'default', attempts: 0, reserved_at: NULL, available_at: NOW(), created_at: NOW()
```

### **Step 5: Examine Job Payload**
```sql
-- Get detailed job information
SELECT 
    id,
    queue,
    JSON_EXTRACT(payload, '$.uuid') as job_uuid,
    JSON_EXTRACT(payload, '$.displayName') as job_class,
    JSON_EXTRACT(payload, '$.maxTries') as max_tries,
    JSON_EXTRACT(payload, '$.timeout') as timeout_seconds,
    attempts,
    reserved_at,
    available_at,
    created_at
FROM jobs 
WHERE id = 1;
```

**Expected Result:**
```sql
job_uuid: "9131c9ae-38ee-43a1-af7b-1fe8ebf3009e"
job_class: "App\\Jobs\\GenerateInvoiceJob"
max_tries: 3
timeout_seconds: 60
attempts: 0
reserved_at: NULL
```

### **Step 6: Monitor Logs Before Job Processing**
```bash
# Monitor Laravel logs
tail -f storage/logs/laravel.log
```

**Expected:** No invoice generation logs yet.

### **Step 7: Process the Job**
```bash
# Process the job once
php artisan queue:work --once
```

**Expected Output:**
```
[2025-10-22 12:00:00][9131c9ae-38ee-43a1-af7b-1fe8ebf3009e] Processing: App\Jobs\GenerateInvoiceJob
[2025-10-22 12:00:00][9131c9ae-38ee-43a1-af7b-1fe8ebf3009e] Processed:  App\Jobs\GenerateInvoiceJob
```

### **Step 8: Verify Job Completion**
```sql
-- Check jobs table (should be empty)
SELECT COUNT(*) as jobs_count FROM jobs;

-- Check logs for invoice generation messages
```

**Expected Results:**
```sql
jobs_count: 0
```

**Logs should show:**
```
[2025-10-22 12:00:00] local.INFO: Invoice generation process completed {"order_id":1,"invoice_number":"INV-ORD-20251022-XY7A","amount":2467.47}
[2025-10-22 12:00:00] local.INFO: Invoice generated successfully {"order_id":1,"order_number":"ORD-20251022-XY7A","total":2467.47,"user_id":1}
```

---

## 🔍 Advanced Study Commands

### **Study Job Payload Structure**
```sql
-- Extract and format job payload
SELECT 
    JSON_PRETTY(payload) as formatted_payload
FROM jobs 
WHERE id = 1;
```

### **Study Order Calculations**
```sql
-- Verify order calculations
SELECT 
    o.id,
    o.order_number,
    o.subtotal,
    o.tax,
    o.total,
    SUM(oi.subtotal) as calculated_subtotal,
    o.subtotal + o.tax as calculated_total
FROM orders o
JOIN order_items oi ON o.id = oi.order_id
GROUP BY o.id;
```

### **Study Multi-Tenancy**
```sql
-- Verify user isolation
SELECT 
    u.id as user_id,
    u.name,
    u.company_name,
    COUNT(o.id) as order_count
FROM users u
LEFT JOIN orders o ON u.id = o.user_id
GROUP BY u.id;
```

---

## 🧪 Error Testing Scenarios

### **Test 1: Invalid Authentication**
```bash
# Try to create order without token
curl -X POST http://localhost:8000/api/orders \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"tax": 10, "items": []}'
```

**Expected Response:**
```json
{
  "message": "Unauthenticated.",
  "status_code": 401
}
```

### **Test 2: Validation Errors**
```bash
# Try to create order with invalid data
curl -X POST http://localhost:8000/api/orders \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "tax": -10,
    "items": []
  }'
```

**Expected Response:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "tax": ["The tax must be at least 0."],
    "items": ["At least one item is required."]
  },
  "status_code": 422
}
```

### **Test 3: Job Failure Simulation**
```bash
# Create order and then simulate job failure
# 1. Create order (creates job)
# 2. Stop database connection
# 3. Try to process job
# 4. Check failed_jobs table
```

---

## 📊 Performance Monitoring

### **Monitor Response Times**
```bash
# Time the order creation request
time curl -X POST http://localhost:8000/api/orders \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"tax": 10, "items": [{"product_name": "Test", "quantity": 1, "unit_price": 100}]}'
```

### **Monitor Job Processing Time**
```bash
# Time job processing
time php artisan queue:work --once
```

### **Monitor Database Performance**
```sql
-- Check query performance
EXPLAIN SELECT * FROM orders WHERE user_id = 1;
EXPLAIN SELECT * FROM order_items WHERE order_id = 1;
EXPLAIN SELECT * FROM jobs WHERE queue = 'default';
```

---

## 🔧 Debugging Commands

### **Check Queue Status**
```bash
# Check queue configuration
php artisan queue:monitor

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### **Check Database State**
```sql
-- Check all tables
SELECT 
    'orders' as table_name, COUNT(*) as count FROM orders
UNION ALL
SELECT 
    'order_items' as table_name, COUNT(*) as count FROM order_items
UNION ALL
SELECT 
    'jobs' as table_name, COUNT(*) as count FROM jobs
UNION ALL
SELECT 
    'failed_jobs' as table_name, COUNT(*) as count FROM failed_jobs;
```

### **Check Logs**
```bash
# View recent logs
tail -n 50 storage/logs/laravel.log

# Search for specific patterns
grep "Invoice generated" storage/logs/laravel.log
grep "Order created" storage/logs/laravel.log
grep "ERROR" storage/logs/laravel.log
```

---

## 📚 Study Questions & Answers

### **Q1: Why is the job deleted after completion?**
**A:** Laravel automatically deletes completed jobs from the `jobs` table to prevent table bloat and improve performance. Failed jobs are moved to `failed_jobs` table for debugging.

### **Q2: How does multi-tenancy work in this system?**
**A:** Each order is automatically assigned the `user_id` of the authenticated user. All queries are scoped by `user_id` to ensure data isolation between users.

### **Q3: What happens if the job fails?**
**A:** The job will be retried up to 3 times (configured in `$tries = 3`). After 3 failures, it's moved to `failed_jobs` table and the `failed()` method is called.

### **Q4: Why use database transactions?**
**A:** Transactions ensure that either all operations (order + items + job dispatch) succeed, or none do. This prevents partial data states if something fails.

### **Q5: How are order totals calculated?**
**A:** OrderItem model automatically calculates `subtotal = quantity × unit_price` on creation. Order model calculates `total = subtotal + tax` when items are saved.

---

## 🎯 Key Learning Outcomes

After completing this study guide, you should understand:

1. ✅ **Complete Request Flow:** From HTTP request to database operations
2. ✅ **Job Dispatch Process:** How background jobs are created and queued
3. ✅ **Database Transactions:** How atomicity is maintained
4. ✅ **Multi-Tenancy:** How user isolation works
5. ✅ **Error Handling:** How errors are caught and handled
6. ✅ **Performance:** How async processing improves response times
7. ✅ **Monitoring:** How to debug and monitor the system

---

**Last Updated:** 2025-10-22
**Status:** ✅ Complete practical study guide

