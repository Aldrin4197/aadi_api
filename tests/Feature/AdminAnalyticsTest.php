<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\ListingView;
use App\Models\User;
use App\Services\Marketplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_page_loads_with_views_recorded_the_way_the_api_records_them(): void
    {
        $this->withoutVite();
        $owner = User::factory()->create(['landlord_status' => 'approved']);
        $listing = app(Marketplace::class)->save($owner, 'spaces', [
            'title' => 'Viewed studio', 'description' => 'Analytics fixture.', 'type' => 'bedspace', 'price' => 1500, 'status' => 'active', 'photos' => [],
            'location' => ['lat' => 11.77, 'lng' => 124.88, 'address' => 'Catbalogan', 'city' => 'Catbalogan'],
        ]);
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($owner)->getJson('/api/listings/'.$listing->id)->assertOk();
        ListingView::create(['listing_id' => $listing->id, 'user_id' => null, 'ip_address' => '127.0.0.1', 'viewed_at' => now()->subDays(2)]);
        $this->assertSame(2, ListingView::count());

        $this->actingAs($admin)->get('/admin/analytics')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Analytics')
            ->where('overview.totalViews', 2)
            ->has('dailyViews', 2)
            ->where('topListings.0.views_count', 2)
            ->where('listingsByType.bedspace', 1));
    }

    public function test_analytics_page_loads_on_an_empty_marketplace(): void
    {
        $this->withoutVite();
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)->get('/admin/analytics')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('overview.totalViews', 0)->has('dailyViews', 0));
    }
}
