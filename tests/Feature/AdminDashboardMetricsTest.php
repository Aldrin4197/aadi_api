<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AdminDashboardMetrics;
use App\Services\Marketplace;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminDashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_period_boundaries_zero_days_and_unique_provider_counts(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-27 12:00:00'));
        User::factory()->create(['created_at'=>'2026-10-21 00:00:00', 'landlord_status'=>'approved', 'vehicle_owner_status'=>'approved']);
        User::factory()->create(['created_at'=>'2026-10-20 23:59:59', 'service_provider_status'=>'pending']);
        User::factory()->create(['created_at'=>'2026-10-13 23:59:59']);
        $metrics = app(AdminDashboardMetrics::class)->summarize(7);
        $this->assertSame(['current'=>1, 'previous'=>1], $metrics['newUsers']);
        $this->assertSame(1, $metrics['approvedProviders']);
        $this->assertSame(1, $metrics['pendingRoles']);
        $this->assertCount(7, $metrics['signups']);
        $this->assertSame(1, $metrics['signups'][0]['count']);
        $this->assertSame(0, $metrics['signups'][6]['count']);
        $this->travelBack();
    }

    public function test_inventory_uses_service_statuses_and_excludes_soft_deleted_records(): void
    {
        $owner = User::factory()->create(['landlord_status'=>'approved','vehicle_owner_status'=>'approved','service_provider_status'=>'approved']);
        foreach (['spaces','haul','raketeer'] as $service) {
            foreach (['active','draft'] as $status) {
                app(Marketplace::class)->save($owner, $service, [
                    'title'=>'Dashboard test listing', 'description'=>str_repeat('Description ', 5),
                    'type'=>Marketplace::TYPES[$service][0], 'price'=>1000, 'status'=>$status,
                    'photos'=>[], 'location'=>['lat'=>11.77,'lng'=>124.88,'city'=>'Catbalogan','address'=>'Test address'],
                    'details'=>['brand'=>'Toyota','model'=>'Hiace','year'=>2024],
                ]);
            }
        }
        $metrics = app(AdminDashboardMetrics::class)->summarize(30);
        $this->assertSame(3, $metrics['availableListings']);
        foreach ($metrics['inventory'] as $row) $this->assertSame(2, $row['total']);
        \App\Models\VehicleListing::where('status','available')->firstOrFail()->delete();
        $this->assertSame(2, app(AdminDashboardMetrics::class)->summarize(30)['availableListings']);
    }

    public function test_dashboard_is_admin_only_and_validates_reporting_window(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create())->get('/admin/dashboard')->assertForbidden();
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin'=>true])->save();
        $this->actingAs($admin)->get('/admin/dashboard?days=7')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')->where('metrics.days',7)->has('metrics.signups',7));
        $this->getJson('/admin/dashboard?days=365')->assertUnprocessable();
    }
}
