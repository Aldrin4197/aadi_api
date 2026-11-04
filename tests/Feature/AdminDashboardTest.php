<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Marketplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    public function test_dashboard_requires_an_admin(): void
    {
        $this->withoutVite();
        $this->get('/admin/dashboard')->assertRedirect();
        $this->actingAs(User::factory()->create())->get('/admin/dashboard')->assertForbidden();
    }

    public function test_empty_marketplace_flags_supply_gaps_and_no_signups_trend(): void
    {
        $this->withoutVite();
        $this->actingAs($this->admin())->get('/admin/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->where('overview.range.days', 30)
            ->has('overview.kpis', 6)
            ->has('overview.trend.dates', 30)
            ->has('overview.supply', 3)
            ->where('overview.insights', fn ($insights) => collect($insights)->contains(fn ($i) => str_contains($i['title'], 'No live Spaces'))));
    }

    public function test_overview_counts_live_listings_pending_roles_and_supports_ranges(): void
    {
        $this->withoutVite();
        $owner = User::factory()->create(['landlord_status' => 'approved', 'vehicle_owner_status' => 'approved', 'service_provider_status' => 'approved']);
        User::factory()->create(['service_provider_status' => 'pending']);
        foreach (['spaces', 'haul', 'raketeer'] as $service) {
            app(Marketplace::class)->save($owner, $service, [
                'title' => 'Live '.$service, 'description' => 'Overview fixture.', 'type' => Marketplace::TYPES[$service][0],
                'price' => 500, 'status' => 'active', 'photos' => [],
                'location' => ['lat' => 11.77, 'lng' => 124.88, 'address' => 'Catbalogan', 'city' => 'Catbalogan'],
                'details' => ['brand' => 'Toyota', 'model' => 'Hiace', 'year' => 2024],
            ]);
        }

        $this->actingAs($this->admin())->get('/admin/dashboard?range=7')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('overview.range.days', 7)
            ->has('overview.trend.dates', 7)
            ->where('overview.kpis.1.value', 3)
            ->where('overview.kpis.4.value', 1)
            ->where('overview.supply.0.live', 1)
            ->where('overview.pipeline.2.pending', 1)
            ->where('overview.pipeline.2.approved', 1)
            ->where('overview.insights.0.title', fn ($title) => str_contains($title, 'provider application')));

        $this->actingAs($this->admin())->get('/admin/dashboard?range=999')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('overview.range.days', 30));
    }
}
