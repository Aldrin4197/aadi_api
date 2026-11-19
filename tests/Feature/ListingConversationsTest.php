<?php

namespace Tests\Feature;

use App\Models\ListingConversation;
use App\Models\User;
use App\Services\Marketplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ListingConversationsTest extends TestCase
{
    use RefreshDatabase;

    private function listing(User $owner, string $service = 'spaces')
    {
        $owner->forceFill([
            'landlord_status' => 'approved', 'vehicle_owner_status' => 'approved', 'service_provider_status' => 'approved',
        ])->save();
        return app(Marketplace::class)->save($owner, $service, [
            'title' => 'A local listing', 'description' => 'Listing details',
            'type' => Marketplace::TYPES[$service][0], 'price' => 1000, 'status' => 'active',
            'location' => ['lat' => 12, 'lng' => 124, 'address' => 'Calbayog'],
            'photos' => [], 'details' => ['brand' => 'Toyota', 'model' => 'Van', 'year' => 2024],
        ]);
    }

    public function test_threads_are_per_listing_and_customer_and_participants_can_reply(): void
    {
        $this->withoutVite();
        $provider = User::factory()->create();
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();
        foreach (array_keys(Marketplace::TYPES) as $service) {
            $listing = $this->listing($provider, $service);
            $url = "/offers/{$service}/{$listing->id}/messages";
            if ($service === 'spaces') $this->post($url, ['body' => 'Hello'])->assertRedirect('/login');
            $this->actingAs($customer)->post($url, ['body' => 'Is it available?'])->assertRedirect();
            $conversation = ListingConversation::where('listing_id', $listing->id)->firstOrFail();
            $this->assertSame($provider->id, $conversation->provider_id);
            $this->post($url, ['body' => 'Another question'])->assertRedirect("/messages/{$conversation->id}");
            $this->assertSame(2, $conversation->messages()->count());
            $this->actingAs($otherCustomer)->post($url, ['body' => 'My question'])->assertRedirect();
            $this->assertSame(2, ListingConversation::where('listing_id', $listing->id)->count());
            $this->actingAs($provider)->post("/messages/{$conversation->id}", ['body' => 'Yes, it is.'])->assertRedirect("/messages/{$conversation->id}");
            $this->get("/messages/{$conversation->id}")->assertInertia(fn (Assert $page) => $page
                ->component('Account/MessageThread')->where('conversation.other.id', $customer->id)
                ->where('conversations.data.0.id', $conversation->id)
                ->where('messages.data.0.body', 'Is it available?')->where('messages.data.2.mine', true));
            $this->actingAs($customer)->get('/messages')->assertInertia(fn (Assert $page) => $page
                ->component('Account/Messages')->where('conversations.data.0.latestBody', 'Yes, it is.'));
            $this->assertDatabaseHas('notifications', [
                'notifiable_id' => $customer->id,
                'data' => json_encode(['kind' => 'message', 'title' => 'New listing message', 'text' => $provider->name.' sent a message about A local listing.', 'href' => "/messages/{$conversation->id}", 'tone' => 'info']),
            ]);
        }
        $this->assertDatabaseCount('listing_conversations', 6);
        $this->assertDatabaseCount('notifications', 12);
    }

    public function test_threads_are_private_and_reject_invalid_or_self_messages(): void
    {
        $this->withoutVite();
        $provider = User::factory()->create();
        $customer = User::factory()->create();
        $outsider = User::factory()->create(['is_admin' => true]);
        $listing = $this->listing($provider);
        $url = "/offers/spaces/{$listing->id}/messages";
        $this->actingAs($provider)->post($url, ['body' => 'Hello'])->assertForbidden();
        $this->actingAs($customer)->post($url, ['body' => ' '])->assertSessionHasErrors('body');
        $this->post($url, ['body' => str_repeat('a', 2001)])->assertSessionHasErrors('body');
        $this->post($url, ['body' => 'Hello'])->assertRedirect();
        $conversation = ListingConversation::firstOrFail();
        $this->actingAs($outsider)->get("/messages/{$conversation->id}")->assertForbidden();
        $this->getJson("/messages/{$conversation->id}")->assertForbidden();
        $this->post("/messages/{$conversation->id}", ['body' => 'Intrusion'])->assertForbidden();
        $this->get('/messages')->assertInertia(fn (Assert $page) => $page->has('conversations.data', 0));
        $this->assertDatabaseCount('listing_messages', 1);
    }

    public function test_unavailable_listings_and_restricted_recipients_cannot_start_conversations(): void
    {
        $provider = User::factory()->create();
        $customer = User::factory()->create();
        $listing = $this->listing($provider);
        $url = "/offers/spaces/{$listing->id}/messages";
        $this->actingAs($customer);
        $listing->forceFill(['availability_paused' => true])->save();
        $this->post($url, ['body' => 'Hello'])->assertSessionHasErrors('body');
        $listing->forceFill(['availability_paused' => false, 'is_sample' => true])->save();
        $this->post($url, ['body' => 'Hello'])->assertSessionHasErrors('body');
        $listing->forceFill(['is_sample' => false, 'status' => 'draft'])->save();
        $this->post($url, ['body' => 'Hello'])->assertSessionHasErrors('body');
        $listing->forceFill(['status' => 'active'])->save();
        $provider->forceFill(['restriction_type' => 'disabled'])->save();
        $this->post($url, ['body' => 'Hello'])->assertSessionHasErrors('body');
        $this->assertDatabaseCount('listing_conversations', 0);
    }

    public function test_deleted_listings_preserve_threads_and_restricted_recipients_cannot_receive_replies(): void
    {
        $this->withoutVite();
        $provider = User::factory()->create();
        $customer = User::factory()->create();
        $listing = $this->listing($provider);
        $this->actingAs($customer)->post("/offers/spaces/{$listing->id}/messages", ['body' => 'Hello'])->assertRedirect();
        $conversation = ListingConversation::firstOrFail();
        $listing->delete();
        $this->get("/messages/{$conversation->id}")->assertInertia(fn (Assert $page) => $page
            ->where('conversation.listingUrl', null)->where('messages.data.0.body', 'Hello'));
        $this->post("/messages/{$conversation->id}", ['body' => 'Still interested'])->assertRedirect();
        $provider->forceFill(['restriction_type' => 'disabled'])->save();
        $this->post("/messages/{$conversation->id}", ['body' => 'Another question'])->assertSessionHasErrors('body');
        $this->assertDatabaseCount('listing_messages', 2);
    }

    public function test_thread_paginates_messages_in_chronological_order(): void
    {
        $this->withoutVite();
        $provider = User::factory()->create();
        $customer = User::factory()->create();
        $listing = $this->listing($provider);
        $this->actingAs($customer)->post("/offers/spaces/{$listing->id}/messages", ['body' => 'First'])->assertRedirect();
        $conversation = ListingConversation::firstOrFail();
        foreach (range(2, 45) as $number) {
            DB::table('listing_messages')->insert(['listing_conversation_id' => $conversation->id, 'sender_id' => $customer->id, 'body' => "Message {$number}", 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->get("/messages/{$conversation->id}")->assertInertia(fn (Assert $page) => $page
            ->has('messages.data', 40)->where('messages.data.0.body', 'Message 6')->where('messages.data.39.body', 'Message 45'));
        $this->get("/messages/{$conversation->id}?page=2")->assertInertia(fn (Assert $page) => $page
            ->has('messages.data', 5)->where('messages.data.0.body', 'First')->where('messages.data.4.body', 'Message 5'));
        $this->getJson("/messages/{$conversation->id}?page=2")->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('messages.current_page', 2)
            ->assertJsonCount(5, 'messages.data')
            ->assertJsonPath('messages.data.0.body', 'First');
    }

    public function test_inbox_orders_threads_by_the_latest_message(): void
    {
        $this->withoutVite();
        $provider = User::factory()->create();
        $customer = User::factory()->create();
        $first = $this->listing($provider);
        $second = $this->listing($provider);
        $this->actingAs($customer)->post("/offers/spaces/{$first->id}/messages", ['body' => 'First thread'])->assertRedirect();
        $conversation = ListingConversation::where('listing_id', $first->id)->firstOrFail();
        $this->post("/offers/spaces/{$second->id}/messages", ['body' => 'Second thread'])->assertRedirect();
        $this->post("/messages/{$conversation->id}", ['body' => 'Latest message'])->assertRedirect();
        $this->get('/messages')->assertInertia(fn (Assert $page) => $page
            ->where('conversations.data.0.id', $conversation->id)
            ->where('conversations.data.0.latestBody', 'Latest message'));
    }
}
