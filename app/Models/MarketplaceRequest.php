<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class MarketplaceRequest extends Model
{
    use HasUlids;

    protected $fillable = [
        'service', 'listing_id', 'listing_title', 'customer_id', 'provider_id',
        'requested_at', 'driver_requested', 'note', 'status',
    ];

    protected function casts(): array
    {
        return ['requested_at' => 'datetime', 'driver_requested' => 'boolean'];
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }
}
