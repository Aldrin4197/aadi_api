<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceListingResource extends JsonResource
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
            'providerId' => (string) $this->provider_id,
            'providerName' => $this->provider->name ?? null,
            'providerAvatar' => $this->provider->avatar_url ?? null,
            'title' => $this->title,
            'description' => $this->description,
            'serviceType' => $this->service_type,
            'serviceCategory' => $this->service_category,
            'skillsRequired' => $this->skills_required ?? [],
            'priceType' => $this->price_type,
            'priceAmount' => (float) $this->price_amount,
            'currency' => $this->currency,
            'serviceArea' => $this->service_area,
            'availabilitySchedule' => $this->availability_schedule ?? [],
            'responseTimeMinutes' => $this->response_time_minutes,
            'estimatedDurationMinutes' => $this->estimated_duration_minutes,
            'status' => $this->status,
            'images' => $this->images ?? [],
            'certifications' => $this->certifications ?? [],
            'viewsCount' => $this->views_count,
            'ratingAverage' => (float) $this->rating_average,
            'ratingCount' => $this->rating_count,
            'completionCount' => $this->completion_count,
            'createdAt' => $this->created_at->toISOString(),
            'updatedAt' => $this->updated_at->toISOString(),
        ];
    }
}
