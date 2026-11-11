<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PostLoginFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitors_see_the_landing_page_but_signed_in_users_go_straight_to_the_map(): void
    {
        $this->withoutVite();
        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Marketplace/Explore')->has('landing'));

        $user = User::factory()->create();
        $this->actingAs($user)->get('/')->assertRedirect('/explore');
        $this->actingAs($user)->get('/?service=haul&q=van')->assertRedirect('/explore?q=van&service=haul');
        $this->actingAs($user)->get('/explore')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Marketplace/Explore')->missing('landing'));
    }

    public function test_logging_in_opens_the_map_unless_a_destination_was_requested(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['email'=>$user->email, 'password'=>'password'])->assertRedirect('/explore');
        $this->post('/logout');

        $this->get('/saved-listings')->assertRedirect('/login');
        $this->post('/login', ['email'=>$user->email, 'password'=>'password'])->assertRedirect(url('/saved-listings'));
    }

    public function test_logging_out_returns_to_the_public_landing_page(): void
    {
        $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect('/');
        $this->withoutVite()->get('/')->assertOk();
    }

    public function test_everyone_keeps_the_map_saved_listings_profile_and_provider_application_pages(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        foreach (['/explore', '/saved-listings', '/profile', '/settings', '/become-provider'] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }

    public function test_dashboard_is_only_for_approved_providers(): void
    {
        $this->withoutVite();
        foreach ([[], ['landlord_status'=>'pending'], ['vehicle_owner_status'=>'rejected', 'service_provider_status'=>'pending']] as $statuses) {
            $this->actingAs(User::factory()->create($statuses))->get('/dashboard')->assertRedirect('/explore');
        }

        foreach (['landlord_status', 'vehicle_owner_status', 'service_provider_status'] as $role) {
            $this->actingAs(User::factory()->create([$role=>'approved']))->get('/dashboard')->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component('Account/Dashboard'));
        }
    }

    public function test_provider_status_is_shared_with_the_frontend_for_role_based_navigation(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create())->get('/explore')
            ->assertInertia(fn (Assert $page) => $page->where('auth.isProvider', false));
        $this->actingAs(User::factory()->create(['landlord_status'=>'approved']))->get('/explore')
            ->assertInertia(fn (Assert $page) => $page->where('auth.isProvider', true));
        $this->actingAs(User::factory()->create(['service_provider_status'=>'pending']))->get('/explore')
            ->assertInertia(fn (Assert $page) => $page->where('auth.isProvider', false));
        $this->withoutVite()->get('/explore')->assertInertia(fn (Assert $page) => $page->where('auth.isProvider', false));
    }
}
