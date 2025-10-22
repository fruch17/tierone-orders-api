<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

/**
 * Client Controller
 * Handles HTTP requests for client-specific operations
 * Following Single Responsibility Principle: only handles HTTP concerns
 * Following Dependency Inversion: depends on Service abstraction
 */
class ClientController extends Controller
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
     * Get all orders for a specific client.
     * Security check: only allows clients to see their own orders
     * Following Single Responsibility: only handles HTTP request/response
     *
     * @param int $id Client ID
     * @return JsonResponse
     */
    public function orders(int $id): JsonResponse
    {
        try {
            // Security check: user can only access orders for their effective client
            $effectiveClientId = auth()->user()->getEffectiveClientId();
            if ($effectiveClientId !== $id) {
                return response()->json([
                    'message' => 'Forbidden. You can only access orders for your client.',
                    'status_code' => 403,
                ], 403);
            }

            // Get client/user information
            $client = User::findOrFail($id);

            // Delegate business logic to service layer
            $orders = $this->orderService->getOrdersForClient($id);

            return response()->json([
                'client' => new UserResource($client),
                'orders' => OrderResource::collection($orders),
                'count' => $orders->count(),
                'status_code' => 200,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Client not found',
                'error' => 'The requested client was not found',
                'status_code' => 404,
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve orders',
                'error' => 'An error occurred while processing your request',
                'status_code' => 500,
            ], 500);
        }
    }
}
