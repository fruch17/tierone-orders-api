<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * Following mass assignment protection (Security best practice)
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'order_number',
        'subtotal',
        'tax',
        'total',
        'notes',
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
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Boot method to handle model events.
     * Auto-generates order number and assigns authenticated user
     * Following Single Responsibility: each concern in its own method
     */
    protected static function boot(): void
    {
        parent::boot();

        // Auto-assign user_id from authenticated user (Multi-tenancy)
        static::creating(function (Order $order) {
            if (auth()->check() && !$order->user_id) {
                $order->user_id = auth()->id();
            }

            // Auto-generate unique order number if not provided
            if (!$order->order_number) {
                $order->order_number = static::generateOrderNumber();
            }
        });
    }

    /**
     * Generate a unique order number.
     * Format: ORD-YYYYMMDD-XXXX
     * Following Open/Closed Principle: can be overridden in child classes
     *
     * @return string
     */
    public static function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(4));
        
        return "ORD-{$date}-{$random}";
    }

    /**
     * Get the user that owns the order.
     * Defines the inverse of hasMany relationship
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items for the order.
     * Defines one-to-many relationship with OrderItem
     *
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Scope to filter orders by authenticated user.
     * Ensures multi-tenancy: users only see their own orders
     * Usage: Order::forAuthUser()->get()
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForAuthUser($query)
    {
        if (auth()->check()) {
            return $query->where('user_id', auth()->id());
        }
        
        return $query;
    }

    /**
     * Calculate and update order totals from items.
     * Following Single Responsibility: calculation logic in one place
     * Can be called after adding/updating items
     *
     * @return void
     */
    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum('subtotal');
        $this->total = $this->subtotal + $this->tax;
        $this->save();
    }
}
