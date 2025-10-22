<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * Following mass assignment protection (Security best practice)
     *
     * @var array<string>
     */
    protected $fillable = [
        'order_id',
        'product_name',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    /**
     * The attributes that should be cast.
     * Ensures proper data types when retrieving from database
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Boot method to handle model events.
     * Auto-calculates subtotal before saving
     * Following Single Responsibility: calculation in one place
     */
    protected static function boot(): void
    {
        parent::boot();

        // Auto-calculate subtotal when creating or updating
        static::saving(function (OrderItem $item) {
            $item->calculateSubtotal();
        });

        // Recalculate order totals after item is saved
        static::saved(function (OrderItem $item) {
            $item->order->calculateTotals();
        });

        // Recalculate order totals after item is deleted
        static::deleted(function (OrderItem $item) {
            $item->order->calculateTotals();
        });
    }

    /**
     * Get the order that owns the item.
     * Defines the inverse of hasMany relationship
     *
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Calculate the subtotal for this item.
     * Following Single Responsibility: calculation logic isolated
     * Subtotal = quantity × unit_price
     *
     * @return void
     */
    public function calculateSubtotal(): void
    {
        $this->subtotal = $this->quantity * $this->unit_price;
    }

    /**
     * Get the formatted unit price.
     * Helper method for display purposes
     *
     * @return string
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        return '$' . number_format($this->unit_price, 2);
    }

    /**
     * Get the formatted subtotal.
     * Helper method for display purposes
     *
     * @return string
     */
    public function getFormattedSubtotalAttribute(): string
    {
        return '$' . number_format($this->subtotal, 2);
    }
}
