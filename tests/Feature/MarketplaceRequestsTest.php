<?php

namespace Tests\Feature;

use App\Models\MarketplaceRequest;
use App\Models\User;
use App\Services\Marketplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Database\Seeders\MarketplaceDemoSeeder;
use Database\Seeders\MarketplaceUsersSeeder;
use Tests\TestCase;

class MarketplaceRequestsTest extends TestCase
{
    use RefreshDatabase;

    private function listing(User $owner, string $service, array $extra = [])
    {
        return app(Marketplace::class)->save($owner, $service, [
            'title' => 'A local listing', 'description' => 'Listing details',
            'type' => Marketplace::TYPES[$service][0], 'price' => 1000, 'status' => 'active',
            'location' => ['lat' => 12, 'lng' => 124, 'address' => 'Calbayog'],
            'photos' => [], 'details' => ['brand' => 'Toyota', 'model' => 'Van', 'year' => 2024],
            ...$extra,
        ]);
    }

    private function provider(): User
    {
        return User::factory()->create(['landlord_status' => 'approved', 'vehicle_owner_status' => 'approved', 'service_provider_status' => 'approved', 'phone' => '09123456789']);
    }

    public function test_providers_can_pause_published_listings_without_exposing_drafts(): void
    {
        $this->withoutVite();
        $provider = $this->provider();
        $customer = User::factory()->create();
        foreach (array_keys(Marketplace::TYPES) as $service) {
            $listing = $this->listing($provider, $service);
            $url = "/my-listings/$service/{$listing->id}/availability";
            $this->actingAs($customer)->patch($url, ['paused' => true])->assertForbidden();
            $this->actingAs($provider)->patch($url, ['paused' => true])->assertRedirect();
            $this->assertTrue((bool) $listing->fresh()->availability_paused);
            $results = app(Marketplace::class)->search(['service' => $service]);
            $this->assertTrue(collect($results['data'])->contains(fn ($item) => $item['id'] === $listing->id && $item['availabilityPaused']));
            $this->actingAs($customer)->get("/offers/$service/{$listing->id}")->assertInertia(fn (Assert $page) => $page->where('listing.availabilityPaused', true));
            $payload = ['requestedAt' => $service === 'spaces' ? now()->addDay()->format('Y-m-d\TH:i') : now()->addDay()->toDateString(), 'shareContact' => true];
            if ($service !== 'spaces') $payload['requestedUntil'] = now()->addDays(2)->toDateString();
            $this->post("/offers/$service/{$listing->id}/requests", $payload)->assertSessionHasErrors('requestedAt');
            $this->actingAs($provider)->patch($url, ['paused' => false])->assertRedirect();
            $this->assertFalse((bool) $listing->fresh()->availability_paused);
            $this->actingAs($customer)->post("/offers/$service/{$listing->id}/requests", $payload)->assertRedirect('/requests');
        }
    }

    public function test_accepted_date_ranges_block_overlap_until_cancelled(): void
    {
        $this->withoutVite();
        $provider = $this->provider();
        $customer = User::factory()->create();
        $secondCustomer = User::factory()->create();
        foreach (['haul', 'raketeer'] as $service) {
            $listing = $this->listing($provider, $service);
            $url = "/offers/$service/{$listing->id}/requests";
            $start = now()->addDays(10)->toDateString();
            $end = now()->addDays(12)->toDateString();
            $payload = ['requestedAt' => $start, 'requestedUntil' => $end, 'shareContact' => true];
            $this->actingAs($customer)->post($url, [...$payload, 'requestedUntil' => now()->addDays(9)->toDateString()])->assertSessionHasErrors('requestedUntil');
            $this->post($url, $payload)->assertRedirect('/requests');
            $first = MarketplaceRequest::where('listing_id', $listing->id)->firstOrFail();
            $this->actingAs($secondCustomer)->post($url, $payload)->assertRedirect('/requests');
            $second = MarketplaceRequest::where('listing_id', $listing->id)->whereKeyNot($first->id)->firstOrFail();
            $this->actingAs($provider)->patch('/requests/'.$first->id, ['action' => 'accept', 'shareContact' => true])->assertRedirect();
            $this->patch('/requests/'.$second->id, ['action' => 'accept', 'shareContact' => true])->assertSessionHasErrors('action');
            $this->actingAs($secondCustomer)->post($url, $payload)->assertSessionHasErrors('requestedAt');
            $this->get("/offers/$service/{$listing->id}")->assertInertia(fn (Assert $page) => $page
                ->where('unavailableDates.0.start', $start)->where('unavailableDates.0.end', $end));
            $middle = now()->addDays(11)->toDateString();
            $this->actingAs($customer)->get('/calendar?month='.substr($middle, 0, 7))->assertInertia(fn (Assert $page) => $page
                ->where('events', fn ($events) => collect($events)->contains(fn ($event) =>
                    $event['id'] === $first->id && $event['date'] === $middle)));
            $this->actingAs($customer)->patch('/requests/'.$first->id, ['action' => 'cancel'])->assertRedirect();
            $this->actingAs($provider)->patch('/requests/'.$second->id, ['action' => 'accept', 'shareContact' => true])->assertRedirect();
            $this->assertSame('accepted', $second->fresh()->status);
        }
    }

