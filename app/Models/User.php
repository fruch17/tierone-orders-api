<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     * Following mass assignment protection (Security best practice)
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'company_name',
        'email',
        'password',
        'role',
        'client_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     * Prevents sensitive data from being exposed in API responses
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     * Ensures proper data types when retrieving from database
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => 'string',
            'client_id' => 'integer',
        ];
    }

    /**
     * Get the orders for the user's client.
     * Defines one-to-many relationship with Order for multi-tenancy
     * Admin users see their own orders, staff see their admin's orders
     *
     * @return HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'client_id', 'id');
    }

    /**
     * Get orders created by this specific user.
     * Different from orders() - this shows orders created by the user themselves
     *
     * @return HasMany
     */
    public function createdOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'client_id', 'id');
    }

    /**
     * Check if user is admin.
     * Admin users can manage staff and have full access
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is staff.
     * Staff users have limited access to their own data
     *
     * @return bool
     */
    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    /**
     * Check if user can manage other users.
     * Only admin users can manage staff
     *
     * @return bool
     */
    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Get the effective client ID for multi-tenancy.
     * Admin users use their own ID as client_id
     * Staff users use their client_id field
     *
     * @return int
     */
    public function getEffectiveClientId(): int
    {
        if ($this->isAdmin()) {
            return $this->id; // Admin is their own client
        }
        
        return $this->client_id; // Staff belongs to admin's client
    }

    /**
     * Check if user belongs to a specific client.
     * Used for multi-tenancy data isolation
     *
     * @param int $clientId
     * @return bool
     */
    public function belongsToClient(int $clientId): bool
    {
        return $this->getEffectiveClientId() === $clientId;
    }

    /**
     * Get all staff members belonging to this admin.
     * Only admin users have staff members
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function staff(): HasMany
    {
        return $this->hasMany(User::class, 'client_id', 'id')
                    ->where('role', 'staff');
    }

    /**
     * Get the admin user that this staff belongs to.
     * Only staff users have an admin
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id', 'id')
                    ->where('role', 'admin');
    }
}
