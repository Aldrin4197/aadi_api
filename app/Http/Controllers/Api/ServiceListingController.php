<?php

namespace App\Http\Controllers\Api;

use App\Enums\ServiceProviderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceListingResource;
use App\Models\ServiceListing;
use Illuminate\Http\Request;

class ServiceListingController extends Controller
{
    public function index(Request $request)
    {
        $query = ServiceListing::with('provider');

        // Filter by service type
        if ($request->filled('serviceType')) {
            $query->where('service_type', $request->serviceType);
        }

        // Filter by service category
        if ($request->filled('serviceCategory')) {
            $query->where('service_category', $request->serviceCategory);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by price range
        if ($request->filled('minPrice')) {
            $query->where('price_amount', '>=', $request->minPrice);
        }
        if ($request->filled('maxPrice')) {
            $query->where('price_amount', '<=', $request->maxPrice);
        }

        // Filter by price type
        if ($request->filled('priceType')) {
            $query->where('price_type', $request->priceType);
        }

        // Search by service area
        if ($request->filled('serviceArea')) {
            $query->where('service_area', 'like', '%' . $request->serviceArea . '%');
        }

        $query->orderBy('created_at', 'desc');

        $listings = $query->paginate(20);

        return ServiceListingResource::collection($listings);
    }

    public function show(string $id)
    {
        $listing = ServiceListing::with('provider')->findOrFail($id);

        // Increment views
        $listing->increment('views_count');

        return new ServiceListingResource($listing);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        // Check if user is approved service provider
        if ($user->service_provider_status !== ServiceProviderStatus::APPROVED) {
            return response()->json([
                'message' => 'You must be an approved service provider to create service listings.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'serviceType' => 'required|string',
            'serviceCategory' => 'nullable|string',
            'skillsRequired' => 'nullable|array',
            'priceType' => 'required|in:fixed,hourly,per_project',
            'priceAmount' => 'required|numeric|min:0',
            'serviceArea' => 'required|string',
            'availabilitySchedule' => 'nullable|array',
            'responseTimeMinutes' => 'nullable|integer|min:0',
            'estimatedDurationMinutes' => 'nullable|integer|min:0',
            'images' => 'nullable|array',
            'certifications' => 'nullable|array',
        ]);

        $listing = ServiceListing::create([
            'provider_id' => $user->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'service_type' => $validated['serviceType'],
            'service_category' => $validated['serviceCategory'] ?? null,
            'skills_required' => $validated['skillsRequired'] ?? [],
            'price_type' => $validated['priceType'],
            'price_amount' => $validated['priceAmount'],
            'service_area' => $validated['serviceArea'],
            'availability_schedule' => $validated['availabilitySchedule'] ?? [],
            'response_time_minutes' => $validated['responseTimeMinutes'] ?? null,
            'estimated_duration_minutes' => $validated['estimatedDurationMinutes'] ?? null,
            'images' => $validated['images'] ?? [],
            'certifications' => $validated['certifications'] ?? [],
        ]);

        return new ServiceListingResource($listing->fresh()->load('provider'));
    }

    public function update(Request $request, string $id)
    {
        $listing = ServiceListing::findOrFail($id);

        if ($listing->provider_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You do not have permission to update this listing.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'serviceType' => 'sometimes|string',
            'serviceCategory' => 'nullable|string',
            'skillsRequired' => 'nullable|array',
            'priceType' => 'sometimes|in:fixed,hourly,per_project',
            'priceAmount' => 'sometimes|numeric|min:0',
            'serviceArea' => 'sometimes|string',
            'availabilitySchedule' => 'nullable|array',
            'responseTimeMinutes' => 'nullable|integer|min:0',
            'estimatedDurationMinutes' => 'nullable|integer|min:0',
            'status' => 'sometimes|in:available,busy,inactive',
            'images' => 'nullable|array',
            'certifications' => 'nullable|array',
        ]);

        $updateData = [];
        if (isset($validated['title'])) $updateData['title'] = $validated['title'];
        if (isset($validated['description'])) $updateData['description'] = $validated['description'];
        if (isset($validated['serviceType'])) $updateData['service_type'] = $validated['serviceType'];
        if (array_key_exists('serviceCategory', $validated)) $updateData['service_category'] = $validated['serviceCategory'];
        if (array_key_exists('skillsRequired', $validated)) $updateData['skills_required'] = $validated['skillsRequired'];
        if (isset($validated['priceType'])) $updateData['price_type'] = $validated['priceType'];
        if (isset($validated['priceAmount'])) $updateData['price_amount'] = $validated['priceAmount'];
        if (isset($validated['serviceArea'])) $updateData['service_area'] = $validated['serviceArea'];
        if (array_key_exists('availabilitySchedule', $validated)) $updateData['availability_schedule'] = $validated['availabilitySchedule'];
        if (array_key_exists('responseTimeMinutes', $validated)) $updateData['response_time_minutes'] = $validated['responseTimeMinutes'];
        if (array_key_exists('estimatedDurationMinutes', $validated)) $updateData['estimated_duration_minutes'] = $validated['estimatedDurationMinutes'];
        if (isset($validated['status'])) $updateData['status'] = $validated['status'];
        if (array_key_exists('images', $validated)) $updateData['images'] = $validated['images'];
        if (array_key_exists('certifications', $validated)) $updateData['certifications'] = $validated['certifications'];

        $listing->update($updateData);

        return new ServiceListingResource($listing->fresh()->load('provider'));
    }

    public function destroy(Request $request, string $id)
    {
        $listing = ServiceListing::findOrFail($id);

        if ($listing->provider_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You do not have permission to delete this listing.',
            ], 403);
        }

        $listing->delete();

        return response()->noContent();
    }
}

