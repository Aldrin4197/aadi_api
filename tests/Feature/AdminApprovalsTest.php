<?php

namespace Tests\Feature;

use App\Models\ProviderApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminApprovalsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->withoutVite();
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    private function application(string $name, string $service, string $status, int $daysAgo = 1): ProviderApplication
    {
        $field = ['spaces' => 'landlord_status', 'haul' => 'vehicle_owner_status', 'raketeer' => 'service_provider_status'][$service];
        $user = User::factory()->create(['name' => $name, $field => $status]);

        return ProviderApplication::create([
            'user_id' => $user->id, 'service' => $service, 'status' => $status, 'display_name' => $name.' Services', 'phone' => '09171234567',
            'area' => 'Catbalogan', 'description' => 'Application fixture for the approvals queue.', 'documents' => [], 'details' => ['applicantType' => 'individual'],
            'submitted_at' => now()->subDays($daysAgo), 'decided_at' => $status === 'pending' ? null : now(),
        ]);
    }

    private function submission(string $service): array
    {
        $types = array_keys(array_filter(config('provider_verification.'.$service), fn ($rule) => $rule['required']));

        return ['displayName' => 'Second try', 'phone' => '09171234567', 'area' => 'Catbalogan', 'description' => 'I corrected the documents that were rejected.',
            'details' => ['applicantType' => 'individual'], 'confirmed' => true,
            'documents' => array_map(fn ($type) => UploadedFile::fake()->image($type.'.jpg'), $types),
            'documentMeta' => array_map(fn ($type) => ['type' => $type], $types)];
    }

    public function test_queue_defaults_to_pending_and_filters_sorts_and_counts_every_status(): void
    {
        $this->application('Zoe', 'spaces', 'pending', 1);
        $this->application('Ana', 'haul', 'pending', 5);
        $this->application('Bea', 'spaces', 'approved');
        $this->application('Cy', 'raketeer', 'rejected');
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/approvals')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Marketplace/Approvals')
            ->where('counts', ['pending' => 2, 'approved' => 1, 'rejected' => 1, 'all' => 4])
            ->has('applications.data', 2)
            ->where('sort', 'submitted')->where('dir', 'asc')->where('applications.data.0.name', 'Ana')
            ->etc());
    }

    public function test_queue_status_service_search_and_name_sort(): void
    {
        $this->application('Zoe', 'spaces', 'pending', 1);
        $this->application('Ana', 'haul', 'pending', 5);
        $this->application('Bea', 'spaces', 'approved');
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/approvals?status=approved')->assertOk()
            ->assertInertia(fn (Assert $p) => $p->has('applications.data', 1)->where('applications.data.0.name', 'Bea'));
        $this->actingAs($admin)->get('/admin/approvals?status=all&service=spaces&sort=name&dir=desc')->assertOk()
            ->assertInertia(fn (Assert $p) => $p->has('applications.data', 2)->where('applications.data.0.name', 'Zoe'));
        $this->actingAs($admin)->get('/admin/approvals?status=all&q=ana')->assertOk()
            ->assertInertia(fn (Assert $p) => $p->has('applications.data', 1)->where('applications.data.0.name', 'Ana'));
        $this->actingAs($admin)->get('/admin/approvals?status=bogus')->assertSessionHasErrors('status');
        $this->actingAs(User::factory()->create())->get('/admin/approvals')->assertForbidden();
    }

    public function test_pending_legacy_requests_without_an_application_still_appear(): void
    {
        User::factory()->create(['name' => 'Legacy User', 'landlord_status' => 'pending']);

        $this->actingAs($this->admin())->get('/admin/approvals')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->has('applications.data', 1)->where('applications.data.0.name', 'Legacy User')->where('applications.data.0.application', null));
    }

    public function test_rejection_needs_a_real_reason_and_the_applicant_can_apply_again(): void
    {
        $application = $this->application('Dee', 'raketeer', 'pending');
        $user = $application->user;
        $admin = $this->admin();
        $url = '/admin/approvals/'.$user->id.'/raketeer';
        $version = $application->revision();

        $this->actingAs($admin)->post($url, ['status' => 'rejected', 'version' => $version, 'reviewNote' => 'too short'])->assertSessionHasErrors('reviewNote');
        $this->assertSame('pending', $application->fresh()->status);

        $this->post($url, ['status' => 'rejected', 'version' => $version, 'reviewNote' => 'The ID photo is blurry, please upload a clear copy.'])->assertRedirect();
        $this->assertSame('rejected', $application->fresh()->status);
        $this->assertSame('rejected', $user->fresh()->service_provider_status->value);

        $this->get('/admin/approvals?status=rejected')->assertInertia(fn (Assert $p) => $p
            ->has('applications.data', 1)->where('applications.data.0.application.reviewNote', 'The ID photo is blurry, please upload a clear copy.'));

        $this->actingAs($user->fresh())->post('/provider-applications/raketeer', [...$this->submission('raketeer'), 'version' => $application->fresh()->revision()])
            ->assertRedirect('/provider-applications/raketeer');
        $this->assertSame('pending', $application->fresh()->status);
        $this->assertSame('pending', $user->fresh()->service_provider_status->value);

        $this->actingAs($admin)->get('/admin/approvals')->assertInertia(fn (Assert $p) => $p
            ->has('applications.data', 1)->where('applications.data.0.userId', $user->id));
    }

    public function test_decisions_cannot_be_replayed_or_made_on_a_stale_version(): void
    {
        $application = $this->application('Eli', 'spaces', 'pending');
        $admin = $this->admin();
        $url = '/admin/approvals/'.$application->user_id.'/spaces';

        $this->actingAs($admin)->post($url, ['status' => 'rejected', 'version' => str_repeat('a', 64), 'reviewNote' => 'Stale review attempt note.'])->assertSessionHasErrors('application');
        $this->assertSame('pending', $application->fresh()->status);

        $this->post($url, ['status' => 'rejected', 'version' => $application->revision(), 'reviewNote' => 'Documents were not readable.'])->assertRedirect();
        $this->post($url, ['status' => 'approved', 'version' => $application->fresh()->revision()])->assertStatus(422);
        $this->assertSame('rejected', $application->fresh()->status);
    }
}