    public function test_older_requests_without_end_dates_block_only_the_recorded_day(): void
    {
        $provider = $this->provider();
        $customer = User::factory()->create();
        $listing = $this->listing($provider, 'haul');
        $date = now()->addDays(5)->toDateString();
        MarketplaceRequest::create([
            'service' => 'haul', 'listing_id' => $listing->id, 'listing_title' => $listing->title,
            'customer_id' => $customer->id, 'provider_id' => $provider->id, 'requested_at' => $date,
            'status' => 'accepted',
        ]);
        $url = "/offers/haul/{$listing->id}/requests";
        $this->actingAs($customer)->post($url, ['requestedAt' => $date, 'requestedUntil' => $date, 'shareContact' => true])->assertSessionHasErrors('requestedAt');
        $next = now()->addDays(6)->toDateString();
        $this->post($url, ['requestedAt' => $next, 'requestedUntil' => $next, 'shareContact' => true])->assertRedirect('/requests');
    }

    public function test_requests_for_all_categories_require_consent_and_provider_acceptance(): void
    {
        $this->withoutVite();
        $provider = $this->provider();
        $customer = User::factory()->create(['phone' => '09987654321']);
        foreach (array_keys(Marketplace::TYPES) as $service) {
            $listing = $this->listing($provider, $service, $service === 'haul' ? ['driverAvailable' => true] : []);
            $payload = [
                'requestedAt' => $service === 'spaces' ? now()->addDays(2)->format('Y-m-d\TH:i') : now()->addDays(2)->format('Y-m-d'),
                ...($service === 'spaces' ? [] : ['requestedUntil' => now()->addDays(3)->toDateString()]),
                'driverRequested' => $service === 'haul', 'note' => 'Please contact me.', 'shareContact' => true,
            ];
            $url = "/offers/$service/{$listing->id}/requests";
            if ($service === 'spaces') $this->post($url, $payload)->assertRedirect('/login');
            $this->actingAs($customer)->post($url, $payload)->assertRedirect('/requests');
            $item = MarketplaceRequest::where('service', $service)->firstOrFail();
            $this->assertSame('pending', $item->status);
            $this->assertSame($service === 'haul', $item->driver_requested);
            $this->get('/requests')->assertInertia(fn (Assert $page) => $page->component('Account/Requests')
                ->where('requests.data.0.id', $item->id)->where('requests.data.0.contact', null));
            $this->actingAs($provider)->patch('/requests/'.$item->id, ['action' => 'accept'])->assertSessionHasErrors('shareContact');
            $this->patch('/requests/'.$item->id, ['action' => 'accept', 'shareContact' => true])->assertRedirect();
            $this->assertSame('accepted', $item->fresh()->status);
            $this->get('/requests')->assertInertia(fn (Assert $page) => $page
                ->where('requests.data.0.contact.email', $customer->email)->where('requests.data.0.contact.phone', $customer->phone));
            $this->actingAs($customer)->get('/requests')->assertInertia(fn (Assert $page) => $page
                ->where('requests.data.0.contact.email', $provider->email)->where('requests.data.0.contact.phone', $provider->phone));
            $this->patch('/requests/'.$item->id, ['action' => 'cancel'])->assertRedirect();
            $this->get('/requests')->assertInertia(fn (Assert $page) => $page->where('requests.data.0.contact', null));
        }
        $this->assertSame(6, $provider->notifications()->count());
        $this->assertSame(3, $customer->notifications()->count());
    }

