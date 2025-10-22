<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Order Controller
 * Handles HTTP requests for order operations
 * Following Single Responsibility Principle: only handles HTTP concerns
 * Following Dependency Inversion: depends on Service abstraction
 */
class OrderController extends Controller
{
    /**
     * Constructor with dependency injection.
     * Following Dependency Inversion Principle: depends on abstraction
     *
     * @param OrderService $orderService
     */
    public function __construct(
        private OrderService $orderService
    ) {
        // Service is injected automatically by Laravel's container
    }

    /**
     * Create a new order.
     * Delegates business logic to Service layer
     * Following Single Responsibility: only handles HTTP request/response
     *
     * @param StoreOrderRequest $request
     * @return JsonResponse
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            // Delegate business logic to service layer
            $order = $this->orderService->createOrder($request);

            return response()->json([
                'message' => 'Order created successfully',
                'order' => new OrderResource($order->load('items')),
                'status_code' => 201,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create order',
                'error' => 'An error occurred while processing your order',
                'status_code' => 500,
            ], 500);
        }
    }

    /**
     * Get a single order by ID.
     * Ensures multi-tenancy: only returns orders belonging to authenticated user
     * Following Single Responsibility: only handles HTTP request/response
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        // Delegate business logic to service layer
        $order = $this->orderService->getOrderById($id);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found',
                'error' => 'The requested order was not found or you do not have access to it',
                'status_code' => 404,
            ], 404);
        }

        return response()->json([
            'order' => new OrderResource($order),
            'status_code' => 200,
        ], 200);
    }

    /**
     * Get all orders for the authenticated user.
     * Multi-tenancy: only returns orders belonging to authenticated user
     * Following Single Responsibility: only handles HTTP request/response
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // Delegate business logic to service layer
        $orders = $this->orderService->getOrdersForAuthUser();

        return response()->json([
            'orders' => OrderResource::collection($orders),
            'count' => $orders->count(),
            'status_code' => 200,
        ], 200);
    }
}
