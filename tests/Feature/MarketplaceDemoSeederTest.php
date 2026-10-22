<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\ServiceListing;
use App\Models\User;
use App\Models\VehicleListing;
use Database\Seeders\MarketplaceDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MarketplaceDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_samples_are_visible_in_all_three_marketplace_categories(): void
    {
        $this->seed(MarketplaceDemoSeeder::class);

        foreach (['spaces' => 'month', 'haul' => 'day', 'raketeer' => 'job'] as $service => $unit) {
            $response = $this->getJson('/api/v1/offers?service='.$service.'&q=Catbalogan');
            $response->assertOk()->assertJsonPath('meta.total', 5);
            foreach ($response->json('data') as $item) {
                $this->assertStringStartsWith('[Sample]', $item['title']);
                $this->assertMatchesRegularExpression('/^[0-7][0-9A-HJKMNP-TV-Z]{25}$/', $item['id']);
                $this->assertNotNull($item['location']['lat']);
                $this->assertNotNull($item['location']['lng']);
                $this->assertContains($item['priceUnit'], $service === 'raketeer' ? ['job', 'hour'] : [$unit]);
                $this->assertSame([], $item['photos']);
            }
        }
        $this->assertFalse(User::sole()->is_admin);
    }

    public function test_reseeding_resets_password_and_preserves_edits_and_soft_deletions(): void
    {
        $this->seed(MarketplaceDemoSeeder::class);
        $owner = User::sole();
        $owner->update(['password'=>'PreviouslyChangedPassword']);
        $listing = Listing::firstOrFail();
        $listing->update(['title' => 'Edited sample', 'price' => 1234]);
        $vehicle = VehicleListing::firstOrFail();
        $vehicle->delete();
        $service = ServiceListing::firstOrFail();
        $service->delete();

        $this->seed(MarketplaceDemoSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('listings', 5);
        $this->assertDatabaseCount('vehicle_listings', 5);
        $this->assertDatabaseCount('service_listings', 5);
        $this->assertTrue(Hash::check('password', $owner->fresh()->password));
        $this->assertSame('Edited sample', $listing->fresh()->title);
        $this->assertEquals(1234, $listing->fresh()->price);
        $this->assertSoftDeleted($vehicle);
        $this->assertSoftDeleted($service);
    }
}
