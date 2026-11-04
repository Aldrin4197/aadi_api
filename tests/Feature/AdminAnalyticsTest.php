<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\ListingView;
use App\Models\User;
use App\Services\Marketplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    private function listing(User $owner, string $title = 'Viewed studio', string $service = 'spaces'): \Illuminate\Database\Eloquent\Model
    {
        return app(Marketplace::class)->save($owner, $service, [
            'title' => $title, 'description' => 'Analytics fixture.', 'type' => Marketplace::TYPES[$service][0], 'price' => 1500, 'status' => 'active', 'photos' => [],
            'location' => ['lat' => 11.77, 'lng' => 124.88, 'address' => 'Catbalogan', 'city' => 'Catbalogan'],
            'details' => ['brand' => 'Toyota', 'model' => 'Hiace', 'year' => 2024],
        ]);
    }

    public function test_the_page_is_admin_only(): void
    {
        $this->withoutVite();
        $this->get('/admin/analytics')->assertRedirect();
        $this->actingAs(User::factory()->create())->get('/admin/analytics')->assertForbidden();
    }

    public function test_it_loads_on_an_empty_marketplace_and_explains_the_silence(): void
    {
        $this->withoutVite();

        $this->actingAs($this->admin())->get('/admin/analytics')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Analytics')
            ->where('analytics.range.days', 30)
            ->has('analytics.kpis', 6)
            ->has('analytics.trend.dates', 30)
            ->has('analytics.topListings', 0)
            ->where('analytics.insights', fn ($items) => collect($items)->contains(fn ($i) => str_contains($i['title'], 'No property views'))));
    }

    public function test_it_reports_views_saves_reviews_funnel_top_listings_and_types(): void
    {
        $this->withoutVite();
        $owner = User::factory()->create(['landlord_status' => 'approved', 'vehicle_owner_status' => 'approved']);
        $fan = User::factory()->create();
        $studio = $this->listing($owner, 'Viewed studio');
        $this->listing($owner, 'Quiet room');
        $this->listing($owner, 'A van', 'haul');

        foreach ([0, 1, 2] as $i) ListingView::create(['listing_id' => $studio->id, 'user_id' => null, 'ip_address' => "10.0.0.$i", 'viewed_at' => now()->subDays($i)]);
        ListingView::create(['listing_id' => $studio->id, 'user_id' => null, 'ip_address' => '10.0.0.9', 'viewed_at' => now()->subDays(45)]);
        DB::table('favorites')->insert(['user_id' => $fan->id, 'listing_id' => $studio->id, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('marketplace_reviews')->insert(['user_id' => $fan->id, 'service' => 'spaces', 'listing_id' => $studio->id, 'rating' => 5, 'body' => 'Great place to stay.', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($this->admin())->get('/admin/analytics?range=7')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('analytics.range.days', 7)
            ->has('analytics.trend.dates', 7)
            ->where('analytics.kpis.0.key', 'views')->where('analytics.kpis.0.value', 3)
            ->where('analytics.kpis.1.key', 'saves')->where('analytics.kpis.1.value', 1)
            ->where('analytics.kpis.2.key', 'reviews')->where('analytics.kpis.2.value', 1)
            ->where('analytics.funnel.0.count', 3)->where('analytics.funnel.1.per100', 33.3)
            ->where('analytics.topListings.0.title', 'Viewed studio')->where('analytics.topListings.0.views', 3)->where('analytics.topListings.0.saves', 1)
            ->where('analytics.types', fn ($rows) => collect($rows)->contains(fn ($r) => $r['category'] === 'haul' && $r['type'] === 'multicab' && $r['count'] === 1))
            ->where('analytics.insights', fn ($items) => collect($items)->contains(fn ($i) => str_contains($i['title'], 'Most viewed'))));

        $this->actingAs($this->admin())->get('/admin/analytics?range=999')->assertInertia(fn (Assert $page) => $page->where('analytics.range.days', 30));
    }

    public function test_the_dashboard_engagement_includes_saves_of_properties(): void
    {
        $this->withoutVite();
        $owner = User::factory()->create(['landlord_status' => 'approved']);
        $fan = User::factory()->create();
        $studio = $this->listing($owner);
        DB::table('favorites')->insert(['user_id' => $fan->id, 'listing_id' => $studio->id, 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($this->admin())->get('/admin/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('overview.kpis', fn ($kpis) => collect($kpis)->contains(fn ($k) => $k['key'] === 'engagement' && $k['value'] === 1)));
    }

    public function test_opening_a_property_records_one_view_per_visitor_and_ignores_owners_and_admins(): void
    {
        $this->withoutVite();
        $owner = User::factory()->create(['landlord_status' => 'approved', 'vehicle_owner_status' => 'approved']);
        $visitor = User::factory()->create();
        $studio = $this->listing($owner);
        $van = $this->listing($owner, 'A van', 'haul');

        $this->actingAs($visitor)->get('/offers/spaces/'.$studio->id)->assertOk();
        $this->get('/offers/spaces/'.$studio->id)->assertOk();
        $this->assertSame(1, ListingView::count());

        $this->actingAs($owner)->get('/offers/spaces/'.$studio->id)->assertOk();
        $this->actingAs($this->admin())->get('/offers/spaces/'.$studio->id)->assertOk();
        $this->assertSame(1, ListingView::count());

        $this->actingAs($visitor)->get('/offers/haul/'.$van->id)->assertOk();
        $this->assertSame(1, ListingView::count());

        $this->travel(31)->minutes();
        $this->actingAs($visitor)->get('/offers/spaces/'.$studio->id)->assertOk();
        $this->assertSame(2, ListingView::count());

        auth()->logout();
        $this->getJson('/api/v1/offers/spaces/'.$studio->id)->assertOk();
        $this->getJson('/api/v1/offers/spaces/'.$studio->id)->assertOk();
        $this->assertSame(3, ListingView::count());
        $this->assertSame(1, ListingView::whereNull('user_id')->count());
    }
}
