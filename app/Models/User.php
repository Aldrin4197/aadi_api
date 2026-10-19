<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\LandlordStatus;
use App\Enums\ServiceProviderStatus;
use App\Enums\VehicleOwnerStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name', 
    'email', 
    'password', 
    'landlord_status', 
    'phone', 
    'avatar_url',
    'vehicle_owner_status',
    'vehicle_owner_business_name',
    'vehicle_owner_licenses',
    'vehicle_owner_inventory',
    'vehicle_owner_certificates',
    'service_provider_status',
    'service_provider_business_name',
    'service_provider_skills',
    'service_provider_certifications',
    'service_provider_service_area',
    'service_provider_years_experience',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasUlids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_admin' => 'boolean',
            'password' => 'hashed',
            'landlord_status' => LandlordStatus::class,
            'vehicle_owner_status' => VehicleOwnerStatus::class,
            'service_provider_status' => ServiceProviderStatus::class,
            'rating_average' => 'decimal:2',
            'rating_count' => 'integer',
            'trust_score' => 'integer',
            'vehicle_owner_licenses' => 'array',
            'vehicle_owner_inventory' => 'array',
            'vehicle_owner_certificates' => 'array',
            'service_provider_skills' => 'array',
            'service_provider_certifications' => 'array',
            'service_provider_years_experience' => 'integer',
        ];
    }

    /**
     * Relationships
     */
    public function badges()
    {
        return $this->hasMany(TrustBadge::class);
    }

    public function listings()
    {
        return $this->hasMany(Listing::class, 'owner_id');
    }

    public function vehicleListings()
    {
        return $this->hasMany(VehicleListing::class, 'owner_id');
    }

    public function serviceListings()
    {
        return $this->hasMany(ServiceListing::class, 'provider_id');
    }

    public function favorites()
    {
        return $this->belongsToMany(Listing::class, 'favorites');
    }

    public function savedSearches()
    {
        return $this->hasMany(SavedSearch::class);
    }

    public function tenantViewingRequests()
    {
        return $this->hasMany(ViewingRequest::class, 'tenant_id');
    }

    public function ownerViewingRequests()
    {
        return $this->hasMany(ViewingRequest::class, 'owner_id');
    }
}
