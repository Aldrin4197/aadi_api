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
        $requests = MarketplaceRequest::query()
            ->where(fn ($query) => $query->where('customer_id', $user->id)->orWhere('provider_id', $user->id))
            ->with(['customer:id,name,email,phone', 'provider:id,name,email,phone'])
            ->latest()->orderByDesc('id')->paginate(20);
        $listings = [];
        foreach ($requests->getCollection()->groupBy('service') as $service => $items) {
            $listings[$service] = $market->model($service)::whereIn('id', $items->pluck('listing_id'))
                ->get(['id', 'status', $market->ownerColumn($service)])->keyBy('id');
        }
        $requests->through(function (MarketplaceRequest $item) use ($user, $market, $listings) {
                $isProvider = (string) $item->provider_id === (string) $user->id;
                $other = $isProvider ? $item->customer : $item->provider;
                $listing = $listings[$item->service]->get($item->listing_id);
                $canViewListing = $listing && ($market->listingStatus($listing, $item->service) === 'active'
                    || $user->is_admin || $market->owns($user, $listing, $item->service));
                return [
                    'id' => $item->id, 'service' => $item->service, 'listingId' => $item->listing_id,
                    'listingTitle' => $item->listing_title, 'isProvider' => $isProvider,
                    'canViewListing' => (bool) $canViewListing,
                    'requestedAt' => $item->requested_at->format($item->service === 'spaces' ? 'Y-m-d H:i' : 'Y-m-d'),
                    'driverRequested' => $item->driver_requested, 'note' => $item->note,
                    'status' => $item->status, 'otherName' => $other->name,
                    'contact' => $item->status === 'accepted' ? ['email' => $other->email, 'phone' => $other->phone] : null,
                ];
            });

        return Inertia::render('Account/Requests', ['requests' => $requests, 'timezone' => config('app.timezone')]);
    }

    public function store(Request $request, Marketplace $market, string $service, string $id)
    {
        $listing = $market->find($service, $id);
        $data = $request->validate([
            'requestedAt' => ['required', $service === 'spaces' ? 'date_format:Y-m-d\TH:i' : 'date_format:Y-m-d', $service === 'spaces' ? 'after:now' : 'after_or_equal:today'],
            'note' => 'nullable|string|max:2000',
            'driverRequested' => 'sometimes|boolean',
            'shareContact' => 'accepted',
        ]);
        if ($market->dto($listing, $service)['status'] !== 'active' || $listing->is_sample) {
            throw ValidationException::withMessages(['requestedAt' => 'This listing is no longer available for requests.']);
        }
        if ($market->owns($request->user(), $listing, $service)) {
            throw ValidationException::withMessages(['requestedAt' => 'You cannot request your own listing.']);
        }
        if (!empty($data['driverRequested']) && ($service !== 'haul' || !$listing->driver_available)) {
            throw ValidationException::withMessages(['driverRequested' => 'A driver is not available for this listing.']);
        }
        $provider = User::findOrFail($listing->{$market->ownerColumn($service)});
        if (!$market->approved($provider, $service)) {
            throw ValidationException::withMessages(['requestedAt' => 'This provider is not currently accepting requests.']);
        }

        DB::transaction(function () use ($data, $listing, $provider, $request, $service) {
            $item = MarketplaceRequest::create([
                'service' => $service, 'listing_id' => $listing->id, 'listing_title' => $listing->title,
                'customer_id' => $request->user()->id, 'provider_id' => $provider->id,
                'requested_at' => $data['requestedAt'], 'driver_requested' => $data['driverRequested'] ?? false,
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
                $listing = $market->model($item->service)::find($item->listing_id);
                if (!$listing || $listing->is_sample || !$market->approved($user, $item->service)
                    || $market->dto($listing, $item->service)['status'] !== 'active'
                    || (string) $listing->{$market->ownerColumn($item->service)} !== (string) $item->provider_id
                    || ($item->driver_requested && !$listing->driver_available)) {
                    throw ValidationException::withMessages(['action' => 'The listing or requested driver is no longer available. Decline this request instead.']);
                }
                if ($item->service === 'spaces' ? $item->requested_at->isPast() : $item->requested_at->isBefore(today())) {
                    throw ValidationException::withMessages(['action' => 'The requested date has passed. Decline this request instead.']);
                }
            }
            $item->status = match ($action) { 'accept' => 'accepted', 'decline' => 'declined', 'cancel' => 'cancelled' };
            $item->save();
            $recipient = User::findOrFail($isProvider ? $item->customer_id : $item->provider_id);
            $recipient->notify(new AppNotification('request', 'Listing request updated', 'Your request for '.$item->listing_title.' was '.$item->status.'.', '/requests'));
        });

        return back()->with('success', 'Request updated.');
    }
}
