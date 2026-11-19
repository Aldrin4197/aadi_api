<?php

namespace App\Models;

use App\Models\Concerns\TracksMarketplacePublication;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleListing extends Model
{
    public function getImagesAttribute($value): array
    {
        return \App\Services\ListingPhotos::resolve($value);
    }

    use HasUlids, SoftDeletes, TracksMarketplacePublication;

    protected $fillable = [
        'owner_id',
        'title',
        'description',
        'vehicle_type',
        'brand',
        'model',
        'year',
        'plate_number',
        'seating_capacity',
        'fuel_type',
        'transmission',
        'features',
        'price_per_day',
        'price_per_week',
        'price_per_month',
        'currency',
        'location',
        'latitude',
        'longitude',
        'status',
        'images',
        'views_count',
        'rating_average',
        'rating_count',
        'available_from',
        'available_to',
        'driver_available',
    ];

    protected $casts = [
        'features' => 'array',
        'images' => 'array',
        'price_per_day' => 'decimal:2',
        'price_per_week' => 'decimal:2',
        'price_per_month' => 'decimal:2',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'rating_average' => 'decimal:2',
        'views_count' => 'integer',
        'rating_count' => 'integer',
        'year' => 'integer',
        'seating_capacity' => 'integer',
        'available_from' => 'date',
        'available_to' => 'date',
        'published_at' => 'datetime',
        'driver_available' => 'boolean',
    ];

    // Relationships
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
