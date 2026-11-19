<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_admin_can_sign_in_and_access_dashboard(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@aadi.test')->sole();
        $this->assertTrue($admin->is_admin);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertFalse(User::where('email', 'test@example.com')->sole()->is_admin);

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($admin);
        $this->get('/admin/dashboard')->assertRedirect('/profile');
        $this->patch('/profile', [
            'name' => $admin->name, 'email' => $admin->email, 'birth_date' => '1990-01-01',
            'sex_assigned_at_birth' => 'prefer_not_to_say', 'city' => 'Catbalogan', 'address' => 'Private address',
        ])->assertSessionHasNoErrors();
        $this->withoutVite()->get('/admin/dashboard')->assertOk();
    }

    public function test_reseeding_repairs_admin_access_and_resets_demo_passwords(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@aadi.test')->sole();
        $admin->forceFill(['is_admin' => false, 'password' => 'ChangedPassword'])->save();
        User::where('email', 'test@example.com')->sole()->update(['password' => 'ChangedPassword']);

        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseCount('users', 2);
        $this->assertTrue($admin->fresh()->is_admin);
        foreach (User::all() as $user) {
            $this->assertTrue(Hash::check('password', $user->password));
        }
    }
}
