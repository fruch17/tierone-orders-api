<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user can create order with items.
     * Verifies complete order creation flow
     */
    public function test_user_can_create_order(): void
    {
        // Create client first
        $client = Client::factory()->create();
        
        // Create authenticated user
        $user = User::factory()->create([
            'role' => 'admin',
            'client_id' => $client->id
        ]);

        $orderData = [
            'tax' => 15.50,
            'notes' => 'Urgent delivery',
            'items' => [
                [
                    'product_name' => 'Laptop',
                    'quantity' => 2,
                    'unit_price' => 1200.00
                ],
                [
                    'product_name' => 'Mouse',
                    'quantity' => 1,
                    'unit_price' => 25.00
                ]
            ]
        ];

        $response = $this->actingAs($user)
                         ->postJson('/api/orders', $orderData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'order' => [
                        'id',
                        'order_number',
                        'client_id',
                        'user_id',
                        'subtotal',
                        'tax',
                        'total',
                        'notes',
                        'items' => [
                            '*' => [
                                'id',
                                'product_name',
                                'quantity',
                                'unit_price',
                                'subtotal'
                            ]
                        ]
                    ],
                    'status_code'
                ]);

        // Verify order was created with correct totals
        $this->assertEquals(2425.00, $response->json('order.subtotal')); // (2*1200) + (1*25)
        $this->assertEquals(15.50, $response->json('order.tax'));
        $this->assertEquals(2440.50, $response->json('order.total')); // subtotal + tax

        // Verify order was created in database
        $this->assertDatabaseHas('orders', [
            'client_id' => $user->client_id,
            'user_id' => $user->id,
            'tax' => 15.50,
            'notes' => 'Urgent delivery'
        ]);
    }

    /**
     * Test user can get their orders.
     * Verifies multi-tenancy and data access
     */
    public function test_user_can_get_orders(): void
    {
        // Create client first
        $client = Client::factory()->create();
        
        // Create user and orders
        $user = User::factory()->create(['role' => 'admin', 'client_id' => $client->id]);
        
        // OPTION 1: Create orders manually specifying IDs (current implementation)
        Order::factory()->count(3)->create([
            'client_id' => $client->id,
            'user_id' => $user->id
        ]);
        
        // OPTION 2: Alternative using ->forClient() and ->createdBy() methods (commented out)
        // Order::factory()->count(3)
        //     ->forClient($client)
        //     ->createdBy($user)
        //     ->create();

        $response = $this->actingAs($user)
                         ->getJson('/api/orders');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'orders' => [
                        '*' => [
                            'id',
                            'order_number',
                            'client_id',
                            'user_id',
                            'subtotal',
                            'tax',
                            'total'
                        ]
                    ],
                    'count',
                    'status_code'
                ]);

        $this->assertEquals(3, $response->json('count'));
    }

    /**
     * Test user can get single order.
     * Verifies order retrieval and multi-tenancy
     */
    public function test_user_can_get_single_order(): void
    {
        // Create client first
        $client = Client::factory()->create();
        
        // Create user and order
        $user = User::factory()->create(['role' => 'admin', 'client_id' => $client->id]);
        
        // OPTION 1: Create order manually specifying IDs (current implementation)
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id
        ]);
        
        // OPTION 2: Alternative using ->forClient() and ->createdBy() methods (commented out)
        // $order = Order::factory()
        //     ->forClient($client)
        //     ->createdBy($user)
        //     ->create();

        $response = $this->actingAs($user)
                         ->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'order' => [
                        'id',
                        'order_number',
                        'client_id',
                        'user_id',
                        'subtotal',
                        'tax',
                        'total',
                        'items'
                    ],
                    'status_code'
                ]);

        $this->assertEquals($order->id, $response->json('order.id'));
    }

    /**
     * Test user cannot access other user's orders.
     * Verifies multi-tenancy security
     */
    public function test_user_cannot_access_other_user_orders(): void
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

        // Try to access user2's order as user1
        $response = $this->actingAs($user1)
                         ->getJson("/api/orders/{$order->id}");

        $response->assertStatus(404)
                ->assertJson([
                    'message' => 'Order not found',
                    'status_code' => 404
                ]);
    }

    /**
     * Test admin and staff can access same orders.
     * Verifies multi-tenancy with roles
     */
    public function test_admin_and_staff_share_orders(): void
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

        // OPTION 1: Create order manually specifying IDs (current implementation)
        $order = Order::factory()->create([
            'client_id' => $client->id,
            'user_id' => $admin->id
        ]);
        
        // OPTION 2: Alternative using ->forClient() and ->createdBy() methods (commented out)
        // $order = Order::factory()
        //     ->forClient($client)
        //     ->createdBy($admin)
        //     ->create();

        // Both admin and staff should be able to access the order
        $adminResponse = $this->actingAs($admin)
                              ->getJson("/api/orders/{$order->id}");
        $adminResponse->assertStatus(200);

        $staffResponse = $this->actingAs($staff)
                              ->getJson("/api/orders/{$order->id}");
        $staffResponse->assertStatus(200);

        // Both should see the same order
        $this->assertEquals(
            $adminResponse->json('order.id'),
            $staffResponse->json('order.id')
        );
    }

    /**
     * Test unauthenticated user cannot create orders.
     * Verifies authentication requirement
     */
    public function test_unauthenticated_user_cannot_create_order(): void
    {
        $orderData = [
            'tax' => 10.00,
            'notes' => 'Test order',
            'items' => [
                [
                    'product_name' => 'Product',
                    'quantity' => 1,
                    'unit_price' => 100.00
                ]
            ]
        ];

        $response = $this->postJson('/api/orders', $orderData);

        $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Unauthenticated.',
                    'status_code' => 401
                ]);
    }
}