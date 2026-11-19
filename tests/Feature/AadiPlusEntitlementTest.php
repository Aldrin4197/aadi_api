<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AadiPlusEntitlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_grant_or_remove_aadi_plus_and_public_profiles_expose_active_state(): void
    {
        config(['aadi_plus.enabled' => true]);
        $this->withoutVite();
        $user = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $url = "/admin/users/{$user->id}/aadi-plus";

        $this->actingAs($user)->patch($url, ['aadi_plus_until' => today()->addDays(30)->toDateString()])
            ->assertForbidden();

        $this->actingAs($admin)->patch($url, ['aadi_plus_until' => today()->addDays(30)->toDateString()])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->hasAadiPlus());
        $this->getJson("/api/v1/providers/{$user->id}")
            ->assertOk()->assertJsonPath('provider.aadiPlus', true);

        $this->patch($url, ['aadi_plus_until' => null])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->hasAadiPlus());
        $this->getJson("/api/v1/providers/{$user->id}")
            ->assertOk()->assertJsonPath('provider.aadiPlus', false);
    }

    public function test_admin_cannot_set_an_expired_aadi_plus_entitlement(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $this->actingAs($admin)->patch("/admin/users/{$user->id}/aadi-plus", [
            'aadi_plus_until' => today()->subDay()->toDateString(),
        ])->assertSessionHasErrors('aadi_plus_until');
    }
}
