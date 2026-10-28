<?php

namespace App\Models;

use App\Enums\ListingCategory;
use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    public function getPhotosAttribute($value): array
    {
        return \App\Services\ListingPhotos::resolve($value);
    }

    use HasFactory, HasUlids;

    protected $fillable = [
        'owner_id',
        'owner_name',
        'title',
        'description',
        'category',
        'type',
        'price',
        'pricing_type',
        'currency',
        'lat',
        'lng',
        'address',
        'city',
        'photos',
        'amenities',
        'attributes',
        'status',
        'property_verification',
    ];

    protected function casts(): array
    {
        return [
            'category' => ListingCategory::class,
            'type' => ListingType::class,
            'status' => ListingStatus::class,
            'property_verification' => VerificationStatus::class,
            'price' => 'decimal:2',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'photos' => 'array',
            'amenities' => 'array',
            'attributes' => 'array',
            'rating_average' => 'decimal:2',
            'rating_count' => 'integer',
        ];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function viewingRequests()
    {
        return $this->hasMany(ViewingRequest::class);
    }

    public function favorites()
    {
        return $this->belongsToMany(User::class, 'favorites');
    }

    public function views()
    {
        return $this->hasMany(ListingView::class);
    }
}
