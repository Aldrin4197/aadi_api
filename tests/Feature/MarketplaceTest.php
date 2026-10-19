<?php
namespace Tests\Feature;
use App\Models\User;
use App\Services\Marketplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MarketplaceTest extends TestCase {
    use RefreshDatabase;
    private function payload(string $service, string $status = 'active'): array {
        return ['title'=>'Nearby '.$service,'description'=>'A useful listing for the neighborhood.',
            'type'=>Marketplace::TYPES[$service][0], 'price'=>1250, 'status'=>$status,
            'location'=>['lat'=>11.7753,'lng'=>124.8861,'address'=>'Catbalogan','city'=>'Catbalogan'],
            'photos'=>[], 'details'=>['brand'=>'Toyota','model'=>'Hiace','year'=>2024]];
    }
    private function provider(): User {
        return User::factory()->create(['landlord_status'=>'approved','vehicle_owner_status'=>'approved','service_provider_status'=>'approved']);
    }
    public function test_public_browsing_and_guest_write_protection(): void {
        $this->withoutVite()->get('/')->assertOk();
        $this->getJson('/api/v1/offers?service=spaces')->assertOk()->assertJsonPath('meta.total',0);
        $this->postJson('/api/v1/offers/spaces',$this->payload('spaces'))->assertUnauthorized();
    }
    public function test_all_services_share_crud_and_admin_records(): void {
        $owner=$this->provider();
        foreach (array_keys(Marketplace::TYPES) as $service) {
            $response=$this->actingAs($owner)->postJson('/api/v1/offers/'.$service,$this->payload($service))->assertCreated();
            $id=$response->json('data.id');
            $this->assertDatabaseHas(match($service){'spaces'=>'listings','haul'=>'vehicle_listings','raketeer'=>'service_listings'},['id'=>$id]);
            $this->getJson('/api/v1/offers?service='.$service)->assertOk()->assertJsonPath('data.0.id',$id)->assertJsonPath('data.0.location.lat',11.7753);
            $this->putJson('/api/v1/offers/'.$service.'/'.$id,[...$this->payload($service),'title'=>'Updated'])->assertOk()->assertJsonPath('data.title','Updated');
            $this->deleteJson('/api/v1/offers/'.$service.'/'.$id)->assertNoContent();
            $this->getJson('/api/v1/offers/'.$service.'/'.$id)->assertNotFound();
        }
    }
    public function test_non_owner_cannot_read_drafts_update_or_delete(): void {
        $owner=$this->provider();
        foreach (array_keys(Marketplace::TYPES) as $service) {
            $record=app(Marketplace::class)->save($owner,$service,$this->payload($service,'draft'));
            $url='/api/v1/offers/'.$service.'/'.$record->id;
            $this->actingAs(User::factory()->create())->getJson($url)->assertForbidden();
            $this->putJson($url,$this->payload($service))->assertForbidden();
            $this->deleteJson($url)->assertForbidden();
            $this->getJson('/api/v1/offers?service='.$service)->assertJsonPath('meta.total',0);
        }
    }
    public function test_unapproved_users_cannot_manage_listings_or_self_approve(): void {
        $user=User::factory()->create();
        $this->actingAs($user)->postJson('/api/v1/offers/spaces',$this->payload('spaces','draft'))->assertForbidden();
        $this->postJson('/api/v1/offers/spaces',$this->payload('spaces'))->assertForbidden();
        $this->get('/my-listings')->assertRedirect('/become-provider');
        $this->get('/my-listings/spaces/new')->assertRedirect('/provider-applications/spaces');
        $this->postJson('/api/me/landlord-application/approve')->assertForbidden();
        $this->get('/admin/dashboard')->assertForbidden();
        $this->get('/admin/approvals')->assertForbidden();
    }
    public function test_admin_can_approve_and_publishing_then_succeeds(): void {
        Storage::fake('local');
        $owner=User::factory()->create();
        $this->actingAs($owner)->post('/provider-applications/spaces', [
            'displayName'=>'Local property host','phone'=>'09171234567','area'=>'Catbalogan',
            'description'=>'I manage a property for long-term rental.',
            'documents'=>[UploadedFile::fake()->image('id.jpg')],'confirmed'=>true,
        ])->assertRedirect('/provider-applications/spaces');
        $admin=User::factory()->create();$admin->forceFill(['is_admin'=>true])->save();
        $this->actingAs($admin)->post('/admin/approvals/'.$owner->id.'/spaces',['status'=>'approved'])->assertRedirect();
        $this->actingAs($owner->fresh())->postJson('/api/v1/offers/spaces',$this->payload('spaces'))->assertCreated();
    }
    public function test_invalid_coordinates_types_and_price_are_rejected(): void {
        $this->actingAs($this->provider())->postJson('/api/v1/offers/spaces',[...$this->payload('spaces'),'type'=>'truck','price'=>-1,'location'=>['lat'=>95,'lng'=>181]])->assertUnprocessable()->assertJsonValidationErrors(['type','price','location.lat','location.lng']);
        $this->getJson('/api/v1/offers?service=unknown')->assertUnprocessable();
    }
    public function test_nearby_search_filters_distance_and_zero_price(): void {
        $owner=$this->provider();
        $service=app(Marketplace::class);
        $near=$service->save($owner,'spaces',[...$this->payload('spaces'),'price'=>0]);
        $service->save($owner,'spaces',[...$this->payload('spaces'),'location'=>['lat'=>14.6,'lng'=>120.98,'address'=>'Manila']]);
        $this->getJson('/api/v1/offers?service=spaces&lat=11.7753&lng=124.8861&radiusKm=5&maxPrice=0')->assertOk()->assertJsonPath('meta.total',1)->assertJsonPath('data.0.id',(string)$near->id);
    }
    public function test_busy_and_rented_statuses_survive_edits(): void {
        $owner=$this->provider();
        foreach (['spaces'=>'rented','haul'=>'rented','raketeer'=>'reserved'] as $service=>$status) {
            $response=$this->actingAs($owner)->postJson('/api/v1/offers/'.$service,$this->payload($service,$status))->assertCreated();
            $this->putJson('/api/v1/offers/'.$service.'/'.$response->json('data.id'),[...$this->payload($service,$status),'title'=>'Edited'])->assertOk()->assertJsonPath('data.status',$status);
            $this->getJson('/api/v1/offers?service='.$service)->assertJsonPath('meta.total',0);
        }
    }
    public function test_image_upload_is_authenticated_and_validated(): void {
        Storage::fake('public');
        $this->actingAs($this->provider())->post('/listing-photos',['photo'=>UploadedFile::fake()->image('room.jpg')],['Accept'=>'application/json'])->assertCreated()->assertJsonStructure(['url']);
        $this->post('/listing-photos',['photo'=>UploadedFile::fake()->create('script.php')],['Accept'=>'application/json'])->assertUnprocessable();
    }
}
