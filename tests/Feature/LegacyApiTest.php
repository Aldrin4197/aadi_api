<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Marketplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Covers the pre-v1 /api/* listing routes that remain available for existing clients. */
class LegacyApiTest extends TestCase
{
    use RefreshDatabase;

    private const NEAR = ['lat' => 11.7753, 'lng' => 124.8861];
    private const FAR = ['lat' => 14.5995, 'lng' => 120.9842];

    private function provider(): User
    {
        return User::factory()->create(['landlord_status' => 'approved', 'vehicle_owner_status' => 'approved', 'service_provider_status' => 'approved']);
    }

    private function seedListing(User $owner, string $service, array $point, string $title): mixed
    {
        return app(Marketplace::class)->save($owner, $service, [
            'title' => $title, 'description' => 'Legacy API fixture.', 'type' => Marketplace::TYPES[$service][0],
            'price' => 1000, 'status' => 'active', 'photos' => [],
            'location' => [...$point, 'address' => 'Somewhere', 'city' => 'Somewhere'],
            'details' => ['brand' => 'Toyota', 'model' => 'Hiace', 'year' => 2024],
        ]);
    }

    public function test_legacy_routes_require_authentication(): void
    {
        foreach (['listings', 'vehicles', 'haul', 'services', 'raketeer'] as $path) {
            $this->getJson('/api/'.$path)->assertUnauthorized();
        }
    }

    public function test_listing_search_returns_nearest_first_within_radius(): void
    {
        $owner = $this->provider();
        $near = $this->seedListing($owner, 'spaces', self::NEAR, 'Near');
        $nearer = $this->seedListing($owner, 'spaces', ['lat' => 11.7760, 'lng' => 124.8870], 'Nearer');
        $this->seedListing($owner, 'spaces', self::FAR, 'Far');

        $response = $this->actingAs($owner)
            ->getJson('/api/listings?lat=11.7753&lng=124.8861&radiusKm=10')->assertOk();

        $response->assertJsonCount(2, 'data');
        $this->assertSame([(string) $near->id, (string) $nearer->id], $response->json('data.*.id'));
        $this->assertNotNull($response->json('data.0.distance'));
    }

    public function test_vehicle_search_filters_by_distance_and_aliases_share_behavior(): void
    {
        $owner = $this->provider();
        $near = $this->seedListing($owner, 'haul', self::NEAR, 'Near van');
        $this->seedListing($owner, 'haul', self::FAR, 'Far van');

        foreach (['vehicles', 'haul'] as $path) {
            $response = $this->actingAs($owner)
                ->getJson("/api/$path?latitude=11.7753&longitude=124.8861&radius=25")->assertOk();
            $response->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', (string) $near->id);
            $this->assertNotNull($response->json('data.0.distance'));
        }

        $this->actingAs($owner)->getJson('/api/vehicles')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_service_listings_are_listed_and_filtered(): void
    {
        $owner = $this->provider();
        $record = $this->seedListing($owner, 'raketeer', self::NEAR, 'Plumber');

        foreach (['services', 'raketeer'] as $path) {
            $this->actingAs($owner)->getJson("/api/$path")->assertOk()->assertJsonCount(1, 'data');
        }
        $this->actingAs($owner)->getJson('/api/services?serviceType=nonexistent')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($owner)->getJson('/api/services/'.$record->id)->assertOk()->assertJsonPath('id', (string) $record->id);
    }

    public function test_unapproved_users_cannot_create_listings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/listings', ['title' => 'x', 'description' => 'x', 'type' => 'bedspace', 'price' => 1, 'lat' => 1, 'lng' => 1])->assertForbidden();
        $this->actingAs($user)->postJson('/api/vehicles', [])->assertForbidden();
        $this->actingAs($user)->postJson('/api/services', [])->assertForbidden();
    }

    public function test_approved_owner_can_create_update_and_delete_each_listing_kind(): void
    {
        $owner = $this->provider();
        $this->actingAs($owner);

        $space = $this->postJson('/api/listings', ['title' => 'Room', 'description' => 'Cozy', 'type' => 'bedspace', 'price' => 2000, 'lat' => 11.7, 'lng' => 124.8])
            ->assertSuccessful()->json('id');
        $vehicle = $this->postJson('/api/vehicles', ['title' => 'Van', 'description' => 'Roomy', 'vehicleType' => 'van', 'brand' => 'Toyota', 'model' => 'Hiace', 'year' => 2022, 'pricePerDay' => 3000, 'location' => 'Catbalogan'])
            ->assertSuccessful()->json('id');
        $service = $this->postJson('/api/services', ['title' => 'Fix', 'description' => 'Repairs', 'serviceType' => 'plumbing', 'priceType' => 'fixed', 'priceAmount' => 500, 'serviceArea' => 'Catbalogan'])
            ->assertSuccessful()->json('id');

        $this->putJson("/api/listings/$space", ['title' => 'Room 2'])->assertOk()->assertJsonPath('title', 'Room 2');
        $this->putJson("/api/vehicles/$vehicle", ['title' => 'Van 2'])->assertOk()->assertJsonPath('title', 'Van 2');
        $this->putJson("/api/services/$service", ['title' => 'Fix 2'])->assertOk()->assertJsonPath('title', 'Fix 2');

        $this->deleteJson("/api/listings/$space")->assertNoContent();
        $this->deleteJson("/api/vehicles/$vehicle")->assertNoContent();
        $this->deleteJson("/api/services/$service")->assertNoContent();
    }

    public function test_only_the_owner_can_modify_or_delete_a_listing(): void
    {
        $owner = $this->provider();
        $other = $this->provider();
        $records = [
            'listings' => $this->seedListing($owner, 'spaces', self::NEAR, 'Space'),
            'vehicles' => $this->seedListing($owner, 'haul', self::NEAR, 'Van'),
            'services' => $this->seedListing($owner, 'raketeer', self::NEAR, 'Service'),
        ];

        foreach ($records as $path => $record) {
            $this->actingAs($other)->putJson("/api/$path/{$record->id}", ['title' => 'Hijacked'])->assertForbidden();
            $this->actingAs($other)->deleteJson("/api/$path/{$record->id}")->assertForbidden();
            $this->assertNotSame('Hijacked', $record->fresh()->title);
        }
    }
}
