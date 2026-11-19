<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AadiPlusPerks;
use App\Services\Marketplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AadiPlusPerksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['aadi_plus.enabled' => true]);
    }

    private function provider(bool $plus = false): User
    {
        return User::factory()->create([
            'landlord_status' => 'approved', 'vehicle_owner_status' => 'approved', 'service_provider_status' => 'approved',
            'aadi_plus_until' => $plus ? now()->addMonth() : null,
        ]);
    }

    private function payload(array $photos = []): array
    {
        return ['title' => 'Nearby room', 'description' => 'A useful listing for the neighborhood.', 'type' => 'bedspace', 'price' => 1250,
            'status' => 'active', 'location' => ['lat' => 11.7753, 'lng' => 124.8861, 'address' => 'Catbalogan', 'city' => 'Catbalogan'], 'photos' => $photos];
    }

    private function setPerk(string $key, bool $enabled, ?int $free = null, ?int $plus = null): void
    {
        app(AadiPlusPerks::class)->update($key, $enabled, $free, $plus);
    }

    public function test_perks_page_and_updates_are_admin_only_and_validated(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($user)->get('/admin/aadiplus')->assertForbidden();
        $this->actingAs($user)->patch('/admin/aadiplus/perks/saved_listings', ['enabled' => false])->assertForbidden();

        $this->actingAs($admin)->get('/admin/aadiplus')->assertOk();
        $this->patch('/admin/aadiplus/perks/saved_listings', ['enabled' => false, 'freeLimit' => 5])->assertSessionHasNoErrors();
        $this->assertFalse(app(AadiPlusPerks::class)->all()['saved_listings']['enabled']);
        $this->assertSame(5, app(AadiPlusPerks::class)->all()['saved_listings']['freeLimit']);
        $this->patch('/admin/aadiplus/perks/listing_photos', ['enabled' => true, 'plusLimit' => 13])->assertSessionHasErrors('plusLimit');
        $this->patch('/admin/aadiplus/perks/nope', ['enabled' => true])->assertNotFound();
    }

    public function test_admin_can_grant_plus_by_email(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $this->actingAs($admin)->post('/admin/aadiplus/members', ['email' => $user->email, 'until' => today()->addDays(10)->toDateString()])
            ->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->hasAadiPlus());
        $this->post('/admin/aadiplus/members', ['email' => 'missing@example.com', 'until' => today()->toDateString()])->assertSessionHasErrors('email');
    }

    public function test_member_limits_apply_only_while_the_perk_is_enabled_and_membership_active(): void
    {
        $perks = app(AadiPlusPerks::class);
        $member = $this->provider(true);
        $free = $this->provider();

        $this->assertSame(12, $perks->limit('listing_photos', $member));
        $this->assertSame(6, $perks->limit('listing_photos', $free));
        $this->assertNull($perks->limit('saved_listings', $member));

        $this->setPerk('listing_photos', false, 6, 12);
        $this->assertSame(6, $perks->limit('listing_photos', $member));
    }

    public function test_active_listing_limit_blocks_free_providers_but_not_members(): void
    {
        $this->setPerk('active_listings', true, 1, 2);
        $market = app(Marketplace::class);
        $free = $this->provider();
        $market->save($free, 'spaces', $this->payload());

        try {
            $market->save($free, 'spaces', $this->payload());
            $this->fail('Free provider exceeded the active listing limit.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }
        $market->save($free, 'spaces', [...$this->payload(), 'status' => 'draft']);

        $member = $this->provider(true);
        $market->save($member, 'spaces', $this->payload());
        $market->save($member, 'haul', [...$this->payload(), 'type' => 'van', 'details' => ['brand' => 'Toyota', 'model' => 'Hiace', 'year' => 2024]]);
        $this->expectException(ValidationException::class);
        $market->save($member, 'spaces', $this->payload());
    }

    public function test_photo_limit_follows_membership(): void
    {
        $this->setPerk('listing_photos', true, 1, 3);
        $photos = ['https://example.com/a.jpg', 'https://example.com/b.jpg'];

        $this->actingAs($this->provider())->post('/my-listings/spaces', $this->payload($photos))->assertSessionHasErrors('photos');
        $this->actingAs($this->provider(true))->post('/my-listings/spaces', $this->payload($photos))->assertSessionHasNoErrors();
    }

    public function test_saved_search_and_analytics_perks_follow_membership(): void
    {
        $this->setPerk('saved_searches', true, 1, 2);
        $free = $this->provider();
        $body = ['name' => 'Rooms', 'query' => ['service' => 'spaces']];

        $this->actingAs($free)->postJson('/api/me/saved-searches', $body)->assertCreated();
        $this->postJson('/api/me/saved-searches', $body)->assertStatus(422);

        $this->assertFalse(app(AadiPlusPerks::class)->summary($free)['analytics']);
        $this->assertTrue(app(AadiPlusPerks::class)->summary($this->provider(true))['analytics']);
    }

    public function test_suspended_plus_preserves_existing_user_behaviour_and_hides_badges(): void
    {
        config(['aadi_plus.enabled' => false]);
        $this->withoutVite();
        $this->setPerk('active_listings', true, 1, 2);
        $this->setPerk('listing_photos', true, 1, 2);
        $this->setPerk('saved_listings', true, 1, 2);
        $this->setPerk('saved_searches', true, 1, 2);
        $owner = $this->provider(true);
        $viewer = $this->provider();
        $market = app(Marketplace::class);
        $listings = [];
        for ($i = 0; $i < 4; $i++) {
            $listings[] = $market->save($owner, 'spaces', $this->payload());
        }

        $this->assertTrue($owner->fresh()->hasAadiPlus());
        $this->assertFalse($market->dto($listings[0], 'spaces')['ownerAadiPlus']);
        $this->getJson("/api/v1/providers/{$owner->id}")->assertOk()->assertJsonPath('provider.aadiPlus', false);
        $this->actingAs($owner)->get('/my-listings')->assertInertia(fn ($page) => $page
            ->where('auth.user.aadiPlus', false)
            ->where('auth.perks.enabled', false)
            ->where('auth.perks.analytics', true)
            ->where('listings', fn ($items) => collect($items)->every(fn ($item) => isset($item['analytics']))));

        $photos = array_map(fn ($i) => "https://example.com/{$i}.jpg", range(1, 12));
        $this->actingAs($viewer)->post('/my-listings/spaces', $this->payload($photos))->assertSessionHasNoErrors();
        $this->post('/my-listings/spaces', $this->payload([...$photos, 'https://example.com/13.jpg']))->assertSessionHasErrors('photos');
        foreach (array_slice($listings, 0, 2) as $listing) {
            $this->put("/offers/spaces/{$listing->id}/save")->assertSessionHasNoErrors();
        }
        $this->assertSame(2, \Illuminate\Support\Facades\DB::table('favorites')->where('user_id', $viewer->id)->count());
        $body = ['name' => 'Rooms', 'query' => ['service' => 'spaces']];
        $this->postJson('/api/me/saved-searches', $body)->assertCreated();
        $this->postJson('/api/me/saved-searches', $body)->assertCreated();
    }

    public function test_admin_can_prepare_perks_while_suspended(): void
    {
        config(['aadi_plus.enabled' => false]);
        $this->withoutVite();
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get('/admin/aadiplus')
            ->assertInertia(fn ($page) => $page->where('plusEnabled', false));
        $this->patch('/admin/aadiplus/perks/active_listings', ['enabled' => true, 'freeLimit' => 4, 'plusLimit' => 9])
            ->assertSessionHasNoErrors();
        $perks = app(AadiPlusPerks::class);
        $this->assertNull($perks->limit('active_listings', $admin));
        config(['aadi_plus.enabled' => true]);
        $this->assertSame(4, $perks->limit('active_listings', $admin));
    }

    public function test_suspended_plus_does_not_change_search_order(): void
    {
        config(['aadi_plus.enabled' => false]);
        $market = app(Marketplace::class);
        $member = $market->save($this->provider(true), 'spaces', $this->payload());
        $regular = $market->save($this->provider(), 'spaces', $this->payload());
        $member->forceFill(['created_at' => now()->subDay()])->save();
        $regular->forceFill(['created_at' => now()])->save();

        $this->assertSame((string) $regular->id, $market->search(['service' => 'spaces'])['data'][0]['id']);
        config(['aadi_plus.enabled' => true]);
        $this->assertSame((string) $member->id, $market->search(['service' => 'spaces'])['data'][0]['id']);
    }
}
