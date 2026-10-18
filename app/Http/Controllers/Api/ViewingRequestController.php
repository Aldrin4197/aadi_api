<?php

namespace App\Http\Controllers\Api;

use App\Enums\ViewingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ViewingRequestResource;
use App\Models\Listing;
use App\Models\ViewingRequest;
use Illuminate\Http\Request;

class ViewingRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Get all viewing requests where user is either tenant or owner
        $viewingRequests = ViewingRequest::query()
            ->where('tenant_id', $user->id)
            ->orWhere('owner_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return ViewingRequestResource::collection($viewingRequests);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'listingId' => 'required|string|exists:listings,id',
            'requestedAt' => 'required|date',
            'note' => 'nullable|string',
        ]);

        $listing = Listing::findOrFail($validated['listingId']);
        $user = $request->user();

        // Prevent owner from requesting viewing of their own listing
        if ($listing->owner_id === $user->id) {
            return response()->json([
                'message' => 'You cannot request a viewing for your own listing.',
            ], 400);
        }

        $viewingRequest = ViewingRequest::create([
            'listing_id' => $listing->id,
            'listing_title' => $listing->title,
            'tenant_id' => $user->id,
            'tenant_name' => $user->name,
            'owner_id' => $listing->owner_id,
            'owner_name' => $listing->owner_name,
            'requested_at' => $validated['requestedAt'],
            'status' => ViewingStatus::PENDING,
            'note' => $validated['note'] ?? null,
        ]);

        return new ViewingRequestResource($viewingRequest);
    }

    public function update(Request $request, string $id)
    {
        $viewingRequest = ViewingRequest::findOrFail($id);
        $user = $request->user();

        // Check authorization
        if ($viewingRequest->tenant_id !== $user->id && $viewingRequest->owner_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'status' => 'sometimes|string',
            'requestedAt' => 'sometimes|date',
            'proposedAt' => 'nullable|date',
            'note' => 'nullable|string',
        ]);

        // Status transition logic
        $newStatus = $validated['status'] ?? null;

        if ($newStatus) {
            // Owner actions
            if ($viewingRequest->owner_id === $user->id) {
                if ($newStatus === 'confirmed') {
                    $viewingRequest->status = ViewingStatus::CONFIRMED;
                } elseif ($newStatus === 'declined') {
                    $viewingRequest->status = ViewingStatus::DECLINED;
                } elseif ($newStatus === 'rescheduled' && isset($validated['proposedAt'])) {
                    $viewingRequest->status = ViewingStatus::RESCHEDULED;
                    $viewingRequest->proposed_at = $validated['proposedAt'];
                }
            }

            // Tenant actions
            if ($viewingRequest->tenant_id === $user->id) {
                if ($newStatus === 'pending' && isset($validated['requestedAt'])) {
                    $viewingRequest->status = ViewingStatus::PENDING;
                    $viewingRequest->requested_at = $validated['requestedAt'];
                    $viewingRequest->proposed_at = null;
                } elseif ($newStatus === 'confirmed' && $viewingRequest->status === ViewingStatus::RESCHEDULED) {
                    // Accept proposed time
                    $viewingRequest->status = ViewingStatus::CONFIRMED;
                    $viewingRequest->requested_at = $viewingRequest->proposed_at;
                }
            }
        }

        if (isset($validated['note'])) {
            $viewingRequest->note = $validated['note'];
        }

        $viewingRequest->save();

        return new ViewingRequestResource($viewingRequest->fresh());
    }

    public function destroy(Request $request, string $id)
    {
        $viewingRequest = ViewingRequest::findOrFail($id);
        $user = $request->user();

        // Check authorization - either party can delete
        if ($viewingRequest->tenant_id !== $user->id && $viewingRequest->owner_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $viewingRequest->delete();

        return response()->noContent();
    }
}
