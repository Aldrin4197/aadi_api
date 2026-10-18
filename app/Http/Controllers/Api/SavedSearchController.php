<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SavedSearchResource;
use App\Models\SavedSearch;
use Illuminate\Http\Request;

class SavedSearchController extends Controller
{
    public function index(Request $request)
    {
        $savedSearches = $request->user()
            ->savedSearches()
            ->orderByDesc('created_at')
            ->get();

        return SavedSearchResource::collection($savedSearches);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'query' => 'required|array',
        ]);

        $savedSearch = $request->user()->savedSearches()->create([
            'name' => $validated['name'],
            'query' => $validated['query'],
        ]);

        return new SavedSearchResource($savedSearch);
    }

    public function destroy(Request $request, string $id)
    {
        $savedSearch = SavedSearch::findOrFail($id);

        // Authorization
        if ($savedSearch->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $savedSearch->delete();

        return response()->noContent();
    }
}
