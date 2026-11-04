<?php

namespace Tests\Feature;

use App\Models\ProviderApplication;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Marketplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Mail::fake();
        $this->withoutVite();
    }

    private function admin(string $name = 'Admin One'): User
    {
        $admin = User::factory()->create(['name' => $name]);
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    private function titles(User $user): array
    {
        return $user->notifications()->latest()->get()->pluck('data.title')->all();
    }

    public function test_the_inbox_requires_a_login_and_only_shows_your_own_notifications(): void
    {
        $this->getJson('/notifications')->assertUnauthorized();
        $this->getJson('/notifications/count')->assertUnauthorized();

        $me = User::factory()->create();
        $other = User::factory()->create();
        $me->notify(new \App\Notifications\AppNotification('info', 'For me', 'Hello', '/dashboard'));
        $other->notify(new \App\Notifications\AppNotification('info', 'For someone else'));

        $this->actingAs($me)->getJson('/notifications')->assertOk()
            ->assertJsonPath('unread', 1)->assertJsonCount(1, 'items')->assertJsonPath('items.0.title', 'For me')
            ->assertJsonPath('items.0.href', '/dashboard')->assertJsonPath('items.0.read', false);
    }

    public function test_notifications_can_be_marked_read_one_by_one_or_all_at_once_but_never_for_someone_else(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        foreach (['One', 'Two', 'Three'] as $title) $me->notify(new \App\Notifications\AppNotification('info', $title));
        $other->notify(new \App\Notifications\AppNotification('info', 'Theirs'));
        $mine = $me->notifications()->first();
        $theirs = $other->notifications()->first();

        $this->actingAs($me)->postJson('/notifications/'.$theirs->id.'/read')->assertNotFound();
        $this->assertNull($theirs->fresh()->read_at);

        $this->postJson('/notifications/'.$mine->id.'/read')->assertOk()->assertJsonPath('unread', 2);
        $this->postJson('/notifications/read-all')->assertOk()->assertJsonPath('unread', 0);
        $this->assertNull($theirs->fresh()->read_at);
        $this->getJson('/notifications/count')->assertJsonPath('unread', 0);
    }

    public function test_the_unread_count_is_shared_with_every_page_for_signed_in_users(): void
    {
        $user = User::factory()->create();
        $user->notify(new \App\Notifications\AppNotification('info', 'Hi'));

        $this->actingAs($user)->get('/explore')->assertInertia(fn (Assert $p) => $p->where('notifications.unread', 1));
        $this->actingAs($this->admin())->get('/admin/dashboard')->assertInertia(fn (Assert $p) => $p->where('notifications.unread', 0));
    }

    public function test_submitting_an_application_notifies_every_admin_and_a_draft_does_not(): void
    {
        $adminA = $this->admin('Admin A');
        $adminB = $this->admin('Admin B');
        $user = User::factory()->create(['name' => 'Maria Santos']);
        $types = array_keys(array_filter(config('provider_verification.spaces'), fn ($rule) => $rule['required']));
        $payload = ['displayName' => 'Santos Rentals', 'phone' => '09171234567', 'area' => 'Catbalogan', 'description' => 'Two boarding houses near the university.',
            'details' => ['applicantType' => 'individual'], 'confirmed' => true,
            'documents' => array_map(fn ($type) => UploadedFile::fake()->image($type.'.jpg'), $types),
            'documentMeta' => array_map(fn ($type) => ['type' => $type], $types)];

        $this->actingAs($user)->post('/provider-applications/spaces', [...$payload, 'action' => 'draft'])->assertRedirect();
        $this->assertSame([], $this->titles($adminA));

        $version = ProviderApplication::first()?->revision();
        $this->post('/provider-applications/spaces', [...$payload, 'version' => $version])->assertRedirect();
        $this->assertSame(['New provider application'], $this->titles($adminA));
        $this->assertSame(['New provider application'], $this->titles($adminB));
        $this->assertStringContainsString('Maria Santos', $adminA->notifications()->first()->data['text']);
        $this->assertSame('/admin/approvals', $adminA->notifications()->first()->data['href']);
        $this->assertSame([], $this->titles($user));
    }

    public function test_an_approval_or_rejection_notifies_only_the_applicant(): void
    {
        $admin = $this->admin();
        $approved = User::factory()->create(['landlord_status' => 'pending']);
        $rejected = User::factory()->create(['service_provider_status' => 'pending']);
        $docs = [['path' => 'provider-documents/x/id.jpg', 'name' => 'id.jpg', 'type' => 'identity']];
        Storage::disk('local')->put('provider-documents/x/id.jpg', 'x');
        $app = fn ($user, $service, $documents) => ProviderApplication::create(['user_id' => $user->id, 'service' => $service, 'status' => 'pending', 'display_name' => 'N', 'phone' => '0917', 'area' => 'A', 'description' => str_repeat('d', 30), 'documents' => $documents, 'details' => [], 'submitted_at' => now()]);
        $propertyDocs = array_map(fn ($type) => ['path' => 'provider-documents/x/id.jpg', 'name' => $type.'.jpg', 'type' => $type], array_keys(array_filter(config('provider_verification.spaces'), fn ($r) => $r['required'])));
        $a = $app($approved, 'spaces', $propertyDocs);
        $b = $app($rejected, 'raketeer', $docs);

        $this->actingAs($admin)->post('/admin/approvals/'.$approved->id.'/spaces', ['status' => 'approved', 'version' => $a->revision()])->assertRedirect();
        $this->post('/admin/approvals/'.$rejected->id.'/raketeer', ['status' => 'rejected', 'version' => $b->revision(), 'reviewNote' => 'The ID photo is blurry, please upload a clear copy.'])->assertRedirect();

        $this->assertSame(["You're approved for Aadi Properties"], $this->titles($approved));
        $this->assertSame('good', $approved->notifications()->first()->data['tone']);
        $this->assertSame(['Aadi Raket application needs an update'], $this->titles($rejected));
        $this->assertStringContainsString('blurry', $rejected->notifications()->first()->data['text']);
        $this->assertSame('/provider-applications/raketeer', $rejected->notifications()->first()->data['href']);
        $this->assertSame([], $this->titles($admin));
    }

    public function test_new_tickets_notify_admins_and_replies_notify_the_member_who_wrote_in(): void
    {
        $admin = $this->admin();
        $member = User::factory()->create(['name' => 'Ana Cruz']);
        $payload = ['name' => 'Ana Cruz', 'email' => 'ana@example.com', 'category' => 'account', 'subject' => 'I cannot log in', 'message' => 'The login page says my details are wrong after a reset.'];

        $this->post('/support/tickets', [...$payload, 'subject' => 'A guest question', 'email' => 'guest@example.com'])->assertSessionHasNoErrors();
        $this->actingAs($member)->post('/support/tickets', $payload)->assertSessionHasNoErrors();
        $this->post('/support/tickets', $payload);
        $this->assertSame(2, $admin->notifications()->count());
        $this->assertSame('New support ticket', $admin->notifications()->first()->data['title']);

        $guest = SupportTicket::where('subject', 'A guest question')->firstOrFail();
        $ticket = SupportTicket::where('subject', 'I cannot log in')->firstOrFail();

        $this->actingAs($admin)->postJson('/admin/tickets/'.$guest->id.'/messages', ['kind' => 'reply', 'body' => 'Hello, we are looking into it.'])->assertOk();
        $this->postJson('/admin/tickets/'.$ticket->id.'/messages', ['kind' => 'note', 'body' => 'Internal only note.'])->assertOk();
        $this->assertSame([], $this->titles($member));

        $this->postJson('/admin/tickets/'.$ticket->id.'/messages', ['kind' => 'reply', 'body' => 'Please try the Forgot password link.'])->assertOk();
        $this->patchJson('/admin/tickets/'.$ticket->id, ['status' => 'resolved'])->assertOk();
        $this->patchJson('/admin/tickets/'.$ticket->id, ['priority' => 'high'])->assertOk();

        $this->assertEqualsCanonicalizing(['Your support request is resolved', 'Aadi support replied'], $this->titles($member));
        $this->assertNull($member->notifications()->first()->data['href']);
    }

    public function test_a_new_review_notifies_the_listing_owner_once_and_edits_stay_quiet(): void
    {
        $owner = User::factory()->create(['landlord_status' => 'approved']);
        $reviewer = User::factory()->create();
        $listing = app(Marketplace::class)->save($owner, 'spaces', [
            'title' => 'Sunny studio', 'description' => 'Review fixture.', 'type' => 'bedspace', 'price' => 1500, 'status' => 'active', 'photos' => [],
            'location' => ['lat' => 11.77, 'lng' => 124.88, 'address' => 'Catbalogan', 'city' => 'Catbalogan'],
        ]);
        $url = '/offers/spaces/'.$listing->id.'/review';

        $this->actingAs($reviewer)->put($url, ['rating' => 5, 'body' => 'Wonderful place and a very helpful host.'])->assertRedirect();
        $this->put($url, ['rating' => 4, 'body' => 'Still wonderful, updated my review.'])->assertRedirect();

        $this->assertSame(['New review on your listing'], $this->titles($owner));
        $notification = $owner->notifications()->first()->data;
        $this->assertStringContainsString('Sunny studio', $notification['text']);
        $this->assertStringContainsString('★★★★★', $notification['text']);
        $this->assertSame('/offers/spaces/'.$listing->id, $notification['href']);
        $this->assertSame([], $this->titles($reviewer));
    }
}
