<?php

namespace App\Http\Controllers\Api;

use App\Enums\ListingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ListingResource;
use App\Models\Listing;
use App\Models\ListingView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListingController extends Controller
{
    public function search(Request $request)
    {
        $validated = $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'radiusKm' => 'required|numeric|min:0',
            'type' => 'nullable|string',
            'minPrice' => 'nullable|numeric|min:0',
            'maxPrice' => 'nullable|numeric|min:0',
            'availableOnly' => 'nullable|boolean',
        ]);

        $lat = $validated['lat'];
        $lng = $validated['lng'];
        $radiusKm = $validated['radiusKm'];
        $availableOnly = $validated['availableOnly'] ?? true;

        // Haversine formula for distance calculation
        $query = Listing::query()
            ->select('*')
            ->selectRaw(
                '(6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) AS distance',
                [$lat, $lng, $lat]
            )
            ->having('distance', '<=', $radiusKm);

        if ($availableOnly) {
            $query->where('status', ListingStatus::ACTIVE->value);
        }

        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (!empty($validated['minPrice'])) {
            $query->where('price', '>=', $validated['minPrice']);
        }

        if (!empty($validated['maxPrice'])) {
            $query->where('price', '<=', $validated['maxPrice']);
        }

        $listings = $query->orderBy('distance')->get();

        return ListingResource::collection($listings);
    }

    public function show(Request $request, string $id)
    {
        $listing = Listing::findOrFail($id);

        // Track view
        ListingView::create([
            'listing_id' => $listing->id,
            'user_id' => $request->user()?->id,
            'ip_address' => $request->ip(),
            'viewed_at' => now(),
        ]);

        return new ListingResource($listing);
    }

    public function listingsByOwner(string $ownerId)
    {
        $listings = Listing::where('owner_id', $ownerId)->get();

        return ListingResource::collection($listings);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|string',
            'price' => 'required|numeric|min:0',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'photos' => 'nullable|array',
            'amenities' => 'nullable|array',
            'status' => 'nullable|string',
        ]);

        $user = $request->user();

        $listing = Listing::create([
            'owner_id' => $user->id,
            'owner_name' => $user->name,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'type' => $validated['type'],
            'price' => $validated['price'],
            'currency' => '₱',
            'lat' => $validated['lat'],
            'lng' => $validated['lng'],
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'photos' => $validated['photos'] ?? [],
            'amenities' => $validated['amenities'] ?? [],
            'status' => $validated['status'] ?? 'active',
        ]);

        return new ListingResource($listing);
    }

    public function update(Request $request, string $id)
    {
        $listing = Listing::findOrFail($id);

        // Authorization
        if ($listing->owner_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'type' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'lat' => 'sometimes|numeric',
            'lng' => 'sometimes|numeric',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'photos' => 'nullable|array',
            'amenities' => 'nullable|array',
            'status' => 'sometimes|string',
        ]);

        $listing->update($validated);

        return new ListingResource($listing->fresh());
    }

    public function destroy(Request $request, string $id)
    {
        $listing = Listing::findOrFail($id);

        // Authorization
        if ($listing->owner_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $listing->delete();

        return response()->noContent();
    }

    public function analytics(string $id)
    {
        $listing = Listing::findOrFail($id);

        $views = $listing->views()->count();
        $interested = $listing->favorites()->count();
        $viewingRequests = $listing->viewingRequests()->count();
        $inquiries = 0; // Placeholder for when messaging is implemented

        $conversionRate = $views > 0 ? $viewingRequests / $views : 0;

        return response()->json([
            'views' => $views,
            'interested' => $interested,
            'viewingRequests' => $viewingRequests,
            'inquiries' => $inquiries,
            'conversionRate' => round($conversionRate, 4),
        ]);
    }
}
