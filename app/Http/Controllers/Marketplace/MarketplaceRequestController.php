<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceRequest;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\Marketplace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class MarketplaceRequestController extends Controller
{
    public function index(Request $request, Marketplace $market)
    {
        $user = $request->user();
        $filters = $request->validate(['tab' => ['sometimes', Rule::in(['sent', 'received'])]]);
        $isProvider = $user->isApprovedProvider();
        $activeTab = $isProvider ? ($filters['tab'] ?? 'received') : 'sent';
        $requests = MarketplaceRequest::query()
            ->where($activeTab === 'received' ? 'provider_id' : 'customer_id', $user->id)
            ->with(['customer:id,name,email,phone,avatar_path,avatar_url', 'provider:id,name,email,phone,avatar_path,avatar_url'])
            ->latest()->orderByDesc('id')->paginate(20)->withQueryString();
        $listings = [];
        foreach ($requests->getCollection()->groupBy('service') as $service => $items) {
            $imageColumn = $service === 'spaces' ? 'photos' : 'images';
            $typeColumn = match ($service) {
                'spaces' => 'type',
                'haul' => 'vehicle_type',
                default => 'service_type',
            };
            $listings[$service] = $market->model($service)::whereIn('id', $items->pluck('listing_id'))
                ->get(['id', 'status', $market->ownerColumn($service), $imageColumn, $typeColumn])->keyBy('id');
        }
        $requests->through(function (MarketplaceRequest $item) use ($user, $market, $listings) {
                $isProvider = (string) $item->provider_id === (string) $user->id;
                $other = $isProvider ? $item->customer : $item->provider;
                $listing = $listings[$item->service]->get($item->listing_id);
                $imageColumn = $item->service === 'spaces' ? 'photos' : 'images';
                $typeColumn = match ($item->service) {
                    'spaces' => 'type',
                    'haul' => 'vehicle_type',
                    default => 'service_type',
                };
                $photos = $listing?->{$imageColumn} ?? [];
                $listingType = $listing?->{$typeColumn};
                if ($listingType instanceof \BackedEnum) {
                    $listingType = $listingType->value;
                }
                $canViewListing = $listing && ($market->listingStatus($listing, $item->service) === 'active'
                    || $user->is_admin || $market->owns($user, $listing, $item->service));
                return [
                    'id' => $item->id, 'service' => $item->service, 'listingId' => $item->listing_id,
                    'listingTitle' => $item->listing_title, 'isProvider' => $isProvider,
                    'photo' => $photos[0] ?? null, 'listingType' => $listingType,
                    'canViewListing' => (bool) $canViewListing,
                    'requestedAt' => $item->requested_at->format($item->service === 'spaces' ? 'Y-m-d H:i' : 'Y-m-d'),
                    'scheduledDate' => $item->requested_at->format('M j, Y'),
                    'scheduledTime' => $item->service === 'spaces' ? $item->requested_at->format('g:i A') : null,
                    'requestedUntil' => $item->requested_until?->toDateString(),
                    'driverRequested' => $item->driver_requested, 'note' => $item->note,
                    'status' => $item->status, 'otherName' => $other->name,
                    'otherId' => (string) $other->id, 'otherAvatar' => $other->avatar_url,
                    'contact' => $item->status === 'accepted' ? ['email' => $other->email, 'phone' => $other->phone] : null,
                ];
            });

        return Inertia::render('Account/Requests', [
            'requests' => $requests, 'timezone' => config('app.timezone'), 'activeTab' => $activeTab,
        ]);
    }

    public function store(Request $request, Marketplace $market, string $service, string $id)
    {
        $listing = $market->find($service, $id);
        $data = $request->validate([
            'requestedAt' => ['required', $service === 'spaces' ? 'date_format:Y-m-d\TH:i' : 'date_format:Y-m-d', $service === 'spaces' ? 'after:now' : 'after_or_equal:today'],
            'requestedUntil' => $service === 'spaces' ? ['prohibited'] : ['required', 'date_format:Y-m-d', 'after_or_equal:requestedAt'],
            'note' => 'nullable|string|max:2000',
            'driverRequested' => 'sometimes|boolean',
            'shareContact' => 'accepted',
        ]);
        if ($market->listingStatus($listing, $service) !== 'active' || $listing->availability_paused || $listing->is_sample) {
            throw ValidationException::withMessages(['requestedAt' => 'This listing is no longer available for requests.']);
        }
        if ($market->owns($request->user(), $listing, $service)) {
            throw ValidationException::withMessages(['requestedAt' => 'You cannot request your own listing.']);
        }
        if (!empty($data['driverRequested']) && ($service !== 'haul' || !$listing->driver_available)) {
            throw ValidationException::withMessages(['driverRequested' => 'A driver is not available for this listing.']);
        }
        $provider = User::findOrFail($listing->{$market->ownerColumn($service)});
        if ($provider->isRestricted() || !$market->approved($provider, $service)) {
            throw ValidationException::withMessages(['requestedAt' => 'This provider is not currently accepting requests.']);
        }
        if ($service !== 'spaces' && $this->overlaps($service, $id, $data['requestedAt'], $data['requestedUntil'])) {
            throw ValidationException::withMessages(['requestedAt' => 'These dates overlap an accepted booking. Choose different dates.']);
        }

        DB::transaction(function () use ($data, $listing, $provider, $request, $service, $market) {
            $provider = User::whereKey($provider->id)->lockForUpdate()->firstOrFail();
            if ($provider->isRestricted() || !$market->approved($provider, $service)) {
                throw ValidationException::withMessages(['requestedAt' => 'This provider is not currently accepting requests.']);
            }
            $item = MarketplaceRequest::create([
                'service' => $service, 'listing_id' => $listing->id, 'listing_title' => $listing->title,
                'customer_id' => $request->user()->id, 'provider_id' => $provider->id,
                'requested_at' => $data['requestedAt'], 'requested_until' => $data['requestedUntil'] ?? null, 'driver_requested' => $data['driverRequested'] ?? false,
                'note' => $data['note'] ?? null, 'status' => 'pending',
            ]);
            $provider->notify(new AppNotification('request', 'New listing request', 'You have a new request for '.$item->listing_title.'.', '/requests'));
        });

        return redirect('/requests')->with('success', 'Request sent. The provider must accept it before it is confirmed.');
    }

    public function update(Request $request, MarketplaceRequest $marketplaceRequest)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['accept', 'decline', 'cancel'])],
            'shareContact' => $request->input('action') === 'accept' ? ['accepted'] : ['sometimes', 'boolean'],
        ]);
        $user = $request->user();
        DB::transaction(function () use ($marketplaceRequest, $data, $user) {
            $item = MarketplaceRequest::whereKey($marketplaceRequest->id)->lockForUpdate()->firstOrFail();
            $isProvider = (string) $item->provider_id === (string) $user->id;
            $isCustomer = (string) $item->customer_id === (string) $user->id;
            abort_unless($isProvider || $isCustomer, 403);
            $action = $data['action'];
            abort_unless(($isProvider && in_array($action, ['accept', 'decline'], true) && $item->status === 'pending')
                || ($action === 'cancel' && in_array($item->status, ['pending', 'accepted'], true)), 403);

            if ($action === 'accept') {
                $market = app(Marketplace::class);
                $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                $listing = $market->model($item->service)::whereKey($item->listing_id)->lockForUpdate()->first();
                if (!$listing || $listing->is_sample || $user->isRestricted() || !$market->approved($user, $item->service)
                    || $market->dto($listing, $item->service, null, [])['status'] !== 'active'
                    || $listing->availability_paused
                    || (string) $listing->{$market->ownerColumn($item->service)} !== (string) $item->provider_id
                    || ($item->driver_requested && !$listing->driver_available)) {
                    throw ValidationException::withMessages(['action' => 'The listing or requested driver is no longer available. Decline this request instead.']);
                }
                if ($item->service === 'spaces' ? $item->requested_at->isPast() : $item->requested_at->isBefore(today())) {
                    throw ValidationException::withMessages(['action' => 'The requested date has passed. Decline this request instead.']);
                }
                if ($item->service !== 'spaces' && $this->overlaps($item->service, $item->listing_id, $item->requested_at->toDateString(), ($item->requested_until ?? $item->requested_at)->toDateString())) {
                    throw ValidationException::withMessages(['action' => 'These dates overlap an accepted booking. Decline this request instead.']);
                }
            }
            $item->status = match ($action) { 'accept' => 'accepted', 'decline' => 'declined', 'cancel' => 'cancelled' };
            $item->save();
            if ($action === 'accept') app(\App\Services\ProviderRecords::class)->accepted($item);
            if ($action === 'cancel') app(\App\Services\ProviderRecords::class)->cancelled($item);
            $recipient = User::findOrFail($isProvider ? $item->customer_id : $item->provider_id);
            $recipient->notify(new AppNotification('request', 'Listing request updated', 'Your request for '.$item->listing_title.' was '.$item->status.'.', '/requests'));
        });

        return back()->with('success', 'Request updated.');
    }

    private function overlaps(string $service, string $listingId, string $start, string $end): bool
    {
        return MarketplaceRequest::where('service', $service)->where('listing_id', $listingId)
            ->where('status', 'accepted')->whereDate('requested_at', '<=', $end)
            ->where(fn ($query) => $query->whereDate('requested_until', '>=', $start)
                ->orWhere(fn ($query) => $query->whereNull('requested_until')->whereDate('requested_at', '>=', $start)))
            ->exists();
    }
}
