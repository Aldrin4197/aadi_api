<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Services\Marketplace;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ListingDirectionsController extends Controller
{
    public function __invoke(Request $request, Marketplace $market, string $service, string $id)
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'mode' => ['sometimes', 'required', 'in:driving,walking'],
        ]);
        $mode = $data['mode'] ?? 'driving';
        $profile = $mode === 'walking' ? 'foot-walking' : 'driving-car';
        $listing = $market->find($service, $id);
        if ($market->listingStatus($listing, $service) !== 'active') {
            abort_unless($request->user(), 404);
            $market->authorize($request->user(), $listing, $service);
        }

        $latitude = $service === 'spaces' ? $listing->lat : $listing->latitude;
        $longitude = $service === 'spaces' ? $listing->lng : $listing->longitude;
        if ($latitude === null || $longitude === null) {
            return response()->json(['message' => 'This listing has no map location.'], 422);
        }
        $key = config('services.openrouteservice.key');
        if (!$key) {
            return response()->json(['message' => 'Directions are not configured yet.'], 503);
        }

        try {
            $response = Http::timeout(12)->accept('application/geo+json')->withHeaders(['Authorization' => $key])
                ->post("https://api.openrouteservice.org/v2/directions/{$profile}/geojson", [
                    'coordinates' => [
                        [(float) $data['longitude'], (float) $data['latitude']],
                        [(float) $longitude, (float) $latitude],
                    ],
                    'instructions' => false,
                ]);
        } catch (ConnectionException $exception) {
            report($exception);

            return response()->json(['message' => 'Directions are temporarily unavailable. Please try again.'], 503);
        }

        if (!$response->successful()) {
            $code = $response->json('error.code');
            $code = is_numeric($code) ? (int) $code : null;
            Log::warning('Directions service rejected a request.', ['status' => $response->status(), 'code' => $code]);
            if (in_array($code, [2009, 2010, 2013, 2014, 2015, 2016], true)) {
                return response()->json(['message' => "No {$mode} route was found for these locations."], 422);
            }
            if ($code === 2004) {
                return response()->json(['message' => 'This route exceeds the routing service limit. Try a closer starting point.'], 422);
            }
            if (in_array($response->status(), [401, 403], true)) {
                return response()->json(['message' => 'The directions service rejected its API key. Please contact the site administrator.'], 503);
            }
            if ($response->status() === 429) {
                return response()->json(['message' => 'The directions service usage limit was reached. Please try again later.'], 503);
            }

            return response()->json(['message' => 'Directions are temporarily unavailable. Please try again.'], 503);
        }

        $feature = $response->json('features.0');
        $coordinates = $feature['geometry']['coordinates'] ?? null;
        $summary = $feature['properties']['summary'] ?? null;
        if (($feature['geometry']['type'] ?? null) !== 'LineString' || !is_array($coordinates) || count($coordinates) < 2
            || !is_array($summary) || !is_numeric($summary['distance'] ?? null) || !is_numeric($summary['duration'] ?? null)) {
            return response()->json(['message' => 'The directions service returned an invalid route.'], 502);
        }
        foreach ($coordinates as $point) {
            if (!is_array($point) || count($point) < 2 || !is_numeric($point[0]) || !is_numeric($point[1])
                || $point[0] < -180 || $point[0] > 180 || $point[1] < -90 || $point[1] > 90) {
                return response()->json(['message' => 'The directions service returned an invalid route.'], 502);
            }
        }

        return response()->json([
            'coordinates' => array_map(fn ($point) => [(float) $point[1], (float) $point[0]], $coordinates),
            'distance' => (float) $summary['distance'],
            'duration' => (float) $summary['duration'],
        ])->header('Cache-Control', 'no-store');
    }
}
