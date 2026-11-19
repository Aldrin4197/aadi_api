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
                'birth_date' => '1990-01-01',
                'sex_assigned_at_birth' => 'prefer_not_to_say',
                'city' => 'Catbalogan City',
                'address' => 'Private address',
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
                'birth_date' => '1990-01-01',
                'sex_assigned_at_birth' => 'prefer_not_to_say',
                'city' => 'Catbalogan City',
                'address' => 'Private address',
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
                'password' => 'password', 'reason' => 'not_using',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/login');

        $this->assertGuest();
        $this->assertNotNull($user->fresh()->deletion_scheduled_for);
        $this->assertDatabaseHas('account_deletions', ['user_id' => $user->id, 'reason' => 'not_using', 'status' => 'scheduled']);
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

    public function test_social_login_user_can_delete_account_with_email_and_files_are_removed(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::fake('local');
        $user = User::factory()->create(['email' => 'Social@Example.com']);
        DB::table('social_accounts')->insert(['user_id' => $user->id, 'provider' => 'facebook', 'provider_id' => '123', 'created_at' => now(), 'updated_at' => now()]);
        \Illuminate\Support\Facades\Storage::disk('public')->put('avatars/'.$user->id.'/a.jpg', 'x');
        \Illuminate\Support\Facades\Storage::disk('public')->put('listing-photos/'.$user->id.'/p.jpg', 'x');
        \Illuminate\Support\Facades\Storage::disk('local')->put('provider-documents/'.$user->id.'/d.pdf', 'x');
        $user->createToken('mobile');

        $this->actingAs($user)->delete('/profile', ['email' => 'social@example.com', 'reason' => 'privacy'])->assertSessionHasNoErrors()->assertRedirect('/login');
        $this->assertNotNull($user->fresh()->deletion_scheduled_for);
        app(\App\Services\AccountDeletionService::class)->purge($user->fresh());

        $this->assertNull($user->fresh());
        $this->assertSame(0, DB::table('personal_access_tokens')->count());
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing('avatars/'.$user->id.'/a.jpg');
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing('listing-photos/'.$user->id.'/p.jpg');
        \Illuminate\Support\Facades\Storage::disk('local')->assertMissing('provider-documents/'.$user->id.'/d.pdf');
    }

    public function test_email_confirmation_is_not_accepted_without_a_linked_social_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/profile')->delete('/profile', ['email' => $user->email, 'reason' => 'privacy'])->assertSessionHasErrors('password');

        $this->assertNotNull($user->fresh());
    }

    public function test_api_user_can_delete_their_account_with_password(): void
    {
        $user = User::factory()->create();
        \Laravel\Sanctum\Sanctum::actingAs($user);

        $this->deleteJson('/api/me', ['password' => 'wrong', 'reason' => 'privacy'])->assertUnprocessable();
        $this->deleteJson('/api/me', ['password' => 'password'])->assertUnprocessable()->assertJsonValidationErrors('reason');
        $this->deleteJson('/api/me', ['password' => 'password', 'reason' => 'privacy'])->assertStatus(202);

        $this->assertNotNull($user->fresh()->deletion_scheduled_for);
    }
}