    public function test_request_tabs_split_sent_and_received_items_and_include_listing_schedule(): void
    {
        $this->withoutVite();
        $provider = $this->provider();
        $otherProvider = $this->provider();
        $customer = User::factory()->create();
        $customer->forceFill(['avatar_url' => 'https://example.test/customer.webp'])->save();
        $otherProvider->forceFill(['avatar_url' => 'https://example.test/provider.webp'])->save();
        $receivedListing = $this->listing($provider, 'spaces', ['photos' => ['https://example.test/room.webp']]);
        $sentListing = $this->listing($otherProvider, 'spaces');
        $received = MarketplaceRequest::create([
            'service' => 'spaces', 'listing_id' => $receivedListing->id, 'listing_title' => $receivedListing->title,
            'customer_id' => $customer->id, 'provider_id' => $provider->id,
            'requested_at' => '2030-01-03 14:30', 'status' => 'pending',
        ]);
        $sent = MarketplaceRequest::create([
            'service' => 'spaces', 'listing_id' => $sentListing->id, 'listing_title' => $sentListing->title,
            'customer_id' => $provider->id, 'provider_id' => $otherProvider->id,
            'requested_at' => '2030-01-04 09:15', 'status' => 'pending',
        ]);

        $this->actingAs($provider)->get('/requests')->assertInertia(fn (Assert $page) => $page
            ->where('activeTab', 'received')->has('requests.data', 1)
            ->where('requests.data.0.id', $received->id)
            ->where('requests.data.0.photo', 'https://example.test/room.webp')
            ->where('requests.data.0.otherAvatar', 'https://example.test/customer.webp')
            ->where('requests.data.0.scheduledDate', 'Jan 3, 2030')
            ->where('requests.data.0.scheduledTime', '2:30 PM'));
        $this->get('/requests?tab=sent')->assertInertia(fn (Assert $page) => $page
            ->where('activeTab', 'sent')->has('requests.data', 1)
            ->where('requests.data.0.id', $sent->id)
            ->where('requests.data.0.otherAvatar', 'https://example.test/provider.webp'));
        $this->actingAs($customer)->get('/requests?tab=received')->assertInertia(fn (Assert $page) => $page
            ->where('activeTab', 'sent')->has('requests.data', 1)
            ->where('requests.data.0.id', $received->id)
            ->where('requests.data.0.isProvider', false));
    }

    public function test_access_control_validation_and_status_transitions(): void
    {
        $this->withoutVite();
        $provider = $this->provider();
        $customer = User::factory()->create();
        $stranger = User::factory()->create();
        $listing = $this->listing($provider, 'haul');
        $url = "/offers/haul/{$listing->id}/requests";
        $payload = ['requestedAt' => now()->addDay()->toDateString(), 'requestedUntil' => now()->addDays(2)->toDateString(), 'shareContact' => true];
        $this->actingAs($customer)->post($url, [...$payload, 'shareContact' => false])->assertSessionHasErrors('shareContact');
        $this->post($url, [...$payload, 'requestedAt' => now()->subDay()->toDateString()])->assertSessionHasErrors('requestedAt');
        $this->post($url, [...$payload, 'driverRequested' => true])->assertSessionHasErrors('driverRequested');
        $this->actingAs($provider)->post($url, $payload)->assertSessionHasErrors('requestedAt');
        $this->actingAs($customer)->post($url, $payload)->assertRedirect('/requests');
        $item = MarketplaceRequest::firstOrFail();
        $this->patch('/requests/'.$item->id, ['action' => 'accept', 'shareContact' => true])->assertForbidden();
        $this->actingAs($stranger)->patch('/requests/'.$item->id, ['action' => 'cancel'])->assertForbidden();
        $this->get('/requests')->assertInertia(fn (Assert $page) => $page->has('requests.data', 0));
        $this->actingAs($provider)->patch('/requests/'.$item->id, ['action' => 'decline'])->assertRedirect();
        $this->patch('/requests/'.$item->id, ['action' => 'accept', 'shareContact' => true])->assertForbidden();
        $this->actingAs($customer)->patch('/requests/'.$item->id, ['action' => 'cancel'])->assertForbidden();
    }

    public function test_request_cards_only_link_to_listings_visible_to_each_party(): void
    {
        $this->withoutVite();
        $provider = $this->provider();
        $customer = User::factory()->create();
        foreach (array_keys(Marketplace::TYPES) as $service) {
            $listing = $this->listing($provider, $service);
            $this->actingAs($customer)->post("/offers/$service/{$listing->id}/requests", [
                'requestedAt' => $service === 'spaces' ? now()->addDay()->format('Y-m-d\TH:i') : now()->addDay()->toDateString(),
                ...($service === 'spaces' ? [] : ['requestedUntil' => now()->addDays(2)->toDateString()]),
                'shareContact' => true,
            ])->assertRedirect('/requests');
            $item = MarketplaceRequest::where('listing_id', $listing->id)->firstOrFail();
            $assertVisibility = function (bool $visible) use ($item) {
                return fn (Assert $page) => $page->where('requests.data', fn ($items) =>
                    collect($items)->contains(fn ($row) => $row['id'] === $item->id
                        && $row['listingId'] === $item->listing_id
                        && $row['service'] === $item->service
                        && $row['canViewListing'] === $visible));
            };
            $this->get('/requests')->assertInertia($assertVisibility(true));
            $this->get("/offers/$service/{$listing->id}")->assertOk();
            $listing->update(['status' => $service === 'spaces' ? 'draft' : 'inactive']);
            $this->get('/requests')->assertInertia($assertVisibility(false));
            $this->get("/offers/$service/{$listing->id}")->assertForbidden();
            $this->actingAs($provider)->get('/requests')->assertInertia($assertVisibility(true));
            $this->get("/offers/$service/{$listing->id}")->assertOk();
            $listing->delete();
            $this->get('/requests')->assertInertia($assertVisibility(false));
            $this->get("/offers/$service/{$listing->id}")->assertNotFound();
        }
    }

