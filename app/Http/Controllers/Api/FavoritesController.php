<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Http\Request;

class FavoritesController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $favoriteIds = $user->favorites()->pluck('listings.id')->toArray();

        return response()->json($favoriteIds);
    }

    public function store(Request $request, string $listingId)
    {
        $listing = Listing::findOrFail($listingId);
        $user = $request->user();

        // Add to favorites if not already there
        if (!$user->favorites()->where('listings.id', $listingId)->exists()) {
            $user->favorites()->attach($listingId);
        }

        return response()->noContent();
    }

    public function destroy(Request $request, string $listingId)
    {
        $user = $request->user();
        $user->favorites()->detach($listingId);

        return response()->noContent();
    }
}
