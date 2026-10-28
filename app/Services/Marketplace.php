<?php
namespace App\Services;

use App\Models\{Listing, VehicleListing, ServiceListing, User};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Canonical customer-facing contract over the existing admin/API tables. */
class Marketplace
{
    public const TYPES = [
        'spaces' => ['bedspace', 'boardingHouse', 'apartment', 'house', 'commercial'],
        'haul' => ['multicab', 'van', 'jeepney', 'truck', 'suv', 'motorcycle', 'bicycle'],
        'raketeer' => ['homeCleaning', 'repairService', 'tutoringService', 'eventsStaffing', 'plumbing', 'electrical', 'carpentry', 'cleaning', 'tutoring', 'itSupport', 'moving', 'applianceRepair'],
    ];
    public function model(string $service): string {
        return match($service) { 'spaces' => Listing::class, 'haul' => VehicleListing::class, 'raketeer' => ServiceListing::class, default => abort(404) };
    }
    public function statusField(string $service): string {
        return match($service) { 'spaces' => 'landlord_status', 'haul' => 'vehicle_owner_status', 'raketeer' => 'service_provider_status', default => abort(404) };
    }
    public function approved(User $user, string $service): bool {
        return $user->{$this->statusField($service)}?->value === 'approved';
    }
    public function find(string $service, string $id): Model { return $this->model($service)::findOrFail($id); }
    public function authorize(User $user, Model $record, string $service): void {
        abort_unless($user->is_admin || (string) $record->{$service === 'raketeer' ? 'provider_id' : 'owner_id'} === (string) $user->id, 403);
    }
    public function dto(Model $r, string $service): array {
        $space = $service === 'spaces'; $vehicle = $service === 'haul';
        $status = $r->status instanceof \BackedEnum ? $r->status->value : $r->status;
        return [
            'id' => (string) $r->id, 'service' => $service,
            'ownerId' => (string) ($service === 'raketeer' ? $r->provider_id : $r->owner_id),
            'ownerName' => $service === 'raketeer' ? $r->provider?->name : $r->owner?->name,
            'ownerAvatarUrl' => ($service === 'raketeer' ? $r->provider : $r->owner)?->avatar_url,
            'ownerBio' => ($service === 'raketeer' ? $r->provider : $r->owner)?->bio,
            'providerApproved' => ($service === 'raketeer' ? $r->provider : $r->owner)?->{$this->statusField($service)}?->value === 'approved',
            'title' => $r->title, 'description' => $r->description,
            'type' => $space ? $r->type?->value : ($vehicle ? $r->vehicle_type : $r->service_type),
            'price' => (float) ($space ? $r->price : ($vehicle ? $r->price_per_day : $r->price_amount)),
            'priceUnit' => $space ? 'month' : ($vehicle ? 'day' : ($r->price_type === 'hourly' ? 'hour' : 'job')),
            'currency' => 'PHP',
            'location' => ['lat' => ($space ? $r->lat : $r->latitude) === null ? null : (float) ($space ? $r->lat : $r->latitude),
                'lng' => ($space ? $r->lng : $r->longitude) === null ? null : (float) ($space ? $r->lng : $r->longitude),
                'address' => $space ? $r->address : ($vehicle ? $r->location : $r->service_area), 'city' => $space ? $r->city : null],
            'photos' => ($space ? $r->photos : $r->images) ?? [],
            'status' => $space ? $status : match($status) { 'available' => 'active', 'inactive' => 'draft', 'rented' => 'rented', default => 'reserved' },
            'details' => $vehicle ? ['brand' => $r->brand, 'model' => $r->model, 'year' => $r->year] : [],
            'comparison' => [
                'features' => array_values(array_filter(($space ? $r->amenities : ($vehicle ? $r->features : $r->skills_required)) ?? [], 'is_string')),
                'scope' => $vehicle && $r->seating_capacity ? $r->seating_capacity.' seats' :
                    (!$space && !$vehicle && $r->estimated_duration_minutes ? $r->estimated_duration_minutes.' minutes estimated' : null),
            ],
            'createdAt' => $r->created_at->toIso8601String(),
        ];
    }
    public function search(array $filters): array {
        $service = $filters['service'] ?? 'spaces';
        $space = $service === 'spaces';
        $latColumn = $space ? 'lat' : 'latitude'; $lngColumn = $space ? 'lng' : 'longitude';
        $priceColumn = $space ? 'price' : ($service === 'haul' ? 'price_per_day' : 'price_amount');
        $query = $this->model($service)::query()->with($service === 'raketeer' ? 'provider' : 'owner')
            ->where('status', $space ? 'active' : 'available');
        if ($space) $query->where('category', 'space_rental');
        if (!empty($filters['q'])) $query->where(function ($q) use ($filters, $service) {
            $term = '%'.$filters['q'].'%';
            $q->where('title', 'like', $term)->orWhere($service === 'spaces' ? 'city' : ($service === 'haul' ? 'location' : 'service_area'), 'like', $term);
        });
        if (!empty($filters['type'])) $query->where($space ? 'type' : ($service === 'haul' ? 'vehicle_type' : 'service_type'), $filters['type']);
        foreach (['minPrice' => '>=', 'maxPrice' => '<='] as $key => $operator) {
            if (isset($filters[$key])) $query->where($priceColumn, $operator, $filters[$key]);
        }
        $nearby = isset($filters['lat'], $filters['lng']);
        if ($nearby) {
            $lat = (float) $filters['lat']; $lng = (float) $filters['lng']; $radius = (float) ($filters['radiusKm'] ?? 10);
            // Distance in SQL, clamped for floating-point rounding at identical coordinates.
            $cosine = "cos(radians(?))*cos(radians($latColumn))*cos(radians($lngColumn)-radians(?))+sin(radians(?))*sin(radians($latColumn))";
            $distance = "6371 * acos(CASE WHEN ($cosine) > 1 THEN 1 WHEN ($cosine) < -1 THEN -1 ELSE ($cosine) END)";
            $bindings = array_merge([$lat,$lng,$lat], [$lat,$lng,$lat], [$lat,$lng,$lat]);
            $query->whereNotNull($latColumn)->whereNotNull($lngColumn)->whereRaw("($distance) <= ?", [...$bindings, $radius])
                ->orderByRaw($distance, $bindings);
        } else $query->latest();
        $page = $query->paginate(30)->withQueryString();
        return ['data' => $page->getCollection()->map(fn ($r) => $this->dto($r, $service))->values(),
            'meta' => ['total' => $page->total(), 'currentPage' => $page->currentPage(), 'lastPage' => $page->lastPage()]];
    }
    public function validated(Request $request, string $service): array {
        $this->model($service);
        return $request->validate([
            'title' => 'required|string|max:255', 'description' => 'required|string|max:10000',
            'type' => ['required', Rule::in(self::TYPES[$service])], 'price' => 'required|numeric|min:0|max:99999999.99',
            'status' => ['required', Rule::in($service === 'spaces' ? ['draft','active','reserved','rented','archived'] : ($service === 'haul' ? ['draft','active','reserved','rented'] : ['draft','active','reserved']))],
            'location.lat' => 'required|numeric|between:-90,90', 'location.lng' => 'required|numeric|between:-180,180',
            'location.address' => 'required|string|max:255', 'location.city' => 'nullable|string|max:255',
            'photos' => 'present|array|max:12', 'photos.*' => 'required|url:http,https|max:2048',
            'details.brand' => ($service === 'haul' ? 'required' : 'nullable').'|string|max:100',
            'details.model' => ($service === 'haul' ? 'required' : 'nullable').'|string|max:100',
            'details.year' => ($service === 'haul' ? 'required' : 'nullable').'|integer|min:1950|max:'.(date('Y')+1),
            'priceUnit' => ['nullable', Rule::in(['month','day','job','hour'])],
        ]);
    }
    public function save(User $user, string $service, array $data, ?Model $record = null): Model {
        if ($record) $this->authorize($user, $record, $service);
        abort_unless($user->is_admin || $this->approved($user, $service), 403, 'Apply and get approved for this provider role before managing listings.');
        $owner = $record ? User::findOrFail($record->{$service === 'raketeer' ? 'provider_id' : 'owner_id'}) : $user;
        abort_if($data['status'] === 'active' && !$this->approved($owner, $service), 403, 'Provider approval is required to publish. Save a draft while your application is reviewed.');
        if ($service === 'spaces') $status = $data['status'];
        else $status = match($data['status']) { 'active' => 'available', 'draft' => 'inactive', 'rented' => 'rented', default => $service === 'haul' ? 'maintenance' : 'busy' };
        $base = ['title' => $data['title'], 'description' => $data['description'], 'status' => $status];
        $location = $data['location'];
        $fields = match($service) {
            'spaces' => ['owner_id'=>$owner->id,'owner_name'=>$owner->name,'category'=>'space_rental','type'=>$data['type'],'price'=>$data['price'],'lat'=>$location['lat'],'lng'=>$location['lng'],'address'=>$location['address'],'city'=>$location['city'] ?? null,'photos'=>$data['photos']],
            'haul' => ['owner_id'=>$owner->id,'vehicle_type'=>$data['type'],'price_per_day'=>$data['price'],'location'=>$location['address'],'latitude'=>$location['lat'],'longitude'=>$location['lng'],'images'=>$data['photos'],'brand'=>$data['details']['brand'],'model'=>$data['details']['model'],'year'=>$data['details']['year']],
            'raketeer' => ['provider_id'=>$owner->id,'service_type'=>$data['type'],'price_amount'=>$data['price'],'price_type'=>($data['priceUnit'] ?? 'job') === 'hour' ? 'hourly' : 'fixed','service_area'=>$location['address'],'latitude'=>$location['lat'],'longitude'=>$location['lng'],'images'=>$data['photos']],
        };
        $record ??= new ($this->model($service));
        $record->fill([...$base, ...$fields])->save();
        return $record->fresh();
    }
}
