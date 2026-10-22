<?php

namespace Tests\Feature;

use App\Models\{Listing, ServiceListing, User, VehicleListing};
use Database\Seeders\MarketplaceUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\TestCase;

class MarketplaceUsersSeederTest extends TestCase
{
    use RefreshDatabase;

    private function samples(): void
    {
        $this->seed(MarketplaceUsersSeeder::class);
    }

    public function test_each_user_owns_three_public_samples_and_has_a_usable_non_admin_account(): void
    {
        $this->samples();
        $this->assertDatabaseCount('users', 7);
        foreach (['listings', 'vehicle_listings', 'service_listings'] as $table) $this->assertDatabaseCount($table, 7);
        foreach (User::all() as $user) {
            $this->assertFalse($user->is_admin);
            $this->assertNotNull($user->email_verified_at);
            $this->assertTrue(Hash::check('password', $user->password));
            $this->assertSame(3, $user->listings()->count() + $user->vehicleListings()->count() + $user->serviceListings()->count());
            $this->assertMatchesRegularExpression('/^[0-7][0-9A-HJKMNP-TV-Z]{25}$/', $user->id);
        }
        foreach (['spaces', 'haul', 'raketeer'] as $service) {
            $response = $this->getJson('/api/v1/offers?service='.$service)->assertOk()->assertJsonPath('meta.total', 7);
            foreach ($response->json('data') as $item) {
                $this->assertStringStartsWith('[Sample]', $item['title']);
                $this->assertStringStartsWith('[Demo]', $item['ownerName']);
                $this->assertNotNull($item['location']['lat']);
                $this->assertNotNull($item['location']['lng']);
            }
        }
        $alex = User::where('email', 'alex@users.aadi.example')->sole();
        $this->assertSame(1, $alex->listings()->count());
        $this->assertSame(1, $alex->vehicleListings()->count());
        $this->assertSame(1, $alex->serviceListings()->count());
    }

    public function test_reseeding_resets_passwords_but_keeps_edits_roles_and_soft_deletions(): void
    {
        $this->samples();
        $maria = User::where('email', 'maria@users.aadi.example')->sole();
        $maria->update(['name'=>'Edited demo owner', 'password'=>'ChangedPassword2026!', 'landlord_status'=>'rejected']);
        $space = $maria->listings()->firstOrFail();
        $space->update(['title'=>'Edited property', 'price'=>1234]);
        $vehicle = VehicleListing::firstOrFail(); $vehicle->delete();
        $service = ServiceListing::firstOrFail(); $service->delete();
        $this->samples();
        $this->assertDatabaseCount('users', 7);
        foreach (['listings', 'vehicle_listings', 'service_listings'] as $table) $this->assertDatabaseCount($table, 7);
        $this->assertTrue(Hash::check('password', $maria->fresh()->password));
        $this->assertSame('Edited demo owner', $maria->fresh()->name);
        $this->assertSame('rejected', $maria->fresh()->landlord_status->value);
        $this->assertSame('Edited property', $space->fresh()->title);
        $this->assertEquals(1234, $space->fresh()->price);
        $this->assertSoftDeleted($vehicle);
        $this->assertSoftDeleted($service);
    }

    public function test_demo_login_sees_only_its_own_workspace_and_cannot_edit_another_users_listing(): void
    {
        $this->samples();
        $maria = User::where('email', 'maria@users.aadi.example')->sole();
        $this->post('/login', ['email'=>$maria->email, 'password'=>'password'])->assertRedirect();
        $this->assertAuthenticatedAs($maria);
        $this->withoutVite()->get('/my-listings')->assertInertia(fn (Assert $page) => $page
            ->has('listings', 3)->where('listings', fn ($items) => collect($items)->every(fn ($item) => $item['ownerId'] === $maria->id)));
        $foreign = Listing::where('owner_id', '!=', $maria->id)->firstOrFail();
        $this->get('/my-listings/spaces/'.$foreign->id.'/edit')->assertForbidden();
        $this->get('/admin/dashboard')->assertForbidden();
    }

    public function test_conflicting_email_rolls_back_without_adopting_an_existing_account(): void
    {
        $existing = User::factory()->create(['email'=>'ana@users.aadi.example']);
        try {
            $this->samples();
            $this->fail('Expected an email collision to stop the seeder.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Demo email already belongs', $exception->getMessage());
        }
        $this->assertDatabaseCount('users', 1);
        foreach (['listings', 'vehicle_listings', 'service_listings'] as $table) $this->assertDatabaseCount($table, 0);
        $this->assertSame($existing->id, User::sole()->id);
    }
}
