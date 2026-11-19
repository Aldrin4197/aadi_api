<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\ServiceListing;
use App\Models\VehicleListing;
use App\Services\Marketplace;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;

class ListingDirectionsTest extends TestCase
{
    private function mockListing(string $service, string $status = 'active'): void
    {
        $listing = match ($service) {
            'spaces' => new Listing(['lat' => 12, 'lng' => 124]),
            'haul' => new VehicleListing(['latitude' => 12, 'longitude' => 124]),
            'raketeer' => new ServiceListing(['latitude' => 12, 'longitude' => 124]),
        };
        $market = Mockery::mock(Marketplace::class);
        $market->shouldReceive('find')->with($service, 'test-id')->andReturn($listing);
        $market->shouldReceive('listingStatus')->with($listing, $service)->andReturn($status);
        $this->app->instance(Marketplace::class, $market);
    }

    public function test_routes_to_all_listing_types_using_server_side_coordinates_and_key(): void
    {
        config(['services.openrouteservice.key' => 'test-key']);
        Http::fake(['api.openrouteservice.org/*' => Http::response([
            'features' => [['geometry' => ['type' => 'LineString', 'coordinates' => [[123.9, 11.9], [124, 12]]],
                'properties' => ['summary' => ['distance' => 1234, 'duration' => 600]]]],
        ])]);
        foreach (array_keys(Marketplace::TYPES) as $service) {
            $this->mockListing($service);
            $this->postJson("/offers/$service/test-id/directions", ['latitude' => 11.9, 'longitude' => 123.9])
                ->assertOk()->assertJsonPath('coordinates.0.0', 11.9)->assertJsonPath('coordinates.1.1', 124)
                ->assertJsonPath('distance', 1234)->assertJsonPath('duration', 600)
                ->assertHeader('Cache-Control', 'no-store, private');
        }
        Http::assertSentCount(3);
        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === 'https://api.openrouteservice.org/v2/directions/driving-car/geojson'
            && $request->hasHeader('Accept', 'application/geo+json')
            && $request->hasHeader('Authorization', 'test-key')
            && $request['coordinates'] == [[123.9, 11.9], [124, 12]]
            && $request['instructions'] === false);
    }

    public function test_invalid_inputs_missing_key_private_listings_and_provider_failures_are_explicit(): void
    {
        $this->mockListing('spaces');
        $url = '/offers/spaces/test-id/directions';
        $this->postJson($url, ['latitude' => 91, 'longitude' => 124])->assertUnprocessable();
        config(['services.openrouteservice.key' => null]);
        $this->postJson($url, ['latitude' => 12, 'longitude' => 124])->assertStatus(503)
            ->assertJsonPath('message', 'Directions are not configured yet.');
        $this->mockListing('spaces', 'draft');
        $this->postJson($url, ['latitude' => 12, 'longitude' => 124])->assertNotFound();

        $this->mockListing('spaces');
        config(['services.openrouteservice.key' => 'test-key']);
        Http::fake(['api.openrouteservice.org/*' => Http::sequence()
            ->push(['error' => ['code' => 2009]], 404)
            ->push(['features' => [['geometry' => ['type' => 'LineString', 'coordinates' => [[999, 12], [124, 12]]],
                'properties' => ['summary' => ['distance' => 1, 'duration' => 2]]]]], 200)]);
        $this->postJson($url, ['latitude' => 12, 'longitude' => 124])->assertUnprocessable()
            ->assertJsonPath('message', 'No driving route was found for these locations.');
        $this->postJson($url, ['latitude' => 12, 'longitude' => 124])->assertStatus(502);
    }

    public function test_walking_mode_uses_the_walking_profile_and_rejects_unknown_modes(): void
    {
        $this->mockListing('spaces');
        config(['services.openrouteservice.key' => 'test-key']);
        Http::fake(['api.openrouteservice.org/*' => Http::sequence()
            ->push(['features' => [['geometry' => ['type' => 'LineString', 'coordinates' => [[123.9, 11.9], [124, 12]]],
                'properties' => ['summary' => ['distance' => 1234, 'duration' => 1200]]]]])
            ->push(['error' => ['code' => 2009]], 404)]);
        $url = '/offers/spaces/test-id/directions';
        $origin = ['latitude' => 11.9, 'longitude' => 123.9, 'mode' => 'walking'];

        $this->postJson($url, $origin)->assertOk()->assertJsonPath('duration', 1200);
        $this->postJson($url, $origin)->assertUnprocessable()
            ->assertJsonPath('message', 'No walking route was found for these locations.');
        $this->postJson($url, [...$origin, 'mode' => 'cycling'])->assertUnprocessable()->assertJsonValidationErrors('mode');
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.openrouteservice.org/v2/directions/foot-walking/geojson'
            && $request->hasHeader('Accept', 'application/geo+json'));
    }

    public function test_provider_errors_are_specific_without_exposing_provider_details(): void
    {
        $this->mockListing('spaces');
        config(['services.openrouteservice.key' => 'test-key']);
        Http::fake(['api.openrouteservice.org/*' => Http::sequence()
            ->push(['error' => ['code' => 2004, 'message' => 'private provider detail']], 400)
            ->push([], 403)
            ->push([], 429)
            ->push([], 500)]);
        $url = '/offers/spaces/test-id/directions';
        $origin = ['latitude' => 12, 'longitude' => 124];
        $this->postJson($url, $origin)->assertUnprocessable()
            ->assertJsonPath('message', 'This route exceeds the routing service limit. Try a closer starting point.')
            ->assertDontSee('private provider detail');
        $this->postJson($url, $origin)->assertStatus(503)
            ->assertJsonPath('message', 'The directions service rejected its API key. Please contact the site administrator.');
        $this->postJson($url, $origin)->assertStatus(503)
            ->assertJsonPath('message', 'The directions service usage limit was reached. Please try again later.');
        $this->postJson($url, $origin)->assertStatus(503)
            ->assertJsonPath('message', 'Directions are temporarily unavailable. Please try again.');
    }
}
