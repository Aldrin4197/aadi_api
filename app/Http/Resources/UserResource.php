<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $landlordStatus = $this->landlord_status instanceof \BackedEnum
            ? $this->landlord_status->value
            : 'none';
        $vehicleOwnerStatus = $this->vehicle_owner_status instanceof \BackedEnum
            ? $this->vehicle_owner_status->value
            : 'none';
        $serviceProviderStatus = $this->service_provider_status instanceof \BackedEnum
            ? $this->service_provider_status->value
            : 'none';

        return [
            'id' => (string) $this->id,
            'email' => $this->email,
            'emailVerified' => $this->hasVerifiedEmail(),
            'name' => $this->name,
            'landlordStatus' => $landlordStatus,
            'vehicleOwnerStatus' => $vehicleOwnerStatus,
            'serviceProviderStatus' => $serviceProviderStatus,
            'phone' => $this->phone,
            'avatarUrl' => $this->avatar_url,
            'badges' => TrustBadgeResource::collection($this->whenLoaded('badges')),
            'ratingAverage' => (float) $this->rating_average,
            'ratingCount' => $this->rating_count,
            'trustScore' => $this->trust_score,
            // Vehicle Owner Details
            'vehicleOwner' => $this->when($vehicleOwnerStatus !== 'none', [
                'businessName' => $this->vehicle_owner_business_name,
                'licenses' => $this->vehicle_owner_licenses,
                'inventory' => $this->vehicle_owner_inventory,
                'certificates' => $this->vehicle_owner_certificates,
            ]),
            // Service Provider Details
            'serviceProvider' => $this->when($serviceProviderStatus !== 'none', [
                'businessName' => $this->service_provider_business_name,
                'skills' => $this->service_provider_skills,
                'certifications' => $this->service_provider_certifications,
                'serviceArea' => $this->service_provider_service_area,
                'yearsExperience' => $this->service_provider_years_experience,
            ]),
        ];
    }
}
