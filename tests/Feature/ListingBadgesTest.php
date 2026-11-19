<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ListingBadgeService;
use App\Services\Marketplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ListingBadgesTest extends TestCase
{
    use RefreshDatabase;

    private function provider(): User
    {
        return User::factory()->create([
            'landlord_status' => 'approved',
            'vehicle_owner_status' => 'approved',
            'service_provider_status' => 'approved',
            'aadi_plus_until' => now()->addYear(),
        ]);
    }

    private function space(User $owner, string $title, float $price = 1000, string $city = 'Catbalogan')
    {
        return app(Marketplace::class)->save($owner, 'spaces', [
            'title' => $title,
            'description' => 'A published space for testing.',
            'type' => 'apartment',
            'price' => $price,
            'status' => 'active',
            'photos' => [],
            'location' => ['lat' => 11.77, 'lng' => 124.88, 'city' => $city, 'address' => $city],
            'details' => [],
        ]);
    }

    public function test_listing_statuses_use_the_confirmed_review_view_and_availability_thresholds(): void
    {
        $owner = $this->provider();
        $records = collect();
        for ($index = 0; $index < 10; $index++) {
            $records->push($this->space($owner, "Space $index"));
        }

        $records[8]->forceFill(['published_at' => now()->subDays(31)])->save();
        $records[2]->forceFill(['availability_paused' => true])->save();

        $viewRows = [];
        for ($index = 0; $index < 25; $index++) {
            $viewRows[] = ['listing_id' => $records[0]->id, 'user_id' => null, 'ip_address' => "192.0.2.$index", 'viewed_at' => now()];
        }
        for ($index = 0; $index < 20; $index++) {
            $viewRows[] = ['listing_id' => $records[1]->id, 'user_id' => null, 'ip_address' => "198.51.100.$index", 'viewed_at' => now()];
        }
        DB::table('listing_views')->insert($viewRows);

        foreach ([5, 5, 5, 4, 4] as $rating) {
            DB::table('marketplace_reviews')->insert([
                'user_id' => User::factory()->create()->id,
                'service' => 'spaces',
                'listing_id' => $records[0]->id,
                'rating' => $rating,
                'body' => 'A sufficiently detailed review.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $badges = app(ListingBadgeService::class)->forRecords($records, 'spaces');

        $this->assertTrue($badges[(string) $records[0]->id]['popular']);
        $this->assertFalse($badges[(string) $records[1]->id]['popular']);
        $this->assertTrue($badges[(string) $records[0]->id]['top-rated']);
        $this->assertFalse($badges[(string) $records[1]->id]['top-rated']);
        $this->assertTrue($badges[(string) $records[0]->id]['new']);
        $this->assertFalse($badges[(string) $records[8]->id]['new']);
        $this->assertFalse($badges[(string) $records[2]->id]['available-now']);
    }

    public function test_recommendations_require_a_completed_matching_preference_set(): void
    {
        $owner = $this->provider();
        $match = $this->space($owner, 'Within budget', 900, 'Catbalogan');
        $wrongCity = $this->space($owner, 'Different city', 900, 'Calbayog');
        $overBudget = $this->space($owner, 'Over budget', 1500, 'Catbalogan');
        $viewer = User::factory()->create([
            'onboarding_status' => 'completed',
            'onboarding_data' => [
                'interests' => ['spaces'],
                'city' => 'Catbalogan',
                'budget_min' => 500,
                'budget_max' => 1000,
                'budget_unit' => 'month',
            ],
        ]);

        $results = app(Marketplace::class)->search(['service' => 'spaces'], $viewer);
        $byId = $results['data']->keyBy('id');

        $this->assertTrue($byId[(string) $match->id]['badges']['recommended']);
        $this->assertFalse($byId[(string) $wrongCity->id]['badges']['recommended']);
        $this->assertFalse($byId[(string) $overBudget->id]['badges']['recommended']);
        $this->assertTrue($byId[(string) $match->id]['badges']['available-now']);
    }

    public function test_new_badge_uses_the_most_recent_publication_transition(): void
    {
        $owner = $this->provider();
        $market = app(Marketplace::class);
        $draft = $market->save($owner, 'spaces', [
            'title' => 'Older draft',
            'description' => 'A draft published later.',
            'type' => 'apartment',
            'price' => 1000,
            'status' => 'draft',
            'photos' => [],
            'location' => ['lat' => 11.77, 'lng' => 124.88, 'city' => 'Catbalogan', 'address' => 'Catbalogan'],
            'details' => [],
        ]);
        $draft->forceFill(['created_at' => now()->subDays(60)])->save();
        $market->save($owner, 'spaces', [
            'title' => 'Older draft',
            'description' => 'A draft published later.',
            'type' => 'apartment',
            'price' => 1000,
            'status' => 'active',
            'photos' => [],
            'location' => ['lat' => 11.77, 'lng' => 124.88, 'city' => 'Catbalogan', 'address' => 'Catbalogan'],
            'details' => [],
        ], $draft->fresh());

        $published = $draft->fresh();
        $this->assertTrue($published->published_at->greaterThanOrEqualTo(now()->subMinute()));
        $this->assertTrue(app(ListingBadgeService::class)
            ->forRecords([$published], 'spaces')[(string) $published->id]['new']);

        $this->travel(31)->days();
        $market->save($owner, 'spaces', [
            'title' => 'Older draft',
            'description' => 'A draft published later.',
            'type' => 'apartment',
            'price' => 1000,
            'status' => 'draft',
            'photos' => [],
            'location' => ['lat' => 11.77, 'lng' => 124.88, 'city' => 'Catbalogan', 'address' => 'Catbalogan'],
            'details' => [],
        ], $published);
        $republished = $market->save($owner, 'spaces', [
            'title' => 'Older draft',
            'description' => 'A draft published later.',
            'type' => 'apartment',
            'price' => 1000,
            'status' => 'active',
            'photos' => [],
            'location' => ['lat' => 11.77, 'lng' => 124.88, 'city' => 'Catbalogan', 'address' => 'Catbalogan'],
            'details' => [],
        ], $published->fresh());

        $this->assertTrue($republished->published_at->greaterThanOrEqualTo(now()->subMinute()));
        $this->assertTrue(app(ListingBadgeService::class)
            ->forRecords([$republished], 'spaces')[(string) $republished->id]['new']);
    }

    public function test_recommendations_match_vehicle_and_service_budget_units(): void
    {
        $owner = $this->provider();
        $market = app(Marketplace::class);
        foreach ([
            ['service' => 'haul', 'unit' => 'day', 'type' => 'van', 'price' => 700],
            ['service' => 'raketeer', 'unit' => 'hour', 'type' => 'homeCleaning', 'price' => 300],
            ['service' => 'raketeer', 'unit' => 'project', 'type' => 'repairService', 'price' => 900],
        ] as $case) {
            $listing = $market->save($owner, $case['service'], [
                'title' => 'Matching marketplace listing',
                'description' => 'A published marketplace listing.',
                'type' => $case['type'],
                'price' => $case['price'],
                'priceUnit' => $case['unit'],
                'status' => 'active',
                'photos' => [],
                'location' => ['lat' => 11.77, 'lng' => 124.88, 'city' => 'Catbalogan', 'address' => 'Catbalogan, Samar'],
                'details' => ['brand' => 'Toyota', 'model' => 'Van', 'year' => 2024],
            ]);
            $this->assertNotNull($listing->published_at);
            $viewer = User::factory()->create([
                'onboarding_status' => 'completed',
                'onboarding_data' => [
                    'interests' => [$case['service']],
                    'city' => 'Catbalogan',
                    'budget_min' => 100,
                    'budget_max' => 1000,
                    'budget_unit' => $case['unit'],
                ],
            ]);

            $results = $market->search(['service' => $case['service']], $viewer);
            $this->assertTrue($results['data']->firstWhere('id', (string) $listing->id)['badges']['recommended']);
        }
    }
}
