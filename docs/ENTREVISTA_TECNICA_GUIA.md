# 🎯 Guía de Preparación para Entrevista Técnica - Laravel

## 📋 Índice
1. [Principios SOLID](#principios-solid)
2. [Sistemas Multi-Tenant](#sistemas-multi-tenant)
3. [Lazy Loading vs Eager Loading (N+1 Problem)](#lazy-loading-vs-eager-loading-n1-problem)
4. [Test-Driven Development (TDD)](#test-driven-development-tdd)
5. [Patrones de Diseño en Laravel](#patrones-de-diseño-en-laravel)
6. [Rutas y Endpoints API](#rutas-y-endpoints-api)
7. [Middleware en Laravel](#middleware-en-laravel)
8. [Autenticación: Sanctum vs JWT](#autenticación-sanctum-vs-jwt)
9. [Laravel Nova y Alternativas](#laravel-nova-y-alternativas)
10. [Preguntas Frecuentes del Proyecto](#preguntas-frecuentes-del-proyecto)

---

## 🏗️ Principios SOLID

### **¿Qué son los Principios SOLID?**

SOLID es un acrónimo de cinco principios de diseño orientado a objetos que hacen el código más mantenible, escalable y fácil de entender.

### **1. S - Single Responsibility Principle (Principio de Responsabilidad Única)**

**Definición:** Cada clase debe tener una sola razón para cambiar.

**Ejemplo en el Proyecto:**

```php
// ❌ MAL: Controller haciendo lógica de negocio
class OrderController extends Controller {
    public function store(Request $request) {
        $order = new Order();
        $order->client_id = auth()->user()->client_id;
        $order->total = 0;
        // ... cálculos complejos aquí
        $order->save();
    }
}

// ✅ BIEN: Controller solo maneja HTTP, Service maneja lógica
class OrderController extends Controller {
    public function __construct(private OrderService $orderService) {}
    
    public function store(StoreOrderRequest $request): JsonResponse {
        $order = $this->orderService->createOrder($request);
        return response()->json(['order' => $order], 201);
    }
}

// Service tiene la responsabilidad única de manejar lógica de órdenes
class OrderService {
    public function createOrder(StoreOrderRequest $request): Order {
        return DB::transaction(function () use ($request) {
            $order = Order::create([...]);
            // ... lógica de negocio aquí
            return $order;
        });
    }
}
```

**Pregunta Frecuente:**
> **Q: ¿Por qué separaste la lógica de negocio del Controller?**  
> **R:** Porque el Controller debe solo manejar HTTP (request/response) y el Service debe manejar la lógica de negocio. Esto permite reutilizar la lógica en otros contextos (consola, jobs, etc.) y facilita las pruebas unitarias.

### **2. O - Open/Closed Principle (Principio Abierto/Cerrado)**

**Definición:** Las entidades deben estar abiertas para extensión pero cerradas para modificación.

**Ejemplo en el Proyecto:**

```php
// Model Order tiene método generable que puede extenderse
class Order extends Model {
    public static function generateOrderNumber(): string {
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(4));
        return "ORD-{$date}-{$random}";
    }
}

// ✅ Puedes extender sin modificar la clase original
class CustomOrder extends Order {
    public static function generateOrderNumber(): string {
        return "CUSTOM-" . parent::generateOrderNumber();
    }
}
```

### **3. L - Liskov Substitution Principle (Principio de Sustitución de Liskov)**

**Definición:** Los objetos de una superclase deben poder ser reemplazados por objetos de sus subclases sin quebrar la aplicación.

**Ejemplo Conceptual:**
```php
// Todas las implementaciones de OrderRepository deben ser intercambiables
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
        // Implementación diferente, pero misma interfaz
    }
}
```

### **4. I - Interface Segregation Principle (Principio de Segregación de Interfaces)**

**Definición:** No se debe forzar a una clase a implementar interfaces que no usa.

**Ejemplo:**
```php
// ❌ MAL: Interface grande con muchos métodos
interface OrderInterface {
    public function create();
    public function update();
    public function delete();
    public function generateInvoice(); // Solo algunos necesitan esto
}

// ✅ BIEN: Interfaces pequeñas y específicas
interface OrderCrudInterface {
    public function create();
    public function update();
    public function delete();
}

interface InvoiceInterface {
    public function generateInvoice();
}
```

### **5. D - Dependency Inversion Principle (Principio de Inversión de Dependencias)**

**Definición:** Depende de abstracciones, no de concreciones.

**Ejemplo en el Proyecto:**

```php
// ❌ MAL: Dependencia directa de implementación concreta
class OrderController extends Controller {
    public function store(Request $request) {
        $order = Order::create($request->all()); // Depende directamente
    }
}

// ✅ BIEN: Depende de abstracción (Service inyectado)
class OrderController extends Controller {
    public function __construct(private OrderService $orderService) {}
    
    public function store(StoreOrderRequest $request) {
        $order = $this->orderService->createOrder($request);
    }
}

// Laravel resuelve la dependencia automáticamente (Service Container)
```

**Pregunta Frecuente:**
> **Q: ¿Por qué usas inyección de dependencias en el constructor?**  
> **R:** Permite que Laravel inyecte automáticamente las dependencias, facilita las pruebas (puedes mockear el Service) y sigue el principio de inversión de dependencias.

---

## 🏢 Sistemas Multi-Tenant

### **¿Qué es un Sistema Multi-Tenant?**

Un sistema multi-tenant permite que múltiples clientes (tenants) compartan la misma aplicación e infraestructura, pero manteniendo sus datos completamente separados.

### **Modelo Implementado en el Proyecto: Single Database con Separación de Datos**

```sql
-- Estructura de Tablas
clients
├── id
├── name
└── ...

users
├── id
├── name
├── email
├── role (admin/staff)
├── client_id (FK a clients.id)
└── ...

orders
├── id
├── client_id (Multi-tenancy: a qué cliente pertenece)
├── user_id (Audit trail: quién creó la orden)
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

### **Arquitectura: Separación Cliente-Usuario**

**Conceptos Clave:**

#### **1. Client (Empresa/Cliente)**
- Representa una empresa que usa el sistema
- Tiene múltiples usuarios (admin + staff)

#### **2. User Roles:**

**Admin (Administrador):**
- Es el propietario de la empresa/cliente
- `client_id` apunta a su propio registro en `clients`
- Puede gestionar staff
- Ve todas sus órdenes y las de su staff

**Staff (Empleado):**
- Pertenece a un admin (empresa)
- `client_id` apunta al `clients.id` del admin
- No puede gestionar otros usuarios
- Solo ve órdenes de su cliente

### **Cómo Funciona el Multi-Tenancy**

#### **1. Scope para Filtrar por Cliente:**

```php
// En app/Models/Order.php
public function scopeForAuthClient($query) {
    if (auth()->check()) {
        $clientId = auth()->user()->getEffectiveClientId();
        return $query->where('client_id', $clientId);
    }
    return $query;
}

// Uso en Service
class OrderService {
    public function getOrdersForAuthUser(): Collection {
        return Order::forAuthClient()  // Solo órdenes del cliente del usuario
                   ->with(['items', 'client', 'user'])
                   ->latest()
                   ->get();
    }
}
```

#### **2. Asignación Automática de client_id:**

```php
// En app/Models/Order.php - Boot method
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

#### **3. User Model - Método getEffectiveClientId():**

```php
// app/Models/User.php
public function getEffectiveClientId(): int {
    return $this->client_id; // client_id es el mismo para admin y staff
}

// Ambos admin y staff tienen client_id que apunta a la misma empresa
// Admin: client_id = X (su propia empresa)
// Staff: client_id = X (pertenece a la misma empresa del admin)
```

### **Ejemplos Prácticos**

#### **Escenario 1: Crear una Orden**

```php
// Usuario autenticado es un Admin del Client #5
// Usuario autenticado es un Staff del Client #5

// Cuando crea una orden:
POST /api/orders

// Automáticamente se asigna:
order.client_id = 5  // Multi-tenancy
order.user_id = 123  // Audit trail (quién creó)
```

#### **Escenario 2: Ver Órdenes**

```php
// Usuario autenticado es Admin del Client #5
GET /api/orders

// Query automático:
SELECT * FROM orders WHERE client_id = 5
// Devuelve: órdenes del Client #5 (admin + staff)
```

#### **Escenario 3: Staff Solo Ve Órdenes de Su Cliente**

```php
// Usuario Staff autenticado (client_id = 5)
GET /api/orders/123

// Query automático:
SELECT * FROM orders WHERE id = 123 AND client_id = 5
// Solo devuelve si la orden #123 pertenece al Client #5
```

### **Preguntas Frecuentes sobre Multi-Tenancy**

> **Q: ¿Por qué usaste Single Database en lugar de Multi-Database?**  
> **R:** Por ser más simple de mantener, más eficiente en recursos y permitir reportes globales. Con scopes y claves foráneas, mantenemos la separación de datos por `client_id`.

> **Q: ¿Cómo garantizas que un usuario no vea datos de otro cliente?**  
> **R:** Siempre uso `Order::forAuthClient()` que agrega automáticamente `WHERE client_id = X`. Los modelos de Eloquent asignan `client_id` en `creating`. Los tests cubren accesos no autorizados.

> **Q: ¿Qué diferencia hay entre `client_id` y `user_id` en orders?**  
> **R:** `client_id` indica a qué empresa/cliente pertenece (multi-tenancy). `user_id` indica quién creó la orden (audit trail).

---

## 🔄 Lazy Loading vs Eager Loading (N+1 Problem)

### **¿Qué es el Problema N+1?**

Ocurre cuando haces N consultas adicionales para obtener relaciones en lugar de usar una sola consulta con `with()`.

### **Ejemplo del Problema N+1:**

```php
// ❌ LAZY LOADING - Genera N+1 consultas
$orders = Order::all();

foreach ($orders as $order) {
    echo $order->user->name;      // Query #1 para user
    echo $order->client->name;    // Query #2 para client
    foreach ($order->items as $item) {
        echo $item->product_name; // Query #3, #4, #5... por cada item
    }
}

// Consultas resultantes:
// 1 query: SELECT * FROM orders
// N queries: SELECT * FROM users WHERE id = ?
// N queries: SELECT * FROM clients WHERE id = ?
// N queries: SELECT * FROM order_items WHERE order_id = ?
// Total: 1 + 3N consultas SQL ⚠️
```

### **Solución: Eager Loading con `with()`**

```php
// ✅ EAGER LOADING - Solo 3 consultas optimizadas
$orders = Order::with(['user', 'client', 'items'])->get();

foreach ($orders as $order) {
    echo $order->user->name;      // Sin query (ya cargado)
    echo $order->client->name;     // Sin query (ya cargado)
    foreach ($order->items as $item) {
        echo $item->product_name; // Sin query (ya cargado)
    }
}

// Consultas resultantes:
// 1 query: SELECT * FROM orders
// 1 query: SELECT * FROM users WHERE id IN (1,2,3,...)
// 1 query: SELECT * FROM clients WHERE id IN (1,2,3,...)
// 1 query: SELECT * FROM order_items WHERE order_id IN (1,2,3,...)
// Total: 4 consultas SQL ✅
```

### **Implementación en el Proyecto**

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

### **Tipos de Eager Loading**

#### **1. Eager Loading Simple:**
```php
$order = Order::with('items')->find(1);
```

#### **2. Eager Loading Anidado:**
```php
$orders = Order::with('items.product')->get();
```

#### **3. Eager Loading Condicional:**
```php
$orders = Order::with(['items' => function ($query) {
    $query->where('quantity', '>', 10);
}])->get();
```

#### **4. Eager Loading con Contador:**
```php
$users = User::withCount('orders')->get();
// Agrega campo virtual: orders_count
```

### **Identificar el Problema N+1**

```bash
# Laravel Debugbar muestra consultas SQL
# Si ves muchas consultas similares, probablemente N+1

# Usa Query Log en pruebas
DB::enableQueryLog();
$orders = Order::all(); // ... tu código
dd(DB::getQueryLog()); // Ver todas las consultas
```

### **Preguntas Frecuentes**

> **Q: ¿Siempre debes usar `with()`?**  
> **R:** Solo cuando necesitas las relaciones en el contexto. Usar `with()` sin necesidad desperdicia memoria.

> **Q: ¿Qué pasa si olvido usar `with()`?**  
> **R:** Laravel puede hacer lazy loading, pero genera N+1. En producción puedes usar eventos o middleware para monitorear consultas lentas.

> **Q: ¿Cómo evité N+1 en tu proyecto?**  
> **R:** En `OrderService` uso `with(['items', 'client', 'user'])` para cargar relaciones de una vez. En tests verifico el número de consultas esperadas.

---

## 🧪 Test-Driven Development (TDD)

### **¿Qué es TDD?**

Es una metodología de desarrollo donde escribes las pruebas ANTES de escribir el código.

### **Ciclo TDD: Red-Green-Refactor**

```
1. 🔴 RED: Escribes test que falla
2. 🟢 GREEN: Escribes código mínimo para que pase
3. 🔵 REFACTOR: Mejoras el código sin romper tests
```

### **Tipos de Tests en Laravel**

#### **1. Feature Tests (Tests de Funcionalidad)**
Prueban el flujo completo: HTTP request → Controller → Service → Database

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

#### **2. Unit Tests (Tests Unitarios)**
Prueban lógica de negocio aislada, sin HTTP ni DB

```php
// tests/Unit/OrderServiceTest.php
class OrderServiceTest extends TestCase {
    public function test_calculates_order_totals_correctly() {
        // Arrange
        $client = Client::factory()->create();
        $user = User::factory()->for($client)->create();
        
        // Crear mock request
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

### **Configuración de Tests en Laravel**

```php
// phpunit.xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    <env name="CACHE_DRIVER" value="array"/>
</php>
```

**Características:**
- Base de datos en memoria (`:memory:`)
- Después de cada test se resetea con `RefreshDatabase`
- Ambiente aislado (`testing`)
- No afecta datos de desarrollo/producción

### **Factories y Seeders para Tests**

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

// Uso en tests
$user = User::factory()->create(); // Cliente se crea automáticamente
$admin = User::factory()->admin()->create();
$staff = User::factory()->staff()->create();
```

### **Run Tests**

```bash
# Todos los tests
php artisan test

# Tests específicos
php artisan test --filter AuthTest
php artisan test --filter OrderServiceTest

# Con cobertura
php artisan test --coverage
```

### **Preguntas Frecuentes**

> **Q: ¿Cómo sabes qué probar?**  
> **R:** Casos exitosos, límites/validación, errores/fallos y acceso no autorizado. Importan flujo de negocios crítico, validaciones, seguridad multi-tenant y cálculos.

> **Q: ¿Tests ralentizan desarrollo?**  
> **R:** A corto plazo parecen costosos, pero reducen bugs y deuda técnica, permiten refactoring seguro, documentan el uso y detectan regresiones.

> **Q: ¿Cuándo usar Unit vs Feature Tests?**  
> **R:** Features: flujo completo HTTP → DB. Units: lógica aislada. La mayoría deben ser features.

> **Q: ¿Por qué tests fallaron inicialmente?**  
> **R:** Relaciones faltantes y orden de creación. Se corrigió creando clientes antes de usuarios y revisando relaciones.

---

## 🎨 Patrones de Diseño en Laravel

### **1. Service Pattern (Patrón de Servicio)**
Separación de lógica de negocio del Controller

**Código:**
```php
// app/Services/OrderService.php
class OrderService {
    public function createOrder(StoreOrderRequest $request): Order {
        return DB::transaction(function () use ($request) {
            $order = Order::create([...]);
            // ... lógica
            return $order;
        });
    }
}
```

**Beneficios:**
- Controller solo maneja HTTP
- Reutilizable
- Fácil de testear
- Transacciones de base de datos

### **2. Repository Pattern (Patrón de Repositorio)**
Abstracción de acceso a datos (no implementado pero mencionable)

```php
// Ejemplo conceptual
interface OrderRepositoryInterface {
    public function find(int $id): ?Order;
    public function create(array $data): Order;
}

class DatabaseOrderRepository implements OrderRepositoryInterface {
    // Implementación con Eloquent
}

// En el Controller
class OrderController {
    public function __construct(private OrderRepositoryInterface $repository) {}
}
```

### **3. Form Request Pattern**
Validación en capa separada

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

**Beneficios:**
- Validación centralizada
- Mensajes de error personalizados
- Reutilizable

### **4. Resource Pattern**
Transformación de datos para API

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

// Uso
return response()->json([
    'order' => new OrderResource($order)
]);
```

### **5. Job Pattern (Patrón de Cola)**
Tareas asíncronas en background

```php
// app/Jobs/GenerateInvoiceJob.php
class GenerateInvoiceJob implements ShouldQueue {
    public function __construct(private Order $order) {}
    
    public function handle() {
        // Generar PDF de factura
        // Enviar email
        // Actualizar estado
    }
}

// Dispatch
GenerateInvoiceJob::dispatch($order);
```

**Beneficios:**
- Respuestas más rápidas
- Mejor experiencia de usuario
- Escalabilidad

---

## 🛣️ Rutas y Endpoints API

### **Estructura de Rutas en Laravel**

```php
// routes/api.php
Route::prefix('auth')->group(function () {
    // Rutas públicas
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    // Rutas protegidas
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});
```

### **Crear un Nuevo Endpoint**

#### **Paso 1: Definir la Ruta**

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders/reports', [OrderController::class, 'reports'])
        ->name('api.orders.reports');
});
```

#### **Paso 2: Crear el Método en el Controller**

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

#### **Paso 3: Agregar Lógica en el Service (si aplica)**

```php
// app/Services/OrderService.php
public function getOrderReports(): array {
    $orders = $this->getOrdersForAuthUser();
    // ... lógica de reportes
    return [...];
}
```

### **Tipos de Rutas**

#### **Rutas Públicas (Sin Autenticación)**
```php
Route::post('/auth/register', [AuthController::class, 'register']);
```

#### **Rutas Protegidas (Con Autenticación)**
```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
});
```

#### **Rutas con Roles Específicos**
```php
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/auth/register-staff', [AuthController::class, 'registerStaff']);
});
```

### **Nombramiento de Rutas**

```php
// Convención RESTful
GET    /api/orders        -> index()   // Listar
GET    /api/orders/{id}   -> show()    // Ver uno
POST   /api/orders        -> store()   // Crear
PUT    /api/orders/{id}   -> update()  // Actualizar
DELETE /api/orders/{id}   -> destroy() // Eliminar
```

### **Preguntas Frecuentes**

> **Q: ¿Por qué usas prefijos y grupos?**  
> **R:** Porque organizan rutas relacionadas, reutilizan middleware, mejoran legibilidad y centralizan configuración.

> **Q: ¿Cómo creaste el endpoint `GET /api/orders/:id`?**  
> **R:** Añadí `Route::get('/{id}', [OrderController::class, 'show'])`, implementé `show()` en el controller, delegué al service y añadí eager loading. Los tests verifican flujo y multi-tenancy.

---

## 🛡️ Middleware en Laravel

### **¿Qué es un Middleware?**

Software que intercepta HTTP requests antes o después de que lleguen al controller.

### **Tipos de Middleware**

#### **1. Global Middleware**
Se ejecuta en cada request

```php
// bootstrap/app.php
$middleware->api(prepend: [
    \App\Http\Middleware\ForceJsonResponse::class,
]);
```

#### **2. Grupo de Middleware**
Se ejecuta en rutas específicas

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    // Todas las rutas aquí requieren autenticación
});
```

#### **3. Middleware por Ruta**
Se ejecuta solo en una ruta

```php
Route::post('/register-staff', [AuthController::class, 'registerStaff'])
    ->middleware('admin');
```

### **Middleware Personalizado**

#### **Crear Middleware:**

```bash
php artisan make:middleware EnsureAdminRole
```

#### **Implementación:**

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

#### **Registrar Middleware:**

```php
// bootstrap/app.php
$middleware->alias([
    'admin' => \App\Http\Middleware\EnsureAdminRole::class,
]);
```

#### **Usar en Rutas:**

```php
Route::post('/register-staff', [AuthController::class, 'registerStaff'])
    ->middleware(['auth:sanctum', 'admin']);
```

### **Flujo de Middleware**

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

### **Preguntas Frecuentes**

> **Q: ¿Por qué dos lugares para middleware (bootstrap/app.php y routes/api.php)?**  
> **R:** En `bootstrap/app.php` se configura lo global (JSON, CORS, excepciones). En `routes/api.php` se define por ruta (`auth:sanctum`, `admin`). Lo global aplica a todas las rutas `/api/*`.

> **Q: ¿Cuándo crear middleware personalizado?**  
> **R:** Cuando necesitas reutilizar lógica de verificación en varias rutas.

> **Q: ¿Qué middleware usaste en el proyecto?**  
> **R:** `auth:sanctum`, `admin` y globales para JSON. Podrían usarse rate limiting y CORS específico.

---

## 🔐 Autenticación: Sanctum vs JWT

### **Laravel Sanctum**

#### **¿Qué es?**
Solución nativa de Laravel para APIs y SPAs.

#### **Características:**
- **Token-based authentication:** Tokens almacenados en DB
- **Built-in**: Nativo de Laravel
- **Sencillo**: `HasApiTokens`
- **Flexible**: Web sessions o tokens
- **CSRF**: Protección web incluida

#### **Implementación en el Proyecto:**

```php
// app/Models/User.php
class User extends Authenticatable {
    use HasApiTokens; // ✅ Trait de Sanctum
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

// Usar token
// En cada request: Authorization: Bearer {token}
```

#### **Ventajas:**
- ✅ Integración nativa
- ✅ Multi-tenancy directo
- ✅ Tokens en DB
- ✅ Revocación fácil

#### **Desventajas:**
- ❌ Query adicional por request (verificar token)
- ❌ Performance en ultra alta escala

---

### **JWT (JSON Web Tokens)**

#### **¿Qué es?**
Tokens autoportados (self-contained).

#### **Características:**
- **Stateless**: Sin DB query
- **Portable**: Usable en múltiples servicios
- **Self-contained**: Payload incluido
- **Signed**: Firma verificable

#### **Estructura de JWT:**

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

#### **Implementación con laravel-jwt:**

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

// En cada request
public function me() {
    $user = JWTAuth::parseToken()->authenticate();
    return response()->json($user);
}
```

#### **Ventajas:**
- ✅ Sin query por request
- ✅ Útil para microservicios
- ✅ Escalable

#### **Desventajas:**
- ❌ Revocación requiere blacklist
- ❌ Más complejidad

---

### **Comparación: Sanctum vs JWT**

| Aspecto                 | Sanctum                | JWT                 |
|-------------------------|------------------------|---------------------|
| **Query DB**            | Sí (verificar token)   | No                  |
| **Revocación**          | Fácil (eliminar de DB) | Blacklist requerida |
| **Escalabilidad**       | Buena                  | Muy alta            |
| **Facilidad**           | Muy fácil              | Media               |
| **Integración Laravel** | Nativa                 | Usa paquete         |
| **Estado**              | Stateful               | Stateless           |
| **Sesión**              | Persiste               | Expira              |

---

### **¿Por qué Elegí Sanctum?**

1. Simplicidad con Laravel
2. Multi-tenancy directo
3. Revocación rápida
4. Depuración sencilla
5. Suficiente para el alcance

### **Cuando Usar JWT:**

- Alta escala y stateless
- Microservicios distribuidos
- SPA muy grande sin sesiones

### **Preguntas Frecuentes**

> **Q: ¿Por qué Sanctum si JWT es más rápido?**  
> **R:** Por simplicidad, compatibilidad con Laravel y menor complejidad. JWT es mejor si el rendimiento es crítico y la arquitectura lo requiere.

> **Q: ¿Cómo revocas tokens con Sanctum?**  
> **R:** `$user->tokens()->delete()` o `$user->currentAccessToken()->delete()`.

> **Q: ¿JWT es más seguro?**  
> **R:** Ambos pueden ser seguros. Sanctum es más simple; JWT es portable y stateless.

---

## 📦 Laravel Nova y Alternativas

### **Laravel Nova**

Panel admin para Laravel.

#### **Características:**
- Admin panel
- CRUD automático
- Filtros y búsqueda
- Métricas
- Permisos

#### **Cuándo Usar:**
- Necesitas admin
- Ops/gestión diaria
- Presupuesto disponible

#### **Costo:**
No gratuito.

#### **Uso en el Proyecto:**
No aplica. El proyecto es API REST sin panel admin.

---

### **Alternativas a Nova**

#### **1. Filament**
- Laravel-first
- Gratuito y open source
- Incluye form builder
- Workflow plugins

#### **2. Backpack for Laravel**
- Admin y CRUD
- Gratuito

#### **3. Orchid Platform**
- Orientado a Laravel
- Open source

#### **4. Avo (anterior Laravel Spark)**
- SaaS
- No es gratis

---

### **¿Necesitas un Panel Admin para tu Proyecto?**

No inmediatamente. Admin manual o el cliente construye su interfaz.

**Si agregas admin, usa Filament** (similar a Nova, gratuito, buen soporte).

---

## 💬 Preguntas Frecuentes del Proyecto

### **Pregunta 1: ¿Por qué separaste la lógica en Services?**

**Respuesta:**
Separo lógica de negocio del Controller para cumplir SOLID y mantener el código más organizado, reutilizable y testeable. El Controller solo maneja HTTP.

---

### **Pregunta 2: ¿Cómo funciona el multi-tenancy en tu proyecto?**

**Respuesta:**
Usamos una sola base de datos con `client_id` para aislar datos por cliente. Los usuarios tienen `client_id` (empresa/tenant) y en órdenes usamos ese campo. Con `Order::forAuthClient()` filtramos por `client_id`. El modelo asigna `client_id` en `creating`.

---

### **Pregunta 3: ¿Cómo evitaste el problema N+1?**

**Respuesta:**
Cargando relaciones con `with()`, evitando lazy loading. En `OrderService` uso `with(['items', 'client', 'user'])` en `getOrderById()` y `getOrdersForAuthUser()` para reducir consultas.

---

### **Pregunta 4: ¿Por qué usaste Laravel Sanctum en lugar de JWT?**

**Respuesta:**
Por simplicidad con Laravel, control de multi-tenancy y manejo de tokens (crear, revocar, listar, validar). JWT es adecuado para alta escala y arquitecturas stateless.

---

### **Pregunta 5: ¿Cómo manejaste las transacciones de base de datos?**

**Respuesta:**
Con `DB::transaction()` al crear órdenes para garantizar atomicidad y consistencia. Si falla, se revierte.

```php
public function createOrder(StoreOrderRequest $request): Order {
    return DB::transaction(function () use ($request) {
        $order = Order::create([...]);
        // ... crear items
        return $order;
    });
}
```

---

### **Pregunta 6: ¿Qué tests escribiste y por qué?**

**Respuesta:**
Escribí tests de Feature (flujos HTTP) y Unit (Service y cálculo). Cubren happy paths, validaciones, multi-tenancy, cálculos y permisos.

---

### **Pregunta 7: ¿Cómo manejaste la generación de facturas asíncrona?**

**Respuesta:**
Con Laravel Jobs. Despacho `GenerateInvoiceJob` tras crear la orden; corre en background y no bloquea la respuesta.

```php
// En OrderService
GenerateInvoiceJob::dispatch($order);
```

---

### **Pregunta 8: ¿Qué mejoras harías al código?**

**Respuesta:**
- Paginación en endpoints de listado
- Cache con Redis
- Laravel Telescope/Debugbar para debugging
- API versioning (`/api/v1/orders`)
- Rate limiting por IP/auth
- Eventos/listeners en vez de Jobs directos
- Repositorios para abstraer Eloquent
- Logging estructurado
- Tests de integración más amplios

---

### **Pregunta 9: ¿Cómo garantizas la seguridad en tu API?**

**Respuesta:**
- Autenticación con Sanctum
- Middleware `auth:sanctum`
- Validación con FormRequest
- Filtros por tenant
- Password hashing
- Tokens que se revocan
- HTTPS en producción

---

### **Pregunta 10: ¿Qué es `client_id` vs `user_id` en la tabla orders?**

**Respuesta:**
- `client_id`: tenant (empresa/cliente) → Multi-tenancy
- `user_id`: autor (quién creó) → Audit trail

---

## 📝 Consejos para la Entrevista

1. **Explica el “por qué”** detrás de cada decisión
2. **Menciona SOLID** al hablar de arquitectura
3. **Habla de N+1** espontáneamente
4. **Cita TDD** en la metodología
5. **Detalla multi-tenancy** si aparece
6. **Profundiza en Sanctum vs JWT** según el contexto
7. **Menciona mejoras** posibles del proyecto
8. **Muestra lectura del código** con ejemplos

---

## ✅ Checklist Pre-Entrevista

- [ ] Revisar el código del proyecto
- [ ] Entender cada arquitectura
- [ ] Practicar respuestas en voz alta
- [ ] Preparar ejemplos
- [ ] Tener preguntas listas
- [ ] Revisar documentación técnica

---

## 🎯 Recursos Adicionales

- Documentación Laravel: https://laravel.com/docs
- Laravel Sanctum: https://laravel.com/docs/sanctum
- SOLID Principles: https://www.digitalocean.com/community/tutorials/s-o-l-i-d-the-first-five-principles-of-object-oriented-design
- Filament: https://filamentphp.com

---

¡Éxito en tu entrevista! 🚀

