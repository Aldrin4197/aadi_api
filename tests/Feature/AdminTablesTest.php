<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Marketplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminTablesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create(['name' => 'Zed Admin']);
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    private function listing(User $owner, string $service, string $title, float $price, string $status = 'active'): void
    {
        app(Marketplace::class)->save($owner, $service, [
            'title' => $title, 'description' => 'Table fixture.', 'type' => Marketplace::TYPES[$service][0],
            'price' => $price, 'status' => $status, 'photos' => [],
            'location' => ['lat' => 11.77, 'lng' => 124.88, 'address' => 'Catbalogan', 'city' => 'Catbalogan'],
            'details' => ['brand' => 'Toyota', 'model' => 'Hiace', 'year' => 2024],
        ]);
    }

    public function test_users_can_be_filtered_by_role_status_and_sorted_by_listings(): void
    {
        $this->withoutVite();
        $busy = User::factory()->create(['name' => 'Busy Landlord', 'landlord_status' => 'approved']);
        $quiet = User::factory()->create(['name' => 'Quiet Applicant', 'landlord_status' => 'pending']);
        $this->listing($busy, 'spaces', 'First', 1000);
        $this->listing($busy, 'spaces', 'Second', 2000);
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/users?role=spaces&roleStatus=pending')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->has('users.data', 1)->where('users.data.0.name', 'Quiet Applicant'));

        $this->actingAs($admin)->get('/admin/users?role=spaces&roleStatus=approved')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->has('users.data', 1)->where('users.data.0.id', $busy->id));

        $this->actingAs($admin)->get('/admin/users?listings=with')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->has('users.data', 1)->where('users.data.0.id', $busy->id));

        $this->actingAs($admin)->get('/admin/users?listings=without&q=Quiet')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->has('users.data', 1)->where('users.data.0.id', $quiet->id));

        $this->actingAs($admin)->get('/admin/users?sort=listings&dir=desc')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->where('sort', 'listings')->where('dir', 'desc')->where('users.data.0.id', $busy->id));

        $this->actingAs($admin)->get('/admin/users?sort=name&dir=asc')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->where('users.data.0.name', 'Busy Landlord'));
    }

    public function test_user_filters_reject_unknown_values_and_ignore_unsafe_sorts(): void
    {
        $this->withoutVite();
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/users?role=nonsense')->assertSessionHasErrors('role');
        $this->actingAs($admin)->get('/admin/users?sort=password;drop&dir=sideways')->assertOk()
            ->assertInertia(fn (Assert $p) => $p->where('sort', 'joined')->where('dir', 'desc'));
        $this->actingAs(User::factory()->create())->get('/admin/users')->assertForbidden();
    }

    public function test_marketplace_tables_filter_and_sort_for_every_category(): void
    {
        $this->withoutVite();
        $owner = User::factory()->create(['name' => 'Alpha Owner', 'landlord_status' => 'approved', 'vehicle_owner_status' => 'approved', 'service_provider_status' => 'approved']);
        $admin = $this->admin();

        foreach (['spaces' => 'listings', 'haul' => 'vehicles', 'raketeer' => 'services'] as $service => $path) {
            $this->listing($owner, $service, 'Cheap '.$service, 500);
            $this->listing($owner, $service, 'Pricey '.$service, 9000, 'draft');

            $this->actingAs($admin)->get("/admin/$path?sort=price&dir=desc")->assertOk()->assertInertia(fn (Assert $p) => $p
                ->where('sort', 'price')->where('listings.data.0.title', 'Pricey '.$service));

            $this->actingAs($admin)->get("/admin/$path?sort=price&dir=asc")->assertOk()->assertInertia(fn (Assert $p) => $p
                ->where('listings.data.0.title', 'Cheap '.$service));

            $this->actingAs($admin)->get("/admin/$path?maxPrice=1000")->assertOk()->assertInertia(fn (Assert $p) => $p
                ->has('listings.data', 1)->where('listings.data.0.title', 'Cheap '.$service)->where('totals.all', 2)->where('totals.live', 1));

            $this->actingAs($admin)->get("/admin/$path?q=Alpha")->assertOk()->assertInertia(fn (Assert $p) => $p->has('listings.data', 2));
            $this->actingAs($admin)->get("/admin/$path?q=nobody-matches")->assertOk()->assertInertia(fn (Assert $p) => $p->has('listings.data', 0));
            $this->actingAs($admin)->get("/admin/$path?sort=owner&dir=asc")->assertOk();
            $this->actingAs($admin)->get("/admin/$path?provider=unapproved")->assertOk()->assertInertia(fn (Assert $p) => $p->has('listings.data', 0));
            $this->actingAs($admin)->get("/admin/$path?status=bogus")->assertSessionHasErrors('status');
        }
    }

    public function test_search_treats_percent_and_underscore_literally(): void
    {
        $this->withoutVite();
        $owner = User::factory()->create(['landlord_status' => 'approved']);
        $this->listing($owner, 'spaces', 'Plain studio', 100);

        $this->actingAs($this->admin())->get('/admin/listings?q=%25')->assertOk()
            ->assertInertia(fn (Assert $p) => $p->has('listings.data', 0));
    }
}
