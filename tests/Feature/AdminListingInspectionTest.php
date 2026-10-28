<?php
namespace Tests\Feature;
use App\Models\User;
use App\Services\Marketplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
class AdminListingInspectionTest extends TestCase {
    use RefreshDatabase;
    public function test_all_admin_tables_include_inspection_data_and_require_admin(): void {
        $this->withoutVite();
        $owner=User::factory()->create(['landlord_status'=>'approved','vehicle_owner_status'=>'approved','service_provider_status'=>'approved']);
        $admin=User::factory()->create();$admin->forceFill(['is_admin'=>true])->save();
        foreach (['spaces'=>['listings','Listings'],'haul'=>['vehicles','Vehicles'],'raketeer'=>['services','Services']] as $service=>[$path,$page]) {
            $record=app(Marketplace::class)->save($owner,$service,[
                'title'=>'Inspect this draft','description'=>'Full inspection description','type'=>Marketplace::TYPES[$service][0],
                'price'=>1000,'status'=>'draft','photos'=>['https://example.com/photo.jpg'],
                'location'=>['lat'=>11.77,'lng'=>124.88,'city'=>'Catbalogan','address'=>'Test address'],
                'details'=>['brand'=>'Toyota','model'=>'Hiace','year'=>2024],
            ]);
            $this->actingAs($owner)->get('/admin/'.$path)->assertForbidden();
            $this->actingAs($admin)->get('/admin/'.$path)->assertOk()->assertInertia(fn(Assert $p)=>$p
                ->component('Admin/'.$page.'/Index')->has('listings.data',1)->where('listings.data.0.id',$record->id)
                ->where('listings.data.0.service',$service)->where('listings.data.0.description','Full inspection description')
                ->where('listings.data.0.adminOwner.id',$owner->id)->where('listings.data.0.adminOwner.email',$owner->email)
                ->has('listings.data.0.photos',1)->where('listings.data.0.sourceStatus',$service==='spaces'?'draft':'inactive'));
        }
        $this->actingAs($owner)->get('/admin/approvals')->assertForbidden();
        $this->actingAs($admin)->get('/admin/approvals')->assertOk()->assertInertia(fn(Assert $p)=>$p->component('Marketplace/Approvals'));
    }
}
