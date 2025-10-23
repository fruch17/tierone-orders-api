<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_name',
        'company_email',
    ];

    /**
     * Get the orders for the client.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'client_id');
    }

    /**
     * Get the users (staff) for the client.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'client_id');
    }
}
