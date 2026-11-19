<?php

namespace Tests\Feature;

use App\Models\AccountDeletion;
use App\Models\User;
use App\Services\AccountDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountDeletionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_reason_is_required(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->delete('/profile', ['password' => 'password'])->assertSessionHasErrors('reason');
        $this->assertNull($user->fresh()->deletion_scheduled_for);
    }

    public function test_scheduled_user_is_blocked_and_purged_after_grace_then_record_expires(): void
    {
        $user = User::factory()->create();
        app(AccountDeletionService::class)->schedule($user, 'privacy', 'note', 'user');

        $this->actingAs($user->fresh())->get('/settings')->assertRedirect('/login');

        $this->artisan('aadi:process-account-deletions')->assertSuccessful();
        $this->assertNotNull($user->fresh());

        $this->travel(31)->days();
        $this->artisan('aadi:process-account-deletions')->assertSuccessful();
        $this->assertNull($user->fresh());
        $this->assertDatabaseHas('account_deletions', ['reason' => 'privacy', 'status' => 'completed', 'user_id' => null]);

        $this->travel(7)->months();
        $this->artisan('aadi:process-account-deletions')->assertSuccessful();
        $this->assertSame(0, AccountDeletion::count());
    }

    public function test_admin_can_schedule_cancel_and_delete_now(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $when = now()->addDays(5)->toDateTimeString();

        $this->actingAs($admin)->patch("/admin/users/{$user->id}/deletion", ['action' => 'schedule', 'reason' => 'admin_fraud', 'when' => $when])->assertSessionHasNoErrors();
        $this->assertNotNull($user->fresh()->deletion_scheduled_for);
        $this->assertDatabaseHas('account_deletions', ['user_id' => $user->id, 'source' => 'admin', 'reason' => 'admin_fraud']);

        $this->actingAs($admin)->patch("/admin/users/{$user->id}/deletion", ['action' => 'cancel'])->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->deletion_scheduled_for);

        $this->actingAs($admin)->patch("/admin/users/{$user->id}/deletion", ['action' => 'delete_now', 'reason' => 'admin_spam', 'confirm' => 'wrong'])->assertSessionHasErrors('confirm');
        $this->actingAs($admin)->patch("/admin/users/{$user->id}/deletion", ['action' => 'delete_now', 'reason' => 'admin_spam', 'confirm' => $user->email])->assertSessionHasNoErrors();
        $this->assertNull($user->fresh());
        $this->assertDatabaseHas('account_deletions', ['reason' => 'admin_spam', 'status' => 'completed']);
    }

    public function test_non_admin_cannot_use_admin_deletion_and_admins_are_protected(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user)->patch("/admin/users/{$other->id}/deletion", ['action' => 'cancel'])->assertForbidden();

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->patch("/admin/users/{$admin->id}/deletion", ['action' => 'delete_now', 'reason' => 'admin_other', 'confirm' => $admin->email])->assertForbidden();
    }
}
