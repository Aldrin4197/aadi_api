<?php
namespace Tests\Feature;
use App\Models\{User, ProviderApplication};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmailVerificationGateTest extends TestCase {
    use RefreshDatabase;
    protected function setUp(): void { parent::setUp(); Storage::fake('local'); $this->withoutVite(); }
    private function payload(string $role = 'spaces'): array {
        $types = array_keys(array_filter(config('provider_verification.'.$role), fn ($rule) => $rule['required']));
        return ['displayName'=>'Neighborhood services', 'phone'=>'09171234567', 'area'=>'Catbalogan',
            'description'=>'I offer repairs and property services nearby.', 'details'=>['applicantType'=>'individual'],
            'documents'=>array_map(fn ($type) => UploadedFile::fake()->image($type.'.jpg'), $types),
            'documentMeta'=>array_map(fn ($type) => ['type'=>$type], $types), 'confirmed'=>true];
    }

    public function test_inertia_shares_whether_the_email_is_verified(): void {
        $this->actingAs(User::factory()->unverified()->create())->get('/become-provider')
            ->assertInertia(fn ($page) => $page->where('auth.user.emailVerified', false));
        $this->actingAs(User::factory()->create())->get('/become-provider')
            ->assertInertia(fn ($page) => $page->where('auth.user.emailVerified', true));
    }

    public function test_unverified_user_cannot_submit_or_draft_an_application(): void {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user)->from('/provider-applications/spaces')->post('/provider-applications/spaces', $this->payload())
            ->assertRedirect('/provider-applications/spaces')->assertSessionHasErrors('application');
        $this->post('/provider-applications/spaces', [...$this->payload(), 'action'=>'draft'])->assertSessionHasErrors('application');
        $this->assertDatabaseCount('provider_applications', 0);
        $this->assertSame('none', $user->fresh()->landlord_status->value);
    }

    public function test_unverified_user_can_still_read_the_requirements(): void {
        $this->actingAs(User::factory()->unverified()->create())->get('/provider-applications/spaces')->assertOk();
    }

    public function test_api_returns_a_clear_error_for_unverified_users(): void {
        $this->actingAs(User::factory()->unverified()->create(), 'sanctum');
        $this->postJson('/api/v1/provider-applications/spaces', $this->payload())->assertForbidden()->assertJsonPath('code', 'email_unverified');
        $this->postJson('/api/me/landlord-application')->assertForbidden()->assertJsonPath('code', 'email_unverified');
    }

    public function test_verifying_the_email_unlocks_applications(): void {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user)->post('/provider-applications/spaces', $this->payload())->assertSessionHasErrors('application');
        $user->markEmailAsVerified();
        $this->actingAs($user->fresh())->post('/provider-applications/spaces', $this->payload())->assertSessionHasNoErrors();
        $this->assertSame(1, ProviderApplication::count());
    }

    public function test_resend_outcome_is_shared_for_the_banner(): void {
        $this->actingAs(User::factory()->unverified()->create())->post('/email/verification-notification')->assertRedirect();
        $this->get('/become-provider')->assertInertia(fn ($page) => $page->where('flash.verification', 'verification-link-sent'));
    }
}
