<?php

namespace Tests\Feature;

use App\Models\MarketplaceRequest;
use App\Models\User;
use App\Services\Marketplace;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountRestrictionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_actions_validate_audit_revoke_tokens_and_restore(): void
    {
        $this->withoutVite();
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $url = "/admin/users/{$user->id}/restriction";
        $this->patch($url, ['action' => 'disable', 'reason' => 'spam_abuse'])->assertRedirect('/login');
        $this->actingAs($user)->patch($url, ['action' => 'disable', 'reason' => 'spam_abuse'])->assertForbidden();
        $this->actingAs($admin)->patch($url, ['action' => 'lock', 'reason' => 'invalid', 'until' => now()->subDay()->toIso8601String()])->assertSessionHasErrors(['reason', 'until']);
        $this->patch("/admin/users/{$admin->id}/restriction", ['action' => 'disable', 'reason' => 'spam_abuse'])->assertForbidden();
        $this->patch($url, ['action' => 'lock', 'reason' => 'spam_abuse', 'until' => now()->addHour()->toIso8601String()])->assertRedirect();
        $this->assertTrue($user->fresh()->isRestricted());
        $this->assertDatabaseCount('personal_access_tokens', 0);
        Auth::guard('web')->logout();
        Auth::forgetGuards();
        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
        $this->flushHeaders();
        Auth::shouldUse('web');
        Auth::forgetGuards();
        $this->actingAs($admin);
        $this->get("/admin/users/{$user->id}/listings")->assertInertia(fn ($page) => $page->where('owner.restricted', true)->has('restrictionHistory', 1));
        $this->patch($url, ['action' => 'restore'])->assertRedirect();
        $this->assertFalse($user->fresh()->isRestricted());
        $this->patch($url, ['action' => 'disable', 'reason' => 'fraud_scams'])->assertRedirect();
        $this->travel(2)->days();
        $this->assertTrue($user->fresh()->isRestricted());
        $this->patch($url, ['action' => 'restore'])->assertRedirect();
        $this->assertFalse($user->fresh()->isRestricted());
        $this->assertSame(4, DB::table('account_restriction_events')->count());
    }

    public function test_locked_accounts_cannot_sign_in_or_use_existing_sessions_and_expire_automatically(): void
    {
        $user = User::factory()->create();
        $user->restriction_type = 'locked';
        $user->restricted_until = now()->addHour();
        $user->save();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password'])->assertForbidden();
        $token = $user->createToken('existing')->plainTextToken;
        $this->withToken($token)->getJson('/api/me')->assertForbidden();
        $this->flushHeaders();
        Auth::shouldUse('web');
        Auth::forgetGuards();
        $this->actingAs($user)->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest('web');
        $this->travel(2)->hours();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_can_send_reset_link_and_reset_does_not_restore_access(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $user->restriction_type = 'disabled';
        $user->save();
        $url = "/admin/users/{$user->id}/password-reset";
        $this->actingAs($user)->post($url)->assertRedirect('/login');
        $this->actingAs($admin)->post($url)->assertRedirect()->assertSessionHasNoErrors();
        $this->post($url)->assertSessionHasErrors('reset');
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->post('/logout');
            $this->post('/reset-password', ['email' => $user->email, 'token' => $notification->token, 'password' => 'new-password', 'password_confirmation' => 'new-password'])->assertSessionHasNoErrors();

            return true;
        });
        $this->assertTrue($user->fresh()->isRestricted());
        $this->post('/login', ['email' => $user->email, 'password' => 'new-password'])->assertSessionHasErrors('email');
    }

    public function test_restricted_provider_listings_remain_visible_but_requests_are_rejected(): void
    {
        $this->withoutVite();
        $provider = User::factory()->create(['landlord_status' => 'approved', 'vehicle_owner_status' => 'approved', 'service_provider_status' => 'approved']);
        $customer = User::factory()->create();
        $this->actingAs($customer);
        $market = app(Marketplace::class);
        foreach (array_keys(Marketplace::TYPES) as $service) {
            $listing = $market->save($provider, $service, [
                'title' => 'A local listing', 'description' => 'Details', 'type' => Marketplace::TYPES[$service][0],
                'price' => 1000, 'status' => 'active', 'location' => ['lat' => 12, 'lng' => 124, 'address' => 'Calbayog'],
                'photos' => [], 'details' => ['brand' => 'Toyota', 'model' => 'Van', 'year' => 2024],
            ]);
            $provider->restriction_type = 'disabled';
            $provider->save();
            $payload = ['requestedAt' => $service === 'spaces' ? now()->addDay()->format('Y-m-d\TH:i') : now()->addDay()->toDateString(), 'shareContact' => true];
            if ($service !== 'spaces') {
                $payload['requestedUntil'] = now()->addDays(2)->toDateString();
            }
            $this->assertTrue(collect($market->search(['service' => $service])['data'])->contains(fn ($item) => $item['id'] === $listing->id && $item['availabilityPaused']));
            $detail = $this->get("/offers/$service/{$listing->id}");
            $this->assertSame(200, $detail->status(), 'Detail redirect: '.$detail->headers->get('Location'));
            $detail->assertInertia(fn ($page) => $page->where('listing.availabilityPaused', true));
            $profile = $this->get("/providers/{$provider->id}?service=$service");
            $this->assertSame(200, $profile->status(), 'Profile redirect: '.$profile->headers->get('Location'));
            $profile->assertInertia(fn ($page) => $page->where('results.data.0.availabilityPaused', true));
            $this->actingAs($customer)->post("/offers/$service/{$listing->id}/requests", $payload)->assertSessionHasErrors('requestedAt');
            $this->assertFalse((bool) $listing->fresh()->availability_paused);
            $provider->restriction_type = null;
            $provider->save();
            $this->post("/offers/$service/{$listing->id}/requests", $payload)->assertRedirect('/requests');
        }

    }

    public function test_existing_requests_cannot_be_accepted_while_provider_is_restricted(): void
    {
        $provider = User::factory()->create(['landlord_status' => 'approved']);
        $customer = User::factory()->create();
        $market = app(Marketplace::class);
        $listing = $market->save($provider, 'spaces', [
            'title' => 'A local listing', 'description' => 'Details', 'type' => 'bedspace', 'price' => 1000, 'status' => 'active',
            'location' => ['lat' => 12, 'lng' => 124, 'address' => 'Calbayog'], 'photos' => [], 'details' => [],
        ]);
        $this->actingAs($customer)->post("/offers/spaces/{$listing->id}/requests", [
            'requestedAt' => now()->addDay()->format('Y-m-d\TH:i'), 'shareContact' => true,
        ])->assertRedirect('/requests');
        $item = MarketplaceRequest::firstOrFail();
        $provider->restriction_type = 'disabled';
        $provider->save();
        $this->actingAs($provider->fresh())->patch("/requests/{$item->id}", ['action' => 'accept', 'shareContact' => true])->assertRedirect('/login');
        $this->assertSame('pending', $item->fresh()->status);
        $this->actingAs($customer)->postJson('/api/me/viewings', ['listingId' => $listing->id, 'requestedAt' => now()->addDay()->toIso8601String()])->assertUnprocessable()->assertJsonValidationErrors('listingId');
        $provider->restriction_type = null;
        $provider->save();
        $this->actingAs($provider)->patch("/requests/{$item->id}", ['action' => 'accept', 'shareContact' => true])->assertRedirect();
        $this->assertSame('accepted', $item->fresh()->status);
    }
}
