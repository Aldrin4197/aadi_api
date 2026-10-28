<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceListing extends Model
{
    public function getImagesAttribute($value): array
    {
        return \App\Services\ListingPhotos::resolve($value);
    }

    use HasUlids, SoftDeletes;

    protected $fillable = [
        'provider_id',
        'latitude',
        'longitude',
        'title',
        'description',
        'service_type',
        'service_category',
        'skills_required',
        'price_type',
        'price_amount',
        'currency',
        'service_area',
        'availability_schedule',
        'response_time_minutes',
        'estimated_duration_minutes',
        'status',
        'images',
        'certifications',
        'views_count',
        'rating_average',
        'rating_count',
        'completion_count',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'skills_required' => 'array',
        'availability_schedule' => 'array',
        'images' => 'array',
        'certifications' => 'array',
        'price_amount' => 'decimal:2',
        'rating_average' => 'decimal:2',
        'views_count' => 'integer',
        'rating_count' => 'integer',
        'completion_count' => 'integer',
        'response_time_minutes' => 'integer',
        'estimated_duration_minutes' => 'integer',
    ];

    // Relationships
    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }
}
