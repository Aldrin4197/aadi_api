<?php
namespace Tests\Feature;
use App\Models\User;
use App\Services\Marketplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ListingEngagementTest extends TestCase {
    use RefreshDatabase;
    public function test_saves_and_reviews_work_for_every_category_without_allowing_owner_reviews(): void {
        $owner = User::factory()->create(['landlord_status'=>'approved','vehicle_owner_status'=>'approved','service_provider_status'=>'approved']);
        $viewer = User::factory()->create();
        foreach (['spaces','haul','raketeer'] as $service) {
            $record = app(Marketplace::class)->save($owner, $service, [
                'title'=>'Test listing', 'description'=>'Test description', 'type'=>Marketplace::TYPES[$service][0],
                'price'=>1000, 'status'=>'active', 'photos'=>[],
                'location'=>['lat'=>11.77,'lng'=>124.88,'city'=>'Catbalogan','address'=>'Test address'],
                'details'=>['brand'=>'Toyota','model'=>'Hiace','year'=>2024],
            ]);
            $url = '/api/v1/offers/'.$service.'/'.$record->id;
            $this->actingAs($viewer, 'sanctum')->putJson($url.'/save')->assertNoContent();
            $this->putJson($url.'/save')->assertNoContent();
            $this->getJson($url.'/engagement')->assertOk()->assertJsonPath('saved',true)->assertJsonPath('count',0);
            $this->putJson($url.'/review', ['rating'=>6,'body'=>'Useful experience here'])->assertUnprocessable();
            $this->putJson($url.'/review', ['rating'=>4,'body'=>'Useful experience here'])->assertNoContent();
            $this->putJson($url.'/review', ['rating'=>5,'body'=>'Updated experience here'])->assertNoContent();
            $this->getJson($url.'/engagement')->assertJsonPath('count',1)->assertJsonPath('myReview.rating',5);
            $this->actingAs($owner, 'sanctum')->putJson($url.'/review',['rating'=>5,'body'=>'Review my own listing'])->assertForbidden();
            $this->deleteJson($url.'/review')->assertNoContent();
            $this->getJson($url.'/engagement')->assertJsonPath('count',1)->assertJsonPath('saved',false);
            $this->actingAs($viewer, 'sanctum')->deleteJson($url.'/review')->assertNoContent();
            $this->deleteJson($url.'/save')->assertNoContent();
            $this->getJson($url.'/engagement')->assertJsonPath('count',0)->assertJsonPath('saved',false);
            $record->delete();
            $this->putJson($url.'/save')->assertNotFound();
        }
    }
    public function test_guests_cannot_save_or_review(): void {
        $this->putJson('/api/v1/offers/spaces/missing/save')->assertUnauthorized();
        $this->putJson('/api/v1/offers/spaces/missing/review')->assertUnauthorized();
        $this->getJson('/api/v1/saved-listings')->assertUnauthorized();
    }
}
