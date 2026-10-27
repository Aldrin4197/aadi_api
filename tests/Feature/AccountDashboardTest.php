<?php
namespace Tests\Feature;

use App\Models\User;
use App\Services\Marketplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function listing(User $owner, string $service, array $photos = [])
    {
        return app(Marketplace::class)->save($owner, $service, [
            'title'=>'Own listing', 'description'=>'A listing for the dashboard test.',
            'type'=>Marketplace::TYPES[$service][0], 'price'=>1000, 'status'=>'draft',
            'photos'=>$photos, 'location'=>['lat'=>11.77,'lng'=>124.88,'city'=>'Catbalogan','address'=>'Test address'],
            'details'=>['brand'=>'Toyota','model'=>'Hiace','year'=>2024],
        ]);
    }

    public function test_dashboard_is_private_and_counts_only_the_current_users_listings(): void
    {
        $this->withoutVite();
        $this->get('/dashboard')->assertRedirect('/login');
        $owner = User::factory()->create(['landlord_status'=>'approved','vehicle_owner_status'=>'approved','service_provider_status'=>'approved']);
        $other = User::factory()->create(['landlord_status'=>'approved']);
        $this->listing($other, 'spaces');
        foreach (['spaces','haul','raketeer'] as $service) $this->listing($owner, $service);
        $this->actingAs($owner)->get('/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Account/Dashboard')->has('inventory',3)->has('recentListings',3)
            ->where('inventory.0.total',1)->where('inventory.0.drafts',1)->where('inventory.0.published',0));
        $this->actingAs(User::factory()->create())->get('/my-listings')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Marketplace/Manage')->has('listings',0));
    }

    public function test_gallery_order_and_featured_photo_survive_persistence_for_each_service(): void
    {
        $owner = User::factory()->create(['landlord_status'=>'approved','vehicle_owner_status'=>'approved','service_provider_status'=>'approved']);
        $photos = ['https://example.com/featured.jpg','https://example.com/second.jpg'];
        foreach (['spaces','haul','raketeer'] as $service) {
            $record = $this->listing($owner, $service, $photos);
            $this->assertSame($photos, app(Marketplace::class)->dto($record->fresh(),$service)['photos']);
            $data = app(Marketplace::class)->dto($record,$service);
            $data['photos'] = array_reverse($photos);
            $this->actingAs($owner)->put('/my-listings/'.$service.'/'.$record->id, $data)->assertRedirect('/my-listings');
            $this->assertSame(array_reverse($photos), app(Marketplace::class)->dto($record->fresh(),$service)['photos']);
            $record->delete();
        }
    }
}
