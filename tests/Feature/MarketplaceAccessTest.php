<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Marketplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MarketplaceAccessTest extends TestCase
{
    use RefreshDatabase;

    private function offer(string $service, string $status = 'active') {
        $owner = User::factory()->create(['landlord_status'=>'approved','vehicle_owner_status'=>'approved','service_provider_status'=>'approved']);
        return app(Marketplace::class)->save($owner, $service, [
            'title'=>'Preview '.$service, 'description'=>'FULL DESCRIPTION PRIVATE TO SIGNED IN VIEWERS',
            'type'=>Marketplace::TYPES[$service][0], 'price'=>1500, 'status'=>$status,
            'photos'=>['https://example.com/cover.jpg', 'https://example.com/inside.jpg'],
            'location'=>['lat'=>11.7753,'lng'=>124.8861,'city'=>'Catbalogan','address'=>'Exact private address'],
            'details'=>['brand'=>'Toyota','model'=>'Hiace','year'=>2024],
        ]);
    }

    public function test_guest_search_detail_and_featured_props_do_not_expose_locked_fields(): void {
        $this->withoutVite();
        foreach (array_keys(Marketplace::TYPES) as $service) {
            $record = $this->offer($service);
            foreach (['/api/v1/offers/'.$service.'/'.$record->id, '/api/v1/offers?service='.$service] as $url) {
                $response = $this->getJson($url)->assertOk();
                $item = str_contains($url, '?') ? $response->json('data.0') : $response->json('data');
                foreach (['description','ownerId','ownerName','details'] as $key) $this->assertArrayNotHasKey($key, $item);
                $this->assertArrayNotHasKey('address', $item['location']);
                $this->assertSame(11.78, $item['location']['lat']);
                $this->assertCount(1, $item['photos']);
                $this->assertFalse($item['access']['canInquire']);
                $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
            }
            $this->get('/offers/'.$service.'/'.$record->id)->assertInertia(fn (Assert $page) => $page
                ->where('listing.access.level','preview')->missing('listing.description')->missing('listing.location.address'));
        }
        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->has('landing.featured',3)->missing('landing.featured.0.description')->missing('results.data.0.ownerName'));
    }

    public function test_real_bearer_token_unlocks_details_and_revoked_or_invalid_token_is_rejected(): void {
        $record = $this->offer('spaces');
        $viewer = User::factory()->create();
        $token = $viewer->createToken('mobile');
        $url = '/api/v1/offers/spaces/'.$record->id;
        $this->withToken($token->plainTextToken)->getJson($url)->assertOk()
            ->assertJsonPath('data.access.level','full')->assertJsonPath('data.access.canInquire',true)
            ->assertJsonPath('data.location.address','Exact private address')->assertJsonCount(2,'data.photos');
        $this->getJson('/api/v1/offers?service=spaces')->assertOk()->assertJsonPath('data.0.location.lat',11.7753);
        $token->accessToken->delete();
        Auth::forgetGuards();
        $this->getJson($url)->assertUnauthorized();
        Auth::forgetGuards();
        $this->withToken('invalid')->getJson($url)->assertUnauthorized();
    }

    public function test_guests_cannot_preview_unpublished_records_and_tokens_cannot_read_another_owners_drafts(): void {
        foreach (array_keys(Marketplace::TYPES) as $service) {
            $record = $this->offer($service, 'draft');
            $this->getJson('/api/v1/offers/'.$service.'/'.$record->id)->assertNotFound();
            $this->get('/offers/'.$service.'/'.$record->id)->assertNotFound();
        }
        $record = $this->offer('spaces','draft');
        $token = User::factory()->create()->createToken('mobile')->plainTextToken;
        $this->withToken($token)->getJson('/api/v1/offers/spaces/'.$record->id)->assertForbidden();
    }

    public function test_registration_unlock_link_returns_to_the_selected_offer(): void {
        $record = $this->offer('spaces');
        $path = '/offers/spaces/'.$record->id;
        $this->get($path.'/unlock?register=1')->assertRedirect('/register');
        $this->post('/register', ['name'=>'Guest viewer','email'=>'viewer@example.com','password'=>'password','password_confirmation'=>'password'])
            ->assertRedirect($path);
        $this->withoutVite()->get($path)->assertInertia(fn (Assert $page) => $page->where('listing.access.level','full'));
    }

    public function test_token_owner_can_list_own_drafts_and_crud_all_services(): void {
        $owner = User::factory()->create(['landlord_status'=>'approved','vehicle_owner_status'=>'approved','service_provider_status'=>'approved']);
        $this->withToken($owner->createToken('mobile')->plainTextToken);
        foreach (array_keys(Marketplace::TYPES) as $service) {
            $other = $this->offer($service);
            $data = ['title'=>'Mobile draft','description'=>'Draft created through mobile contract','type'=>Marketplace::TYPES[$service][0],
                'price'=>750,'status'=>'draft','photos'=>[], 'location'=>['lat'=>11.7753,'lng'=>124.8861,'address'=>'Catbalogan'],
                'details'=>['brand'=>'Toyota','model'=>'Hiace','year'=>2024]];
            $id = $this->postJson('/api/v1/offers/'.$service,$data)->assertCreated()->json('data.id');
            $this->getJson('/api/v1/me/offers?service='.$service)->assertOk()->assertJsonPath('meta.total',1)
                ->assertJsonPath('data.0.id',$id)->assertJsonPath('data.0.access.canManage',true);
            $this->putJson('/api/v1/offers/'.$service.'/'.$other->id,$data)->assertForbidden();
            $this->putJson('/api/v1/offers/'.$service.'/'.$id,[...$data,'status'=>'active'])->assertOk();
            $this->deleteJson('/api/v1/offers/'.$service.'/'.$id)->assertNoContent();
        }
    }
}
