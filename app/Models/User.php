<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
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
    'bio', 'city', 'address', 'birth_date', 'sex_assigned_at_birth',
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
#[Hidden(['password', 'remember_token', 'address', 'birth_date', 'sex_assigned_at_birth', 'avatar_path', 'onboarding_data', 'onboarding_status', 'aadi_plus_until'])]
class User extends Authenticatable implements MustVerifyEmail
{
    public function getAvatarUrlAttribute($value): ?string
    {
        $path = $this->attributes['avatar_path'] ?? null;
        return $path ? route('avatars.show', ['user'=>$this->id, 'filename'=>basename($path)]) : $value;
    }

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
            'onboarding_data' => 'array',
            'is_admin' => 'boolean',
            'password' => 'hashed',
            'landlord_status' => LandlordStatus::class,
            'vehicle_owner_status' => VehicleOwnerStatus::class,
            'service_provider_status' => ServiceProviderStatus::class,
            'rating_average' => 'decimal:2',
            'rating_count' => 'integer',
            'trust_score' => 'integer',
            'restricted_until' => 'datetime',
            'deletion_scheduled_for' => 'datetime',
            'aadi_plus_until' => 'datetime',
            'vehicle_owner_licenses' => 'array',
            'vehicle_owner_inventory' => 'array',
            'vehicle_owner_certificates' => 'array',
            'service_provider_skills' => 'array',
            'service_provider_certifications' => 'array',
            'service_provider_years_experience' => 'integer',
        ];
    }

    /** True once any provider role (landlord, vehicle owner, service provider) has been approved. */
    public function isApprovedProvider(): bool
    {
        return collect([$this->landlord_status, $this->vehicle_owner_status, $this->service_provider_status])
            ->contains(fn ($status) => $status?->value === 'approved');
    }

    public function isScheduledForDeletion(): bool
    {
        return $this->deletion_scheduled_for !== null;
    }

    public function isRestricted(): bool
    {
        return $this->isScheduledForDeletion()
            || $this->restriction_type === 'disabled'
            || ($this->restriction_type === 'locked' && $this->restricted_until !== null && $this->restricted_until->isFuture());
    }

    public function hasAadiPlus(): bool
    {
        return $this->aadi_plus_until?->isFuture() ?? false;
    }

    public function hasCompleteProfile(): bool
    {
        return filled($this->birth_date)
            && in_array($this->sex_assigned_at_birth, \App\Support\ProfileFields::SEX_OPTIONS, true)
            && filled($this->city)
            && filled($this->address);
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
