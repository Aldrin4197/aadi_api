<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProfileLikesTest extends TestCase
{
    use RefreshDatabase;

    public function test_likes_are_public_reversible_and_only_one_per_account(): void
    {
        $this->withoutVite();
        $target = User::factory()->create();
        $liker = User::factory()->create(['name' => 'Aadi Member']);
        $url = "/users/{$target->id}/likes";
        $this->put($url)->assertRedirect('/login');
        $this->get("/providers/{$target->id}")->assertInertia(fn ($page) => $page->where('profileLikes.count', 0)->where('profileLikes.liked', false));
        $this->actingAs($liker)->put($url)->assertRedirect();
        $this->put($url)->assertRedirect();
        $this->assertDatabaseCount('profile_likes', 1);
        $this->get("/providers/{$target->id}")->assertInertia(fn ($page) => $page->where('profileLikes.count', 1)->where('profileLikes.liked', true));
        $this->getJson($url)->assertOk()->assertJsonPath('data.0.id', $liker->id)->assertJsonPath('data.0.name', 'Aadi Member')
            ->assertJsonMissingPath('data.0.email');
        $this->delete($url)->assertRedirect();
        $this->delete($url)->assertRedirect();
        $this->assertDatabaseCount('profile_likes', 0);
        $this->actingAs($target)->put($url)->assertForbidden();
        $this->delete($url)->assertForbidden();
    }

    public function test_liker_list_is_paginated_and_account_deletion_removes_likes(): void
    {
        $target = User::factory()->create();
        $people = User::factory()->count(21)->create();
        foreach ($people as $person) DB::table('profile_likes')->insert(['profile_id' => $target->id, 'user_id' => $person->id, 'created_at' => now()]);
        $url = "/users/{$target->id}/likes";
        $this->getJson($url)->assertOk()->assertJsonCount(20, 'data')->assertJsonPath('total', 21);
        $this->getJson($url.'?page=2')->assertOk()->assertJsonCount(1, 'data');
        $people->first()->delete();
        $this->assertDatabaseCount('profile_likes', 20);
        $target->delete();
        $this->assertDatabaseCount('profile_likes', 0);
    }

    public function test_admins_cannot_add_likes_but_can_remove_existing_likes_on_web_and_api(): void
    {
        $this->withoutVite();
        $target = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $webUrl = "/users/{$target->id}/likes";
        $apiUrl = "/api/v1/users/{$target->id}/likes";

        $this->actingAs($admin)->put($webUrl)->assertForbidden();
        $token = $admin->createToken('test')->plainTextToken;
        $this->withToken($token)->putJson($apiUrl)->assertForbidden();
        $this->assertDatabaseCount('profile_likes', 0);

        DB::table('profile_likes')->insert(['profile_id' => $target->id, 'user_id' => $admin->id, 'created_at' => now()]);
        $this->get("/providers/{$target->id}")->assertInertia(fn ($page) => $page
            ->where('auth.user.is_admin', true)->where('profileLikes.liked', true));
        $this->delete($webUrl)->assertRedirect();
        $this->assertDatabaseCount('profile_likes', 0);

        DB::table('profile_likes')->insert(['profile_id' => $target->id, 'user_id' => $admin->id, 'created_at' => now()]);
        $this->withToken($token)->deleteJson($apiUrl)->assertNoContent();
        $this->assertDatabaseCount('profile_likes', 0);
    }

    public function test_api_uses_same_likes_and_profile_viewer_state(): void
    {
        $target = User::factory()->create();
        $liker = User::factory()->create();
        $token = $liker->createToken('test')->plainTextToken;
        $url = "/api/v1/users/{$target->id}/likes";
        $this->putJson($url)->assertUnauthorized();
        $this->withToken($token)->putJson($url)->assertNoContent();
        $this->withToken($token)->getJson("/api/v1/providers/{$target->id}")->assertOk()
            ->assertJsonPath('profileLikes.count', 1)->assertJsonPath('profileLikes.liked', true);
        $this->getJson($url)->assertOk()->assertJsonPath('total', 1);
        $this->withToken($token)->deleteJson($url)->assertNoContent();
        $this->assertDatabaseCount('profile_likes', 0);
    }
}
