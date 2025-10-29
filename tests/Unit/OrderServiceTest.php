<?php

namespace Tests\Unit;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\User;
use App\Models\Client;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = new OrderService();
    }

    /**
     * Test order service creates order with correct calculations.
     * Verifies business logic for order creation
     */
    public function test_create_order_calculates_totals_correctly(): void
    {
        // Create client first
        $client = Client::factory()->create();
        
        // Create authenticated user
        $user = User::factory()->create([
            'role' => 'admin',
            'client_id' => $client->id
        ]);
        $this->actingAs($user);

        // Mock request data
        $requestData = [
            'tax' => 10.00,
            'notes' => 'Test order',
            'items' => [
                [
                    'product_name' => 'Product A',
                    'quantity' => 2,
                    'unit_price' => 50.00
                ],
                [
                    'product_name' => 'Product B',
                    'quantity' => 1,
                    'unit_price' => 25.00
                ]
            ]
        ];

        // Create mock request
        $request = StoreOrderRequest::create('/api/orders', 'POST', $requestData);
        $request->setContainer(app());
        $request->validateResolved();

        // Execute service method
        $order = $this->orderService->createOrder($request);

        // Assert order was created
        $this->assertInstanceOf(Order::class, $order);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'client_id' => $user->client_id,
            'user_id' => $user->id,
            'tax' => 10.00,
            'notes' => 'Test order'
        ]);

        // Assert calculations are correct
        $this->assertEquals(125.00, $order->subtotal); // (2*50) + (1*25)
        $this->assertEquals(10.00, $order->tax);
        $this->assertEquals(135.00, $order->total); // subtotal + tax

        // Assert order items were created
        $this->assertCount(2, $order->items);
        $this->assertEquals('Product A', $order->items[0]->product_name);
        $this->assertEquals(2, $order->items[0]->quantity);
        $this->assertEquals(50.00, $order->items[0]->unit_price);
        $this->assertEquals(100.00, $order->items[0]->subtotal);
    }

    /**
     * Test order service respects multi-tenancy.
     * Verifies that orders are scoped to authenticated user
     */
    public function test_get_orders_respects_multi_tenancy(): void
    {
        // Create clients first
        $client1 = Client::factory()->create();
        $client2 = Client::factory()->create();

        // Create two users with different clients
        $user1 = User::factory()->create(['role' => 'admin', 'client_id' => $client1->id]);
        $user2 = User::factory()->create(['role' => 'admin', 'client_id' => $client2->id]);

        // OPTION 1: Create orders manually specifying IDs (current implementation)
        Order::factory()->count(2)->create([
            'client_id' => $client1->id,
            'user_id' => $user1->id
        ]);
        Order::factory()->count(3)->create([
            'client_id' => $client2->id,
            'user_id' => $user2->id
        ]);
        
        // OPTION 2: Alternative using ->forClient() and ->createdBy() methods (commented out)
        // Order::factory()->count(2)
        //     ->forClient($client1)
        //     ->createdBy($user1)
        //     ->create();
        // Order::factory()->count(3)
        //     ->forClient($client2)
        //     ->createdBy($user2)
        //     ->create();

        // Test user1 only sees their orders
        $this->actingAs($user1);
        $user1Orders = $this->orderService->getOrdersForAuthUser();
        $this->assertCount(2, $user1Orders);

        // Test user2 only sees their orders
        $this->actingAs($user2);
        $user2Orders = $this->orderService->getOrdersForAuthUser();
        $this->assertCount(3, $user2Orders);

        // Verify no cross-contamination
        $user1OrderIds = $user1Orders->pluck('id')->toArray();
        $user2OrderIds = $user2Orders->pluck('id')->toArray();
        $this->assertEmpty(array_intersect($user1OrderIds, $user2OrderIds));
    }

    /**
     * Test order service handles staff multi-tenancy correctly.
     * Verifies that staff and admin share the same client orders
     */
    public function test_staff_and_admin_share_client_orders(): void
    {
        // Create client first
        $client = Client::factory()->create();

        // Create admin
        $admin = User::factory()->create([
            'role' => 'admin',
            'client_id' => $client->id
        ]);

        // Create staff belonging to same client
        $staff = User::factory()->create([
            'role' => 'staff',
            'client_id' => $client->id
        ]);

        // OPTION 1: Create orders manually specifying IDs (current implementation)
        Order::factory()->count(2)->create([
            'client_id' => $client->id,
            'user_id' => $admin->id
        ]);
        
        // OPTION 2: Alternative using ->forClient() and ->createdBy() methods (commented out)
        // Order::factory()->count(2)
        //     ->forClient($client)
        //     ->createdBy($admin)
        //     ->create();

        // Test admin sees orders
        $this->actingAs($admin);
        $adminOrders = $this->orderService->getOrdersForAuthUser();
        $this->assertCount(2, $adminOrders);

        // Test staff sees same orders
        $this->actingAs($staff);
        $staffOrders = $this->orderService->getOrdersForAuthUser();
        $this->assertCount(2, $staffOrders);

        // Verify they see the same orders
        $adminOrderIds = $adminOrders->pluck('id')->sort()->values()->toArray();
        $staffOrderIds = $staffOrders->pluck('id')->sort()->values()->toArray();
        $this->assertEquals($adminOrderIds, $staffOrderIds);
    }

    /**
     * Test order service gets order by ID with multi-tenancy.
     * Verifies that users can only access their own orders
     */
    public function test_get_order_by_id_respects_multi_tenancy(): void
    {
        // Create clients first
        $client1 = Client::factory()->create();
        $client2 = Client::factory()->create();

        // Create two users with different clients
        $user1 = User::factory()->create(['role' => 'admin', 'client_id' => $client1->id]);
        $user2 = User::factory()->create(['role' => 'admin', 'client_id' => $client2->id]);

        // OPTION 1: Create order manually specifying IDs (current implementation)
        $order = Order::factory()->create([
            'client_id' => $client2->id,
            'user_id' => $user2->id
        ]);
        
        // OPTION 2: Alternative using ->forClient() and ->createdBy() methods (commented out)
        // $order = Order::factory()
        //     ->forClient($client2)
        //     ->createdBy($user2)
        //     ->create();

        // Test user1 cannot access user2's order
        $this->actingAs($user1);
        $result = $this->orderService->getOrderById($order->id);
        $this->assertNull($result);

        // Test user2 can access their own order
        $this->actingAs($user2);
        $result = $this->orderService->getOrderById($order->id);
        $this->assertInstanceOf(Order::class, $result);
        $this->assertEquals($order->id, $result->id);
    }
}