# 🎯 Technical Interview Guide - Laravel

## 📋 Table of Contents
1. [SOLID Principles](#solid-principles)
2. [Multi-Tenant Systems](#multi-tenant-systems)
3. [Lazy Loading vs Eager Loading (N+1 Problem)](#lazy-loading-vs-eager-loading-n1-problem)
4. [Test-Driven Development (TDD)](#test-driven-development-tdd)
5. [Design Patterns in Laravel](#design-patterns-in-laravel)
6. [Routes and API Endpoints](#routes-and-api-endpoints)
7. [Middleware in Laravel](#middleware-in-laravel)
8. [Authentication: Sanctum vs JWT](#authentication-sanctum-vs-jwt)
9. [Laravel Nova and Alternatives](#laravel-nova-and-alternatives)
10. [Project FAQ](#project-faq)

---

## 🏗️ SOLID Principles

### **What are SOLID Principles?**

SOLID is an acronym for five object-oriented design principles that make code more maintainable, scalable, and easy to understand.

### **1. S - Single Responsibility Principle**

**Definition:** Each class should have only one reason to change.

**Example in the Project:**

```php
// ❌ BAD: Controller handling business logic
class OrderController extends Controller {
    public function store(Request $request) {
        $order = new Order();
        $order->client_id = auth()->user()->client_id;
        $order->total = 0;
        // ... complex calculations here
        $order->save();
    }
}

// ✅ GOOD: Controller only handles HTTP, Service handles logic
class OrderController extends Controller {
    public function __construct(private OrderService $orderService) {}
    
    public function store(StoreOrderRequest $request): JsonResponse {
        $order = $this->orderService->createOrder($request);
        return response()->json(['order' => $order], 201);
    }
}

// Service has the sole responsibility of handling order logic
class OrderService {
    public function createOrder(StoreOrderRequest $request): Order {
        return DB::transaction(function () use ($request) {
            $order = Order::create([...]);
            // ... business logic here
            return $order;
        });
    }
}
```

**Frequent Question:**
> **Q: Why did you separate business logic from the Controller?**  
> **A:** Because the Controller should only handle HTTP (request/response) and the Service should handle business logic. This allows reusing the logic in other contexts (console, jobs, etc.) and makes unit testing easier.

### **2. O - Open/Closed Principle**

**Definition:** Entities should be open for extension but closed for modification.

**Example in the Project:**

```php
// Model Order has a generable method that can be extended
class Order extends Model {
    public static function generateOrderNumber(): string {
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(4));
        return "ORD-{$date}-{$random}";
    }
}

// ✅ You can extend without modifying the original class
class CustomOrder extends Order {
    public static function generateOrderNumber(): string {
        return "CUSTOM-" . parent::generateOrderNumber();
    }
}
```

### **3. L - Liskov Substitution Principle**

**Definition:** Objects of a superclass should be replaceable by objects of their subclasses without breaking the application.

**Conceptual Example:**
```php
// All OrderRepository implementations must be interchangeable
interface OrderRepositoryInterface {
    public function create(array $data): Order;
}

class DatabaseOrderRepository implements OrderRepositoryInterface {
    public function create(array $data): Order {
        return Order::create($data);
    }
}

class CacheOrderRepository implements OrderRepositoryInterface {
    public function create(array $data): Order {
        // Different implementation, but same interface
    }
}
```

### **4. I - Interface Segregation Principle**

**Definition:** Classes should not be forced to implement interfaces they don't use.

**Example:**
```php
// ❌ BAD: Large interface with many methods
interface OrderInterface {
    public function create();
    public function update();
    public function delete();
    public function generateInvoice(); // Only some need this
}

// ✅ GOOD: Small and specific interfaces
interface OrderCrudInterface {
    public function create();
    public function update();
    public function delete();
}

interface InvoiceInterface {
    public function generateInvoice();
}
```

### **5. D - Dependency Inversion Principle**

**Definition:** Depend on abstractions, not on concretions.

**Example in the Project:**

```php
// ❌ BAD: Direct dependency on concrete implementation
class OrderController extends Controller {
    public function store(Request $request) {
        $order = Order::create($request->all()); // Depends directly
    }
}

// ✅ GOOD: Depends on abstraction (injected Service)
class OrderController extends Controller {
    public function __construct(private OrderService $orderService) {}
    
    public function store(StoreOrderRequest $request) {
        $order = $this->orderService->createOrder($request);
    }
}

// Laravel resolves the dependency automatically (Service Container)
```

**Frequent Question:**
> **Q: Why do you use dependency injection in the constructor?**  
> **A:** It allows Laravel to automatically inject dependencies, facilitates testing (you can mock the Service), and follows the dependency inversion principle.

---

## 🏢 Multi-Tenant Systems

### **What is a Multi-Tenant System?**

A multi-tenant system allows multiple clients (tenants) to share the same application and infrastructure while keeping their data completely separate.

### **Model Implemented in the Project: Single Database with Data Separation**

```sql
-- Table Structure
clients
├── id
├── name
└── ...

users
├── id
├── name
├── email
├── role (admin/staff)
├── client_id (FK to clients.id)
└── ...

orders
├── id
├── client_id (Multi-tenancy: which client it belongs to)
├── user_id (Audit trail: who created the order)
├── order_number
├── subtotal
├── tax
├── total
└── ...

order_items
├── id
├── order_id
├── product_name
├── quantity
├── unit_price
└── subtotal
```

### **Architecture: Client-User Separation**

**Key Concepts:**

#### **1. Client (Company/Customer)**
- Represents a company using the system
- Has multiple users (admin + staff)

#### **2. User Roles:**

**Admin (Administrator):**
- Is the owner of the company/client
- `client_id` points to their own record in `clients`
- Can manage staff
- Sees all their orders and those of their staff

**Staff (Employee):**
- Belongs to an admin (company)
- `client_id` points to the admin's `clients.id`
- Cannot manage other users
- Only sees orders from their client

### **How Multi-Tenancy Works**

#### **1. Scope to Filter by Client:**

```php
// In app/Models/Order.php
public function scopeForAuthClient($query) {
    if (auth()->check()) {
        $clientId = auth()->user()->getEffectiveClientId();
        return $query->where('client_id', $clientId);
    }
    return $query;
}

// Use in Service
class OrderService {
    public function getOrdersForAuthUser(): Collection {
        return Order::forAuthClient()  // Only orders from the user's client
                   ->with(['items', 'client', 'user'])
                   ->latest()
                   ->get();
    }
}
```

#### **2. Automatic Assignment of client_id:**

```php
// In app/Models/Order.php - Boot method
protected static function boot(): void {
    static::creating(function (Order $order) {
        if (auth()->check() && !$order->client_id) {
            $order->client_id = auth()->user()->getEffectiveClientId();
        }
        
        if (auth()->check() && !$order->user_id) {
            $order->user_id = auth()->id(); // Audit trail
        }
    });
}
```

#### **3. User Model - getEffectiveClientId() Method:**

```php
// app/Models/User.php
public function getEffectiveClientId(): int {
    return $this->client_id; // client_id is the same for admin and staff
}

// Both admin and staff have client_id pointing to the same company
// Admin: client_id = X (their own company)
// Staff: client_id = X (belongs to the same company as admin)
```

### **Practical Examples**

#### **Scenario 1: Create an Order**

```php
// Authenticated user is an Admin of Client #5
// Authenticated user is a Staff of Client #5

// When creating an order:
POST /api/orders

// Automatically assigned:
order.client_id = 5  // Multi-tenancy
order.user_id = 123  // Audit trail (who created)
```

#### **Scenario 2: View Orders**

```php
// Authenticated user is Admin of Client #5
GET /api/orders

// Automatic query:
SELECT * FROM orders WHERE client_id = 5
// Returns: orders from Client #5 (admin + staff)
```

#### **Scenario 3: Staff Only Sees Orders from Their Client**

```php
// Authenticated Staff user (client_id = 5)
GET /api/orders/123

// Automatic query:
SELECT * FROM orders WHERE id = 123 AND client_id = 5
// Only returns if order #123 belongs to Client #5
```

### **Frequently Asked Questions about Multi-Tenancy**

> **Q: Why did you use Single Database instead of Multi-Database?**  
> **A:** It's simpler to maintain, more resource-efficient, and allows for global reports. With scopes and foreign keys, we maintain data separation by `client_id`.

> **Q: How do you guarantee that a user doesn't see data from another client?**  
> **A:** I always use `Order::forAuthClient()` which automatically adds `WHERE client_id = X`. Eloquent models assign `client_id` in `creating`. Tests cover unauthorized access.

> **Q: What's the difference between `client_id` and `user_id` in orders?**  
> **A:** `client_id` indicates which company/client it belongs to (multi-tenancy). `user_id` indicates who created the order (audit trail).

---

## 🔄 Lazy Loading vs Eager Loading (N+1 Problem)

### **What is the N+1 Problem?**

It occurs when you make N additional queries to get relationships instead of using a single query with `with()`.

### **Example of the N+1 Problem:**

```php
// ❌ LAZY LOADING - Generates N+1 queries
$orders = Order::all();

foreach ($orders as $order) {
    echo $order->user->name;      // Query #1 for user
    echo $order->client->name;    // Query #2 for client
    foreach ($order->items as $item) {
        echo $item->product_name; // Query #3, #4, #5... for each item
    }
}

// Resulting queries:
// 1 query: SELECT * FROM orders
// N queries: SELECT * FROM users WHERE id = ?
// N queries: SELECT * FROM clients WHERE id = ?
// N queries: SELECT * FROM order_items WHERE order_id = ?
// Total: 1 + 3N SQL queries ⚠️
```

### **Solution: Eager Loading with `with()`**

```php
// ✅ EAGER LOADING - Only 3 optimized queries
$orders = Order::with(['user', 'client', 'items'])->get();

foreach ($orders as $order) {
    echo $order->user->name;      // No query (already loaded)
    echo $order->client->name;     // No query (already loaded)
    foreach ($order->items as $item) {
        echo $item->product_name; // No query (already loaded)
    }
}

// Resulting queries:
// 1 query: SELECT * FROM orders
// 1 query: SELECT * FROM users WHERE id IN (1,2,3,...)
// 1 query: SELECT * FROM clients WHERE id IN (1,2,3,...)
// 1 query: SELECT * FROM order_items WHERE order_id IN (1,2,3,...)
// Total: 4 SQL queries ✅
```

### **Implementation in the Project**

```php
// app/Services/OrderService.php
public function getOrderById(int $orderId): ?Order {
    return Order::forAuthClient()
               ->with(['items', 'client', 'user'])  // ✅ Eager Loading
               ->find($orderId);
}

public function getOrdersForAuthUser(): Collection {
    return Order::forAuthClient()
               ->with(['items', 'client', 'user'])  // ✅ Eager Loading
               ->latest()
               ->get();
}
```

### **Types of Eager Loading**

#### **1. Simple Eager Loading:**
```php
$order = Order::with('items')->find(1);
```

#### **2. Nested Eager Loading:**
```php
$orders = Order::with('items.product')->get();
```

#### **3. Conditional Eager Loading:**
```php
$orders = Order::with(['items' => function ($query) {
    $query->where('quantity', '>', 10);
}])->get();
```

#### **4. Eager Loading with Counter:**
```php
$users = User::withCount('orders')->get();
// Adds virtual field: orders_count
```

### **Identifying the N+1 Problem**

```bash
# Laravel Debugbar shows SQL queries
# If you see many similar queries, probably N+1

# Use Query Log in tests
DB::enableQueryLog();
$orders = Order::all(); // ... your code
dd(DB::getQueryLog()); // See all queries
```

### **Frequently Asked Questions**

> **Q: Should you always use `with()`?**  
> **A:** Only when you need the relationships in the context. Using `with()` unnecessarily wastes memory.

> **Q: What happens if I forget to use `with()`?**  
> **A:** Laravel can do lazy loading, but it generates N+1. In production you can use events or middleware to monitor slow queries.

> **Q: How did you avoid N+1 in your project?**  
> **A:** In `OrderService` I use `with(['items', 'client', 'user'])` to load relationships at once. In tests I verify the expected number of queries.

---

## 🧪 Test-Driven Development (TDD)

### **What is TDD?**

It's a development methodology where you write tests BEFORE writing the code.

### **TDD Cycle: Red-Green-Refactor**

```
1. 🔴 RED: Write failing test
2. 🟢 GREEN: Write minimum code to pass
3. 🔵 REFACTOR: Improve code without breaking tests
```

### **Test Types in Laravel**

#### **1. Feature Tests**
Test the complete flow: HTTP request → Controller → Service → Database

```php
// tests/Feature/AuthTest.php
class AuthTest extends TestCase {
    public function test_user_can_register() {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'user' => [
                         'id',
                         'name',
                         'email',
                         'client',
                     ],
                 ]);
    }
}
```

#### **2. Unit Tests**
Test isolated business logic, without HTTP or DB

```php
// tests/Unit/OrderServiceTest.php
class OrderServiceTest extends TestCase {
    public function test_calculates_order_totals_correctly() {
        // Arrange
        $client = Client::factory()->create();
        $user = User::factory()->for($client)->create();
        
        // Create mock request
        $request = StoreOrderRequest::create('/api/orders', 'POST', [
            'tax' => 5.00,
            'items' => [
                ['product_name' => 'Product', 'quantity' => 2, 'unit_price' => 10.00],
            ]
        ]);
        $request->setContainer(app());
        $request->validateResolved();
        
        $this->actingAs($user);
        
        // Act
        $order = app(OrderService::class)->createOrder($request);
        
        // Assert
        $this->assertEquals(20.00, $order->subtotal); // 2 × 10
        $this->assertEquals(5.00, $order->tax);
        $this->assertEquals(25.00, $order->total); // 20 + 5
    }
}
```

### **Test Configuration in Laravel**

```php
// phpunit.xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    <env name="CACHE_DRIVER" value="array"/>
</php>
```

**Characteristics:**
- In-memory database (`:memory:`)
- Resets after each test with `RefreshDatabase`
- Isolated environment (`testing`)
- Doesn't affect development/production data

### **Factories and Seeders for Tests**

```php
// database/factories/UserFactory.php
class UserFactory extends Factory {
    public function definition(): array {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'client_id' => Client::factory(),
            'role' => 'admin',
        ];
    }
}

// Use in tests
$user = User::factory()->create(); // Client created automatically
$admin = User::factory()->admin()->create();
$staff = User::factory()->staff()->create();
```

### **Run Tests**

```bash
# All tests
php artisan test

# Specific tests
php artisan test --filter AuthTest
php artisan test --filter OrderServiceTest

# With coverage
php artisan test --coverage
```

### **Frequently Asked Questions**

> **Q: How do you know what to test?**  
> **A:** Happy paths, validation/limits, errors/failures, unauthorized access. What matters: critical business flow, validations, multi-tenancy security, calculations.

> **Q: Do tests slow down development?**  
> **A:** Initially costly, but they reduce bugs, technical debt, enable safe refactoring, document usage, and catch regressions.

> **Q: When to use Unit vs Feature Tests?**  
> **A:** Features: complete HTTP → DB flow. Units: isolated logic. Most should be features.

> **Q: Why did tests initially fail?**  
> **A:** Missing relationships and creation order. It was fixed by creating clients before users and reviewing relationships.

---

## 🎨 Design Patterns in Laravel

### **1. Service Pattern**
Separation of business logic from Controller

**Code:**
```php
// app/Services/OrderService.php
class OrderService {
    public function createOrder(StoreOrderRequest $request): Order {
        return DB::transaction(function () use ($request) {
            $order = Order::create([...]);
            // ... logic
            return $order;
        });
    }
}
```

**Benefits:**
- Controller only handles HTTP
- Reusable
- Easy to test
- Database transactions

### **2. Repository Pattern**
Data access abstraction (not implemented but mentionable)

```php
// Conceptual example
interface OrderRepositoryInterface {
    public function find(int $id): ?Order;
    public function create(array $data): Order;
}

class DatabaseOrderRepository implements OrderRepositoryInterface {
    // Eloquent implementation
}

// In Controller
class OrderController {
    public function __construct(private OrderRepositoryInterface $repository) {}
}
```

### **3. Form Request Pattern**
Validation in separate layer

```php
// app/Http/Requests/StoreOrderRequest.php
class StoreOrderRequest extends FormRequest {
    public function rules(): array {
        return [
            'tax' => ['required', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
```

**Benefits:**
- Centralized validation
- Custom error messages
- Reusable

### **4. Resource Pattern**
Data transformation for API

```php
// app/Http/Resources/OrderResource.php
class OrderResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'total' => $this->total,
            'client' => new ClientResource($this->client),
            'items' => OrderItemResource::collection($this->items),
        ];
    }
}

// Use
return response()->json([
    'order' => new OrderResource($order)
]);
```

### **5. Job Pattern**
Asynchronous tasks in background

```php
// app/Jobs/GenerateInvoiceJob.php
class GenerateInvoiceJob implements ShouldQueue {
    public function __construct(private Order $order) {}
    
    public function handle() {
        // Generate PDF invoice
        // Send email
        // Update status
    }
}

// Dispatch
GenerateInvoiceJob::dispatch($order);
```

**Benefits:**
- Faster responses
- Better user experience
- Scalability

---

## 🛣️ Routes and API Endpoints

### **Route Structure in Laravel**

```php
// routes/api.php
Route::prefix('auth')->group(function () {
    // Public routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});
```

### **Create a New Endpoint**

#### **Step 1: Define the Route**

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders/reports', [OrderController::class, 'reports'])
        ->name('api.orders.reports');
});
```

#### **Step 2: Create Method in Controller**

```php
// app/Http/Controllers/Api/OrderController.php
public function reports(): JsonResponse {
    $orders = $this->orderService->getOrdersForAuthUser();
    
    return response()->json([
        'reports' => [...],
        'status_code' => 200,
    ], 200);
}
```

#### **Step 3: Add Logic in Service (if applicable)**

```php
// app/Services/OrderService.php
public function getOrderReports(): array {
    $orders = $this->getOrdersForAuthUser();
    // ... report logic
    return [...];
}
```

### **Route Types**

#### **Public Routes (No Authentication)**
```php
Route::post('/auth/register', [AuthController::class, 'register']);
```

#### **Protected Routes (With Authentication)**
```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
});
```

#### **Routes with Specific Roles**
```php
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/auth/register-staff', [AuthController::class, 'registerStaff']);
});
```

### **Route Naming**

```php
// RESTful convention
GET    /api/orders        -> index()   // List
GET    /api/orders/{id}   -> show()    // View one
POST   /api/orders        -> store()   // Create
PUT    /api/orders/{id}   -> update()  // Update
DELETE /api/orders/{id}   -> destroy() // Delete
```

### **Frequently Asked Questions**

> **Q: Why do you use prefixes and groups?**  
> **A:** They organize related routes, reuse middleware, improve readability, and centralize configuration.

> **Q: How did you create the `GET /api/orders/:id` endpoint?**  
> **A:** Added `Route::get('/{id}', [OrderController::class, 'show'])`, implemented `show()` in controller, delegated to service, added eager loading. Tests verify flow and multi-tenancy.

---

## 🛡️ Middleware in Laravel

### **What is Middleware?**

Software that intercepts HTTP requests before or after they reach the controller.

### **Middleware Types**

#### **1. Global Middleware**
Executes on every request

```php
// bootstrap/app.php
$middleware->api(prepend: [
    \App\Http\Middleware\ForceJsonResponse::class,
]);
```

#### **2. Group Middleware**
Executes on specific routes

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    // All routes here require authentication
});
```

#### **3. Route-Specific Middleware**
Executes only on one route

```php
Route::post('/register-staff', [AuthController::class, 'registerStaff'])
    ->middleware('admin');
```

### **Custom Middleware**

#### **Create Middleware:**

```bash
php artisan make:middleware EnsureAdminRole
```

#### **Implementation:**

```php
// app/Http/Middleware/EnsureAdminRole.php
class EnsureAdminRole {
    public function handle(Request $request, Closure $next) {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Admin access required');
        }
        
        return $next($request);
    }
}
```

#### **Register Middleware:**

```php
// bootstrap/app.php
$middleware->alias([
    'admin' => \App\Http\Middleware\EnsureAdminRole::class,
]);
```

#### **Use in Routes:**

```php
Route::post('/register-staff', [AuthController::class, 'registerStaff'])
    ->middleware(['auth:sanctum', 'admin']);
```

### **Middleware Flow**

```
Request
  ↓
Global Middleware (ForceJsonResponse)
  ↓
Route Middleware (auth:sanctum)
  ↓
Specific Middleware (admin)
  ↓
Controller
  ↓
Global Middleware (AddStatusCodeToResponse)
  ↓
Response
```

### **Frequently Asked Questions**

> **Q: Why two places for middleware (bootstrap/app.php and routes/api.php)?**  
> **A:** In `bootstrap/app.php` you configure global (JSON, CORS, exceptions). In `routes/api.php` you define per-route (`auth:sanctum`, `admin`). Global applies to all `/api/*` routes.

> **Q: When to create custom middleware?**  
> **A:** When you need to reuse verification logic on multiple routes.

> **Q: What middleware did you use in the project?**  
> **A:** `auth:sanctum`, `admin`, and global for JSON. Could also use rate limiting and specific CORS.

---

## 🔐 Authentication: Sanctum vs JWT

### **Laravel Sanctum**

#### **What is it?**
Native Laravel solution for APIs and SPAs.

#### **Characteristics:**
- **Token-based authentication:** Tokens stored in DB
- **Built-in**: Native to Laravel
- **Simple**: `HasApiTokens`
- **Flexible**: Web sessions or tokens
- **CSRF**: Built-in web protection

#### **Implementation in the Project:**

```php
// app/Models/User.php
class User extends Authenticatable {
    use HasApiTokens; // ✅ Sanctum trait
}

// Login
public function login(Request $request) {
    $user = User::where('email', $request->email)->first();
    
    if (Hash::check($request->password, $user->password)) {
        $token = $user->createToken('api')->plainTextToken;
        
        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }
}

// Use token
// In each request: Authorization: Bearer {token}
```

#### **Advantages:**
- ✅ Native integration
- ✅ Direct multi-tenancy
- ✅ Tokens in DB
- ✅ Easy revocation

#### **Disadvantages:**
- ❌ Additional query per request (verify token)
- ❌ Performance at ultra-high scale

---

### **JWT (JSON Web Tokens)**

#### **What is it?**
Self-contained tokens.

#### **Characteristics:**
- **Stateless**: No DB query
- **Portable**: Usable across multiple services
- **Self-contained**: Payload included
- **Signed**: Verifiable signature

#### **JWT Structure:**

```
eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.
eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.
SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c
 ├─────────────────┤
   HEADER.PAYLOAD.SIGNATURE
```

**Header:**
```json
{
  "alg": "HS256",
  "typ": "JWT"
}
```

**Payload:**
```json
{
  "sub": "1234567890",
  "name": "John Doe",
  "iat": 1516239022,
  "exp": 1516242622
}
```

**Signature:**
```
HMACSHA256(
  base64UrlEncode(header) + "." + base64UrlEncode(payload),
  secret
)
```

#### **Implementation with laravel-jwt:**

```php
// app/Http/Controllers/AuthController.php
use Tymon\JWTAuth\Facades\JWTAuth;

public function login(Request $request) {
    $credentials = $request->only('email', 'password');
    
    if (!$token = JWTAuth::attempt($credentials)) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    
    return response()->json([
        'token' => $token,
        'type' => 'Bearer'
    ]);
}

// In each request
public function me() {
    $user = JWTAuth::parseToken()->authenticate();
    return response()->json($user);
}
```

#### **Advantages:**
- ✅ No query per request
- ✅ Useful for microservices
- ✅ Scalable

#### **Disadvantages:**
- ❌ Revocation requires blacklist
- ❌ More complexity

---

### **Comparison: Sanctum vs JWT**

| Aspect                 | Sanctum                | JWT                 |
|------------------------|------------------------|---------------------|
| **Query DB**            | Yes (verify token)     | No                  |
| **Revocation**          | Easy (delete from DB)   | Blacklist required   |
| **Scalability**         | Good                   | Very high           |
| **Ease of Use**         | Very easy              | Medium              |
| **Laravel Integration** | Native                 | Uses package        |
| **State**              | Stateful               | Stateless           |
| **Session**            | Persists               | Expires             |

---

### **Why I Chose Sanctum**

1. Simplicity with Laravel
2. Direct multi-tenancy
3. Quick revocation
4. Easy debugging
5. Sufficient for scope

### **When to Use JWT:**

- High scale and stateless
- Distributed microservices
- Very large SPA without sessions

### **Frequently Asked Questions**

> **Q: Why Sanctum if JWT is faster?**  
> **A:** For simplicity, Laravel compatibility, and less complexity. JWT is better if performance is critical and the architecture requires it.

> **Q: How do you revoke tokens with Sanctum?**  
> **A:** `$user->tokens()->delete()` or `$user->currentAccessToken()->delete()`.

> **Q: Is JWT more secure?**  
> **A:** Both can be secure. Sanctum is simpler; JWT is portable and stateless.

---

## 📦 Laravel Nova and Alternatives

### **Laravel Nova**

Admin panel for Laravel.

#### **Characteristics:**
- Admin panel
- Automatic CRUD
- Filters and search
- Metrics
- Permissions

#### **When to Use:**
- You need admin
- Daily ops/management
- Budget available

#### **Cost:**
Not free.

#### **Use in the Project:**
Doesn't apply. The project is a REST API without an admin panel.

---

### **Alternatives to Nova**

#### **1. Filament**
- Laravel-first
- Free and open source
- Includes form builder
- Workflow plugins

#### **2. Backpack for Laravel**
- Admin and CRUD
- Free

#### **3. Orchid Platform**
- Laravel-oriented
- Open source

#### **4. Avo (formerly Laravel Spark)**
- SaaS
- Not free

---

### **Do You Need an Admin Panel for Your Project?**

Not immediately. Manual admin or the client builds their interface.

**If you add admin, use Filament** (similar to Nova, free, good support).

---

## 💬 Project FAQ

### **Question 1: Why did you separate logic into Services?**

**Answer:**
I separate business logic from the Controller to follow SOLID and keep the code more organized, reusable, and testable. The Controller only handles HTTP.

---

### **Question 2: How does multi-tenancy work in your project?**

**Answer:**
We use a single database with `client_id` to isolate data per client. Users have `client_id` (company/tenant) and in orders we use that field. With `Order::forAuthClient()` we filter by `client_id`. The model assigns `client_id` in `creating`.

---

### **Question 3: How did you avoid the N+1 problem?**

**Answer:**
By loading relationships with `with()`, avoiding lazy loading. In `OrderService` I use `with(['items', 'client', 'user'])` in `getOrderById()` and `getOrdersForAuthUser()` to reduce queries.

---

### **Question 4: Why did you use Laravel Sanctum instead of JWT?**

**Answer:**
For simplicity with Laravel, multi-tenancy control, and token management (create, revoke, list, validate). JWT is suitable for high scale and stateless architectures.

---

### **Question 5: How did you handle database transactions?**

**Answer:**
With `DB::transaction()` when creating orders to ensure atomicity and consistency. If it fails, it rolls back.

```php
public function createOrder(StoreOrderRequest $request): Order {
    return DB::transaction(function () use ($request) {
        $order = Order::create([...]);
        // ... create items
        return $order;
    });
}
```

---

### **Question 6: What tests did you write and why?**

**Answer:**
I wrote Feature tests (HTTP flows) and Unit tests (Service and calculation). They cover happy paths, validations, multi-tenancy, calculations, and permissions.

---

### **Question 7: How did you handle asynchronous invoice generation?**

**Answer:**
With Laravel Jobs. I dispatch `GenerateInvoiceJob` after creating the order; it runs in background and doesn't block the response.

```php
// In OrderService
GenerateInvoiceJob::dispatch($order);
```

---

### **Question 8: What improvements would you make to the code?**

**Answer:**
- Pagination in listing endpoints
- Cache with Redis
- Laravel Telescope/Debugbar for debugging
- API versioning (`/api/v1/orders`)
- Rate limiting by IP/auth
- Events/listeners instead of direct Jobs
- Repositories to abstract Eloquent
- Structured logging
- Broader integration tests

---

### **Question 9: How do you guarantee security in your API?**

**Answer:**
- Authentication with Sanctum
- `auth:sanctum` middleware
- Validation with FormRequest
- Tenant filters
- Password hashing
- Revocable tokens
- HTTPS in production

---

### **Question 10: What is `client_id` vs `user_id` in the orders table?**

**Answer:**
- `client_id`: tenant (company/client) → Multi-tenancy
- `user_id`: author (who created) → Audit trail

---

## 📝 Interview Tips

1. **Explain the "why"** behind each decision
2. **Mention SOLID** when talking about architecture
3. **Talk about N+1** spontaneously
4. **Cite TDD** in methodology
5. **Detail multi-tenancy** if it comes up
6. **Dig deep into Sanctum vs JWT** based on context
7. **Mention possible improvements** to the project
8. **Show code reading** with examples

---

## ✅ Pre-Interview Checklist

- [ ] Review project code
- [ ] Understand each architecture
- [ ] Practice answers out loud
- [ ] Prepare examples
- [ ] Have questions ready
- [ ] Review technical documentation

---

## 🎯 Additional Resources

- Laravel Documentation: https://laravel.com/docs
- Laravel Sanctum: https://laravel.com/docs/sanctum
- SOLID Principles: https://www.digitalocean.com/community/tutorials/s-o-l-i-d-the-first-five-principles-of-object-oriented-design
- Filament: https://filamentphp.com

---

Good luck in your interview! 🚀

