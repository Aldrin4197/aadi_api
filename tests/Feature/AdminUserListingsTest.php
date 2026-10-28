<?php
namespace Tests\Feature;
use App\Models\User;
use App\Services\Marketplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
class AdminUserListingsTest extends TestCase {
    use RefreshDatabase;
    public function test_inventory_requires_admin_and_is_scoped_to_selected_owner(): void {
        $this->withoutVite();
        $owner=User::factory()->create(['landlord_status'=>'approved','vehicle_owner_status'=>'approved','service_provider_status'=>'approved']);
        $other=User::factory()->create(['landlord_status'=>'approved','vehicle_owner_status'=>'approved','service_provider_status'=>'approved']);
        $admin=User::factory()->create();$admin->forceFill(['is_admin'=>true])->save();
        $url='/admin/users/'.$owner->id.'/listings';
        $this->get($url)->assertRedirect('/login');
        $this->actingAs($owner)->get($url)->assertForbidden();
        foreach (['spaces','haul','raketeer'] as $service) {
            foreach ([$owner,$other] as $user) app(Marketplace::class)->save($user,$service,[
                'title'=>$user->id === $owner->id?'Owner draft':'Other private draft','description'=>'Private admin inspection',
                'type'=>Marketplace::TYPES[$service][0],'price'=>1000,'status'=>'draft','photos'=>[],
                'location'=>['lat'=>11.77,'lng'=>124.88,'city'=>'Catbalogan','address'=>'Test address'],
                'details'=>['brand'=>'Toyota','model'=>'Hiace','year'=>2024],
            ]);
            $this->actingAs($admin)->get($url.'?service='.$service)->assertOk()->assertInertia(fn(Assert $page)=>$page
                ->component('Admin/Users/Listings')->where('owner.id',$owner->id)->where('service',$service)
                ->has('results.data',1)->where('results.data.0.ownerId',$owner->id)->where('results.data.0.title','Owner draft')
                ->where('results.data.0.sourceStatus',$service==='spaces'?'draft':'inactive'));
        }
        $this->get('/admin/users?q='.urlencode($owner->email))->assertOk()->assertInertia(fn(Assert $page)=>$page
            ->component('Admin/Users/Index')->has('users.data',1)->where('users.data.0.listings_count',1)
            ->where('users.data.0.vehicle_listings_count',1)->where('users.data.0.service_listings_count',1));
        $this->get($url.'?service=unknown')->assertSessionHasErrors('service');
    }
}