    public function test_unavailable_listings_and_removed_driver_cannot_be_accepted(): void
    {
        $provider = $this->provider();
        $customer = User::factory()->create();
        $listing = $this->listing($provider, 'haul', ['driverAvailable' => true]);
        $url = "/offers/haul/{$listing->id}/requests";
        $payload = ['requestedAt' => now()->addDay()->toDateString(), 'requestedUntil' => now()->addDays(2)->toDateString(), 'driverRequested' => true, 'shareContact' => true];
        $this->actingAs($customer)->post($url, $payload)->assertRedirect('/requests');
        $item = MarketplaceRequest::firstOrFail();
        $listing->update(['driver_available' => false]);
        $this->actingAs($provider)->patch('/requests/'.$item->id, ['action' => 'accept', 'shareContact' => true])->assertSessionHasErrors('action');
        $listing->update(['status' => 'inactive']);
        $this->actingAs($customer)->post($url, [...$payload, 'driverRequested' => false])->assertSessionHasErrors('requestedAt');
        $this->actingAs($provider)->patch('/requests/'.$item->id, ['action' => 'decline'])->assertRedirect();
    }

    public function test_samples_and_expired_requests_cannot_be_accepted(): void
    {
        $provider = $this->provider();
        $customer = User::factory()->create();
        $listing = $this->listing($provider, 'spaces');
        $url = "/offers/spaces/{$listing->id}/requests";
        $payload = ['requestedAt' => now()->addDay()->format('Y-m-d\TH:i'), 'shareContact' => true];
        $listing->forceFill(['is_sample' => true])->save();
        $this->actingAs($customer)->post($url, $payload)->assertSessionHasErrors('requestedAt');
        $listing->forceFill(['is_sample' => false])->save();
        $this->post($url, $payload)->assertRedirect('/requests');
        $item = MarketplaceRequest::firstOrFail();
        $item->update(['requested_at' => now()->subDay()]);
        $this->actingAs($provider)->patch('/requests/'.$item->id, ['action' => 'accept', 'shareContact' => true])->assertSessionHasErrors('action');
        $this->assertSame('pending', $item->fresh()->status);
    }

    public function test_listing_rules_are_saved_and_existing_values_survive_older_clients(): void
    {
        $provider = $this->provider();
        $market = app(Marketplace::class);
        $space = $this->listing($provider, 'spaces', ['minimumStayMonths' => 6, 'contractRules' => 'No pets']);
        $vehicle = $this->listing($provider, 'haul', ['driverAvailable' => true]);
        $this->assertSame(6, (int) $market->dto($space, 'spaces')['minimumStayMonths']);
        $this->assertSame('No pets', $market->dto($space, 'spaces')['contractRules']);
        $this->assertTrue($market->dto($vehicle, 'haul')['driverAvailable']);
        $this->actingAs($provider)->putJson('/api/v1/offers/haul/'.$vehicle->id, [
            'title' => 'Changed', 'description' => 'Details', 'type' => 'van', 'price' => 1000, 'status' => 'active',
            'location' => ['lat' => 12, 'lng' => 124, 'address' => 'Calbayog'], 'photos' => [],
            'details' => ['brand' => 'Toyota', 'model' => 'Van', 'year' => 2024],
        ])->assertOk()->assertJsonPath('data.driverAvailable', true);
    }

    public function test_seeded_examples_remain_non_requestable(): void
    {
        $this->seed([MarketplaceDemoSeeder::class, MarketplaceUsersSeeder::class]);
        $customer = User::factory()->create();
        $market = app(Marketplace::class);
        foreach (array_keys(Marketplace::TYPES) as $service) {
            $listing = $market->model($service)::where('is_sample', true)->firstOrFail();
            $this->assertTrue($market->dto($listing, $service)['isSample']);
            $this->actingAs($customer)->post("/offers/$service/{$listing->id}/requests", [
                'requestedAt' => $service === 'spaces' ? now()->addDay()->format('Y-m-d\TH:i') : now()->addDay()->toDateString(),
                ...($service === 'spaces' ? [] : ['requestedUntil' => now()->addDays(2)->toDateString()]),
                'shareContact' => true,
            ])->assertSessionHasErrors('requestedAt');
        }
        $this->assertDatabaseCount('marketplace_requests', 0);
    }
}
