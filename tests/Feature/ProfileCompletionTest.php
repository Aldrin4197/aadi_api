<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileCompletionTest extends TestCase
{
    use RefreshDatabase;

    private function details(User $user, array $overrides = []): array
    {
        return array_replace([
            'name' => $user->name,
            'email' => $user->email,
            'birth_date' => '1990-01-01',
            'sex_assigned_at_birth' => 'prefer_not_to_say',
            'city' => 'Catbalogan City',
            'address' => 'Private address',
        ], $overrides);
    }

    public function test_email_registration_requires_a_valid_adult_birthdate_on_web_and_api(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 16)->startOfDay());
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        Notification::fake();
        $payload = ['name' => 'Test User', 'email' => 'new@example.com', 'password' => 'password', 'password_confirmation' => 'password'];

        foreach (['', '2026-02-30', '2008-11-17', '2030-01-01'] as $date) {
            $this->post('/register', [...$payload, 'birth_date' => $date])->assertSessionHasErrors('birth_date');
            $this->postJson('/api/auth/register', [...$payload, 'birth_date' => $date])->assertUnprocessable()->assertJsonValidationErrors('birth_date');
        }
        $this->assertDatabaseMissing('users', ['email' => $payload['email']]);
        $this->post('/register', [...$payload, 'birth_date' => '2008-11-16'])->assertRedirect(route('verification.notice'));
        $this->assertDatabaseHas('users', ['email' => $payload['email'], 'birth_date' => '2008-11-16']);
    }

    public function test_incomplete_web_profile_is_required_for_account_features_but_not_public_or_recovery_pages(): void
    {
        $user = User::factory()->create(['birth_date' => null, 'sex_assigned_at_birth' => null, 'city' => null, 'address' => null]);
        $this->actingAs($user)->withoutVite();
        $this->get('/explore')->assertOk();
        $this->get('/privacy-policy')->assertOk();
        $this->get('/settings')->assertOk();
        $this->get('/verify-email')->assertRedirect();
        $this->get('/profile')->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertInertia(fn ($page) => $page->where('profileIncomplete', true)->where('auth.profileComplete', false));
        $this->get('/saved-listings')->assertRedirect('/profile')->assertStatus(303);
        $this->postJson('/calendar/events', [])->assertStatus(428)->assertJsonPath('profileUrl', '/profile');

        $this->patch('/profile', $this->details($user))->assertRedirect('/profile')->assertSessionHasNoErrors();
        $this->get('/saved-listings')->assertOk();
    }

    public function test_completion_rejects_missing_invalid_and_future_fields(): void
    {
        $user = User::factory()->create(['birth_date' => null, 'sex_assigned_at_birth' => null, 'city' => null, 'address' => null]);
        $this->actingAs($user);
        $this->patch('/profile', ['name' => $user->name, 'email' => $user->email])
            ->assertSessionHasErrors(['birth_date', 'sex_assigned_at_birth', 'city', 'address']);
        $this->patch('/profile', $this->details($user, ['birth_date' => '3000-01-01', 'sex_assigned_at_birth' => 'invalid']))
            ->assertSessionHasErrors(['birth_date', 'sex_assigned_at_birth']);
        $this->assertFalse($user->fresh()->hasCompleteProfile());
    }

    public function test_incomplete_api_profile_can_be_read_and_completed_before_other_features(): void
    {
        $user = User::factory()->create(['birth_date' => null, 'sex_assigned_at_birth' => null, 'city' => null, 'address' => null]);
        Sanctum::actingAs($user);
        $this->getJson('/api/v1/saved-listings')->assertStatus(428)->assertJsonPath('profileUrl', '/api/v1/profile');
        $this->getJson('/api/v1/profile')->assertOk()->assertJsonPath('data.birth_date', null);
        $this->patchJson('/api/v1/profile', $this->details($user))->assertOk()
            ->assertJsonPath('data.birth_date', '1990-01-01')
            ->assertJsonPath('data.sex_assigned_at_birth', 'prefer_not_to_say');
        $this->getJson('/api/v1/saved-listings')->assertOk();
    }

    public function test_private_details_are_not_shared_with_other_users_or_in_common_props(): void
    {
        $owner = User::factory()->create(['birth_date' => '1993-03-04', 'sex_assigned_at_birth' => 'female', 'address' => 'Private address']);
        $viewer = User::factory()->create();
        $this->assertArrayNotHasKey('birth_date', $owner->toArray());
        $this->assertArrayNotHasKey('sex_assigned_at_birth', $owner->toArray());
        $this->assertArrayNotHasKey('address', $owner->toArray());
        $this->actingAs($viewer)->withoutVite()->get('/profile')
            ->assertInertia(fn ($page) => $page->missing('auth.user.birth_date')->missing('auth.user.sex_assigned_at_birth')->missing('auth.user.address'));
        Sanctum::actingAs($viewer);
        $this->getJson('/api/users/'.$owner->id)->assertOk()
            ->assertJsonMissingPath('birth_date')->assertJsonMissingPath('sex_assigned_at_birth')->assertJsonMissingPath('address');
        $this->getJson('/api/v1/providers/'.$owner->id)->assertOk()
            ->assertJsonMissingPath('provider.birth_date')->assertJsonMissingPath('provider.sex_assigned_at_birth')->assertJsonMissingPath('provider.address');
    }
}
