<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleListingResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'ownerId' => (string) $this->owner_id,
            'ownerName' => $this->owner->name ?? null,
            'ownerAvatar' => $this->owner->avatar_url ?? null,
            'title' => $this->title,
            'description' => $this->description,
            'vehicleType' => $this->vehicle_type,
            'brand' => $this->brand,
            'model' => $this->model,
            'year' => $this->year,
            'plateNumber' => $this->plate_number,
            'seatingCapacity' => $this->seating_capacity,
            'fuelType' => $this->fuel_type,
            'transmission' => $this->transmission,
            'features' => $this->features ?? [],
            'pricePerDay' => (float) $this->price_per_day,
            'pricePerWeek' => $this->price_per_week ? (float) $this->price_per_week : null,
            'pricePerMonth' => $this->price_per_month ? (float) $this->price_per_month : null,
            'currency' => $this->currency,
            'location' => $this->location,
            'latitude' => $this->latitude ? (float) $this->latitude : null,
            'longitude' => $this->longitude ? (float) $this->longitude : null,
            'distance' => $this->when(isset($this->distance), fn() => round($this->distance, 2)),
            'status' => $this->status,
            'images' => $this->images ?? [],
            'viewsCount' => $this->views_count,
            'ratingAverage' => (float) $this->rating_average,
            'ratingCount' => $this->rating_count,
            'availableFrom' => $this->available_from?->toISOString(),
            'availableTo' => $this->available_to?->toISOString(),
            'createdAt' => $this->created_at->toISOString(),
            'updatedAt' => $this->updated_at->toISOString(),
        ];
    }
}
