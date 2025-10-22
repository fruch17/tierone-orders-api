<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Generate Invoice Job
 * Simulates asynchronous invoice generation for orders
 * Following Single Responsibility Principle: only handles invoice generation
 * Following Interface Segregation: implements ShouldQueue interface
 */
class GenerateInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     * Prevents infinite retries
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     * Prevents jobs from running indefinitely
     */
    public int $timeout = 60;

    /**
     * Create a new job instance.
     * Following Dependency Injection: receives Order model
     *
     * @param Order $order
     */
    public function __construct(
        public Order $order
    ) {
        // Job is automatically serialized with the order
    }

    /**
     * Execute the job.
     * Simulates invoice generation process
     * Following Single Responsibility: only handles invoice generation logic
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            // Simulate invoice generation process
            $this->simulateInvoiceGeneration();
            
            // Log successful invoice generation
            Log::info('Invoice generated successfully', [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'total' => $this->order->total,
                'user_id' => $this->order->user_id,
            ]);

        } catch (\Exception $e) {
            // Log error and rethrow to trigger retry mechanism
            Log::error('Failed to generate invoice', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Simulate the invoice generation process.
     * In a real application, this would:
     * - Generate PDF invoice
     * - Send email to client
     * - Store invoice in database
     * - Update order status
     * Following Single Responsibility: only handles simulation logic
     *
     * @return void
     */
    private function simulateInvoiceGeneration(): void
    {
        // Simulate processing time
        sleep(2);
        
        // In a real application, you would:
        // 1. Generate PDF invoice
        // 2. Store invoice data in database
        // 3. Send email notification
        // 4. Update order status
        
        // For this simulation, we just log the process
        Log::info('Invoice generation process completed', [
            'order_id' => $this->order->id,
            'invoice_number' => 'INV-' . $this->order->order_number,
            'amount' => $this->order->total,
        ]);
    }

    /**
     * Handle a job failure.
     * Called when job fails after all retries
     * Following Single Responsibility: only handles failure logic
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Invoice generation job failed permanently', [
            'order_id' => $this->order->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
