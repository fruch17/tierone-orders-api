<?php

namespace App\Services;

use App\Http\Requests\StoreOrderRequest;
use App\Jobs\GenerateInvoiceJob;
use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Order Service
 * Handles all business logic related to orders
 * Following Single Responsibility Principle: only handles order operations
 * Following Dependency Inversion: depends on abstractions (models, jobs)
 */
class OrderService
{
    /**
     * Create a new order with items.
     * Handles transaction to ensure data consistency
     * Dispatches background job for invoice generation
     * Following Single Responsibility: one method, one purpose
     *
     * @param StoreOrderRequest $request
     * @return Order
     * @throws \Exception
     */
    public function createOrder(StoreOrderRequest $request): Order
    {
        return DB::transaction(function () use ($request) {
            // Create the order
            $order = Order::create([
                'user_id' => auth()->id(), // Multi-tenancy: auto-assign authenticated user
                'tax' => $request->tax,
                'notes' => $request->notes,
                'subtotal' => 0, // Will be calculated after items are created
                'total' => 0,    // Will be calculated after items are created
            ]);

            // Create order items
            foreach ($request->items as $itemData) {
                $order->items()->create([
                    'product_name' => $itemData['product_name'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    // subtotal will be auto-calculated by OrderItem model
                ]);
            }

            // Refresh to get calculated totals
            $order->refresh();

            // Dispatch background job for invoice generation
            GenerateInvoiceJob::dispatch($order);

            return $order;
        });
    }

    /**
     * Get a single order by ID.
     * Ensures multi-tenancy: only returns orders belonging to authenticated user
     * Following Single Responsibility: one method, one purpose
     *
     * @param int $orderId
     * @return Order|null
     */
    public function getOrderById(int $orderId): ?Order
    {
        return Order::forAuthUser()
                   ->with(['items', 'user'])
                   ->find($orderId);
    }

    /**
     * Get all orders for the authenticated user.
     * Multi-tenancy: only returns orders belonging to authenticated user
     * Following Single Responsibility: one method, one purpose
     *
     * @return Collection
     */
    public function getOrdersForAuthUser(): Collection
    {
        return Order::forAuthUser()
                   ->with(['items'])
                   ->latest()
                   ->get();
    }

    /**
     * Get all orders for a specific client.
     * Security check: only allows access to own orders
     * Following Single Responsibility: one method, one purpose
     *
     * @param int $clientId
     * @return Collection
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    public function getOrdersForClient(int $clientId): Collection
    {
        // Security check: client can only access their own orders
        if (auth()->id() !== $clientId) {
            abort(403, 'Forbidden. You can only access your own orders.');
        }

        return Order::where('user_id', $clientId)
                   ->with(['items'])
                   ->latest()
                   ->get();
    }

    /**
     * Calculate order totals from items.
     * Utility method for manual recalculation if needed
     * Following Single Responsibility: one method, one purpose
     *
     * @param Order $order
     * @return Order
     */
    public function recalculateOrderTotals(Order $order): Order
    {
        $order->calculateTotals();
        return $order;
    }
}

