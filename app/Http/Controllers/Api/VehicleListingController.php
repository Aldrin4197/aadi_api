<?php

namespace App\Http\Controllers\Api;

use App\Enums\VehicleOwnerStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\VehicleListingResource;
use App\Models\VehicleListing;
use Illuminate\Http\Request;

class VehicleListingController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleListing::with('owner');

        // Filter by vehicle type
        if ($request->filled('vehicleType')) {
            $query->where('vehicle_type', $request->vehicleType);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by price range
        if ($request->filled('minPrice')) {
            $query->where('price_per_day', '>=', $request->minPrice);
        }
        if ($request->filled('maxPrice')) {
            $query->where('price_per_day', '<=', $request->maxPrice);
        }

        // Location-based search with distance
        if ($request->filled('latitude') && $request->filled('longitude')) {
            $lat = $request->latitude;
            $lng = $request->longitude;
            $radius = $request->radius ?? 50; // Default 50km

            $query->select('vehicle_listings.*')
                ->selectRaw(
                    '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance',
                    [$lat, $lng, $lat]
                )
                ->having('distance', '<=', $radius)
                ->orderBy('distance');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $listings = $query->paginate(20);

        return VehicleListingResource::collection($listings);
    }

    public function show(string $id)
    {
        $listing = VehicleListing::with('owner')->findOrFail($id);

        // Increment views
        $listing->increment('views_count');

        return new VehicleListingResource($listing);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        // Check if user is approved vehicle owner
        if ($user->vehicle_owner_status !== VehicleOwnerStatus::APPROVED) {
            return response()->json([
                'message' => 'You must be an approved vehicle owner to create vehicle listings.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'vehicleType' => 'required|string',
            'brand' => 'required|string',
            'model' => 'required|string',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'plateNumber' => 'nullable|string',
            'seatingCapacity' => 'nullable|integer|min:1',
            'fuelType' => 'nullable|string',
            'transmission' => 'nullable|string',
            'features' => 'nullable|array',
            'pricePerDay' => 'required|numeric|min:0',
            'pricePerWeek' => 'nullable|numeric|min:0',
            'pricePerMonth' => 'nullable|numeric|min:0',
            'location' => 'required|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'images' => 'nullable|array',
            'availableFrom' => 'nullable|date',
            'availableTo' => 'nullable|date|after_or_equal:availableFrom',
        ]);

        $listing = VehicleListing::create([
            'owner_id' => $user->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'vehicle_type' => $validated['vehicleType'],
            'brand' => $validated['brand'],
            'model' => $validated['model'],
            'year' => $validated['year'],
            'plate_number' => $validated['plateNumber'] ?? null,
            'seating_capacity' => $validated['seatingCapacity'] ?? null,
            'fuel_type' => $validated['fuelType'] ?? null,
            'transmission' => $validated['transmission'] ?? null,
            'features' => $validated['features'] ?? [],
            'price_per_day' => $validated['pricePerDay'],
            'price_per_week' => $validated['pricePerWeek'] ?? null,
            'price_per_month' => $validated['pricePerMonth'] ?? null,
            'location' => $validated['location'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'images' => $validated['images'] ?? [],
            'available_from' => $validated['availableFrom'] ?? null,
            'available_to' => $validated['availableTo'] ?? null,
        ]);

        return new VehicleListingResource($listing->fresh()->load('owner'));
    }

    public function update(Request $request, string $id)
    {
        $listing = VehicleListing::findOrFail($id);

        if ($listing->owner_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You do not have permission to update this listing.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'vehicleType' => 'sometimes|string',
            'brand' => 'sometimes|string',
            'model' => 'sometimes|string',
            'year' => 'sometimes|integer|min:1900|max:' . (date('Y') + 1),
            'plateNumber' => 'nullable|string',
            'seatingCapacity' => 'nullable|integer|min:1',
            'fuelType' => 'nullable|string',
            'transmission' => 'nullable|string',
            'features' => 'nullable|array',
            'pricePerDay' => 'sometimes|numeric|min:0',
            'pricePerWeek' => 'nullable|numeric|min:0',
            'pricePerMonth' => 'nullable|numeric|min:0',
            'location' => 'sometimes|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'sometimes|in:available,rented,maintenance,inactive',
            'images' => 'nullable|array',
            'availableFrom' => 'nullable|date',
            'availableTo' => 'nullable|date|after_or_equal:availableFrom',
        ]);

        $updateData = [];
        if (isset($validated['title'])) $updateData['title'] = $validated['title'];
        if (isset($validated['description'])) $updateData['description'] = $validated['description'];
        if (isset($validated['vehicleType'])) $updateData['vehicle_type'] = $validated['vehicleType'];
        if (isset($validated['brand'])) $updateData['brand'] = $validated['brand'];
        if (isset($validated['model'])) $updateData['model'] = $validated['model'];
        if (isset($validated['year'])) $updateData['year'] = $validated['year'];
        if (array_key_exists('plateNumber', $validated)) $updateData['plate_number'] = $validated['plateNumber'];
        if (array_key_exists('seatingCapacity', $validated)) $updateData['seating_capacity'] = $validated['seatingCapacity'];
        if (array_key_exists('fuelType', $validated)) $updateData['fuel_type'] = $validated['fuelType'];
        if (array_key_exists('transmission', $validated)) $updateData['transmission'] = $validated['transmission'];
        if (array_key_exists('features', $validated)) $updateData['features'] = $validated['features'];
        if (isset($validated['pricePerDay'])) $updateData['price_per_day'] = $validated['pricePerDay'];
        if (array_key_exists('pricePerWeek', $validated)) $updateData['price_per_week'] = $validated['pricePerWeek'];
        if (array_key_exists('pricePerMonth', $validated)) $updateData['price_per_month'] = $validated['pricePerMonth'];
        if (isset($validated['location'])) $updateData['location'] = $validated['location'];
        if (array_key_exists('latitude', $validated)) $updateData['latitude'] = $validated['latitude'];
        if (array_key_exists('longitude', $validated)) $updateData['longitude'] = $validated['longitude'];
        if (isset($validated['status'])) $updateData['status'] = $validated['status'];
        if (array_key_exists('images', $validated)) $updateData['images'] = $validated['images'];
        if (array_key_exists('availableFrom', $validated)) $updateData['available_from'] = $validated['availableFrom'];
        if (array_key_exists('availableTo', $validated)) $updateData['available_to'] = $validated['availableTo'];

        $listing->update($updateData);

        return new VehicleListingResource($listing->fresh()->load('owner'));
    }

    public function destroy(Request $request, string $id)
    {
        $listing = VehicleListing::findOrFail($id);

        if ($listing->owner_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You do not have permission to delete this listing.',
            ], 403);
        }

        $listing->delete();

        return response()->noContent();
    }
}

