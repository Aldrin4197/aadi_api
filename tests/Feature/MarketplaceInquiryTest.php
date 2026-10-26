<?php

namespace Tests\Feature;

use App\Models\{MarketplaceInquiry, User};
use App\Services\Marketplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MarketplaceInquiryTest extends TestCase
{
    use RefreshDatabase;

    private function offer(User $owner, string $service, string $status = 'active') {
        return app(Marketplace::class)->save($owner,$service,[
            'title'=>'Inquiry fixture', 'description'=>'A listing for inquiries', 'type'=>Marketplace::TYPES[$service][0],
            'price'=>1500,'photos'=>[],'status'=>$status,'location'=>['lat'=>11.77,'lng'=>124.88,'address'=>'Catbalogan'],
            'details'=>['brand'=>'Toyota','model'=>'Hiace','year'=>2024],
        ]);
    }
    private function provider(): User {
        return User::factory()->create(['landlord_status'=>'approved','vehicle_owner_status'=>'approved','service_provider_status'=>'approved']);
    }
    private function token(User $user): void {
        Auth::forgetGuards();
        $this->withToken($user->createToken('mobile')->plainTextToken);
    }
    public function test_guests_cannot_send_or_read_inquiries(): void {
        $record = $this->offer($this->provider(), 'spaces');
        $this->postJson('/api/v1/offers/spaces/'.$record->id.'/inquiries',['message'=>'Is this available?'])->assertUnauthorized();
        $this->getJson('/api/v1/me/inquiries')->assertUnauthorized();
        $this->post('/offers/spaces/'.$record->id.'/inquiries',['message'=>'Is this available?'])->assertRedirect('/login');
        $this->assertDatabaseCount('marketplace_inquiries',0);
    }
    public function test_mobile_inquiries_and_web_replies_share_a_private_conversation_for_every_service(): void {
        $owner = $this->provider(); $customer = User::factory()->create(); $stranger = User::factory()->create();
        foreach (array_keys(Marketplace::TYPES) as $service) {
            $record = $this->offer($owner,$service);
            $this->token($customer);
            $path = '/api/v1/offers/'.$service.'/'.$record->id.'/inquiries';
            $id = $this->postJson($path,['message'=>'Is this available next week?','owner_id'=>$stranger->id])
                ->assertCreated()->assertJsonPath('data.owner.id',$owner->id)->json('data.id');
            $this->postJson($path,['message'=>'Can you confirm the rate?'])->assertCreated()->assertJsonPath('data.id',$id);
            $this->token($owner);
            $this->getJson('/api/v1/inquiries/'.$id)->assertOk()->assertJsonPath('data.messages.meta.total',2);
            $this->postJson('/api/v1/inquiries/'.$id.'/messages',['message'=>'Yes, it is available.'])
                ->assertCreated()->assertJsonPath('data.senderId',$owner->id);
            $this->token($stranger);
            $this->getJson('/api/v1/inquiries/'.$id)->assertNotFound();
            $this->postJson('/api/v1/inquiries/'.$id.'/messages',['message'=>'Unauthorized reply'])->assertNotFound();
            $this->getJson('/api/v1/me/inquiries')->assertJsonPath('meta.total',0);
        }
        $this->assertDatabaseCount('marketplace_inquiries',3);
        $this->assertDatabaseCount('inquiry_messages',9);
        Auth::forgetGuards();
        $this->withHeader('Authorization','')->actingAs($owner)->withoutVite()->get('/inquiries')->assertInertia(fn (Assert $page) => $page->has('inquiries.data',3));
        $id = MarketplaceInquiry::firstOrFail()->id;
        $this->post('/inquiries/'.$id.'/messages',['message'=>'Reply from the web'])->assertRedirect('/inquiries/'.$id);
        $this->assertDatabaseHas('inquiry_messages',['inquiry_id'=>$id,'body'=>'Reply from the web']);
    }
    public function test_own_listing_draft_and_invalid_messages_are_rejected(): void {
        $owner = $this->provider(); $customer = User::factory()->create();
        $record = $this->offer($owner,'spaces'); $draft = $this->offer($owner,'haul','draft');
        $this->token($owner);
        $this->postJson('/api/v1/offers/spaces/'.$record->id.'/inquiries',['message'=>'My own listing'])->assertForbidden();
        $this->token($customer);
        $this->postJson('/api/v1/offers/haul/'.$draft->id.'/inquiries',['message'=>'Draft inquiry'])->assertNotFound();
        $this->postJson('/api/v1/offers/spaces/'.$record->id.'/inquiries',['message'=>''])->assertUnprocessable();
        $this->postJson('/api/v1/offers/spaces/'.$record->id.'/inquiries',['message'=>str_repeat('x',3001)])->assertUnprocessable();
        $this->assertDatabaseCount('inquiry_messages',0);
    }
    public function test_removing_listing_keeps_existing_conversation_but_blocks_new_inquiries(): void {
        $owner = $this->provider(); $customer = User::factory()->create();
        $record = $this->offer($owner,'spaces');
        $this->token($customer);
        $path = '/api/v1/offers/spaces/'.$record->id.'/inquiries';
        $id = $this->postJson($path,['message'=>'Is this available?'])->assertCreated()->json('data.id');
        $record->delete();
        $this->getJson('/api/v1/inquiries/'.$id)->assertOk()->assertJsonPath('data.listingAvailable',false);
        $this->postJson($path,['message'=>'New inquiry'])->assertNotFound();
        $this->postJson('/api/v1/inquiries/'.$id.'/messages',['message'=>'Thanks for letting me know.'])->assertCreated();
    }
}
