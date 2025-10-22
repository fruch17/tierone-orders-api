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
        'client_id',
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

        // Auto-assign client_id and user_id from authenticated user (Multi-tenancy + Audit)
        static::creating(function (Order $order) {
            if (auth()->check() && !$order->client_id) {
                $order->client_id = auth()->user()->getEffectiveClientId();
            }
            
            if (auth()->check() && !$order->user_id) {
                $order->user_id = auth()->id(); // Track who created the order
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
     * Get the user that created the order.
     * Defines the relationship with User model for audit trail
     * This tracks who specifically created the order
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the client that owns the order.
     * Defines the relationship with User model for multi-tenancy
     * Both admin and staff can access orders for the same client
     *
     * @return BelongsTo
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id', 'id');
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
     * Scope to filter orders by authenticated user's client.
     * Ensures multi-tenancy: users only see orders for their client
     * Admin sees their own orders, staff sees their admin's orders
     * Usage: Order::forAuthClient()->get()
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForAuthClient($query)
    {
        if (auth()->check()) {
            $clientId = auth()->user()->getEffectiveClientId();
            return $query->where('client_id', $clientId);
        }
        
        return $query;
    }

    /**
     * Scope to filter orders by authenticated user (legacy method).
     * This is now an alias for scopeForAuthClient()
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForAuthUser($query)
    {
        return $this->scopeForAuthClient($query);
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
