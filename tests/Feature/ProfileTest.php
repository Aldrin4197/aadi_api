<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_settings_requires_authentication_and_shows_only_the_current_users_connections(): void
    {
        $this->get('/settings')->assertRedirect('/login');

        config(['social_login.google.client_id' => 'test-client', 'social_login.google.client_secret' => 'test-secret']);
        $user = User::factory()->create();
        $other = User::factory()->create();
        DB::table('social_accounts')->insert([
            'user_id' => $other->id, 'provider' => 'google', 'provider_id' => 'other-user',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actingAs($user)->withoutVite()->get('/settings')
            ->assertInertia(fn (Assert $page) => $page->component('Account/Settings')
                ->where('connectedProviders', [])->missing('profile'));
        DB::table('social_accounts')->insert([
            'user_id' => $user->id, 'provider' => 'google', 'provider_id' => 'own-user',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->get('/settings')->assertInertia(fn (Assert $page) => $page
            ->where('connectedProviders', ['google']));
    }

    public function test_settings_preserves_password_and_deletion_validation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/settings')->put('/password', [
            'current_password' => 'incorrect',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect('/settings')->assertSessionHasErrors('current_password');

        $this->from('/settings')->delete('/profile', ['password' => 'incorrect'])
            ->assertRedirect('/settings')->assertSessionHasErrors('password');
        $this->assertNotNull($user->fresh());
    }

    public function test_account_navigation_receives_avatar_without_private_credentials(): void
    {
        $user = User::factory()->create(['avatar_url' => '/storage/avatars/sample.jpg']);
        $this->actingAs($user)->withoutVite()->get('/profile')
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.avatar_url', '/storage/avatars/sample.jpg')
                ->missing('auth.user.password')
                ->missing('auth.user.remember_token'));
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
