<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListingResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ownerId' => (string) $this->owner_id,
            'ownerName' => $this->owner_name,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category instanceof \BackedEnum ? $this->category->value : $this->category,
            'type' => $this->type instanceof \BackedEnum ? $this->type->value : $this->type,
            'price' => (float) $this->price,
            'pricingType' => $this->pricing_type ?? 'fixed',
            'currency' => $this->currency,
            'location' => [
                'lat' => (float) $this->lat,
                'lng' => (float) $this->lng,
                'address' => $this->address,
                'city' => $this->city,
            ],
            'photos' => $this->photos ?? [],
            'amenities' => $this->amenities ?? [],
            'attributes' => $this->attributes ?? [],
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'propertyVerification' => $this->property_verification instanceof \BackedEnum ? $this->property_verification->value : $this->property_verification,
            'ratingAverage' => (float) $this->rating_average,
            'ratingCount' => $this->rating_count,
            'createdAt' => $this->created_at->toIso8601String(),
            'distance' => $this->when(isset($this->distance), fn() => round($this->distance, 2)),
        ];
    }
}
