<?php

namespace Tests\Feature;

use App\Mail\TicketReply;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SupportTicketsTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return [...[
            'name' => 'Maria Santos', 'email' => 'maria@example.com', 'category' => 'account',
            'subject' => 'I cannot log in', 'message' => 'I reset my password but the login page still says my details are wrong.',
        ], ...$overrides];
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['name' => 'Support Admin']);
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    private function ticket(array $attributes = []): SupportTicket
    {
        return SupportTicket::create([...[
            'name' => 'Ana Cruz', 'email' => 'ana@example.com', 'category' => 'bug', 'subject' => 'Map does not load',
            'message' => 'The map stays blank on my phone when I open the home page.', 'status' => 'open', 'priority' => 'normal',
        ], ...$attributes]);
    }

    public function test_a_guest_can_submit_a_ticket_from_the_home_page_and_gets_a_reference(): void
    {
        $this->withoutVite();

        $response = $this->from('/')->post('/support/tickets', $this->payload())->assertRedirect('/');
        $ticket = SupportTicket::firstOrFail();
        $response->assertSessionHas('ticket', $ticket->reference);
        $this->assertMatchesRegularExpression('/^AAD-[A-Z2-9]{6}$/', $ticket->reference);
        $this->assertNull($ticket->user_id);
        $this->assertSame('open', $ticket->status);
        $this->assertSame('normal', $ticket->priority);
    }

    public function test_signed_in_users_are_linked_and_reports_are_high_priority(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/support/tickets', $this->payload(['category' => 'report']))->assertSessionHasNoErrors();

        $ticket = SupportTicket::firstOrFail();
        $this->assertSame($user->id, $ticket->user_id);
        $this->assertSame('high', $ticket->priority);
    }

    public function test_submission_is_validated_and_bots_and_duplicates_are_ignored(): void
    {
        $this->post('/support/tickets', $this->payload(['message' => 'short']))->assertSessionHasErrors('message');
        $this->post('/support/tickets', $this->payload(['category' => 'nonsense']))->assertSessionHasErrors('category');
        $this->post('/support/tickets', $this->payload(['email' => 'not-an-email']))->assertSessionHasErrors('email');
        $this->post('/support/tickets', $this->payload(['website' => 'http://spam.example']))->assertSessionHasErrors('website');
        $this->assertDatabaseCount('support_tickets', 0);

        $this->post('/support/tickets', $this->payload());
        $this->post('/support/tickets', $this->payload());
        $this->assertDatabaseCount('support_tickets', 1);
    }

    public function test_only_admins_can_use_the_ticket_desk(): void
    {
        $this->withoutVite();
        $ticket = $this->ticket();

        $this->get('/admin/tickets')->assertRedirect();
        $this->actingAs(User::factory()->create());
        $this->get('/admin/tickets')->assertForbidden();
        $this->getJson('/admin/tickets/'.$ticket->id)->assertForbidden();
        $this->patchJson('/admin/tickets/'.$ticket->id, ['status' => 'closed'])->assertForbidden();
        $this->postJson('/admin/tickets/'.$ticket->id.'/messages', ['kind' => 'reply', 'body' => 'Hello there'])->assertForbidden();
    }

    public function test_tickets_filter_search_and_sort(): void
    {
        $this->withoutVite();
        $this->ticket(['subject' => 'Alpha problem', 'priority' => 'low']);
        $this->ticket(['subject' => 'Bravo problem', 'priority' => 'urgent', 'category' => 'report', 'email' => 'bravo@example.com']);
        $this->ticket(['subject' => 'Charlie problem', 'status' => 'resolved', 'resolved_at' => now()]);
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/tickets')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Admin/Tickets/Index')
            ->has('tickets.data', 2)->where('counts.active', 2)->where('counts.resolved', 1)->where('counts.all', 3));

        $this->actingAs($admin)->get('/admin/tickets?status=all&sort=priority&dir=desc')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->has('tickets.data', 3)->where('tickets.data.0.subject', 'Bravo problem'));
        $this->actingAs($admin)->get('/admin/tickets?status=all&sort=priority&dir=asc')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->where('tickets.data.0.subject', 'Alpha problem'));
        $this->actingAs($admin)->get('/admin/tickets?status=all&category=report')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->has('tickets.data', 1)->where('tickets.data.0.subject', 'Bravo problem'));
        $this->actingAs($admin)->get('/admin/tickets?status=all&q=bravo@')->assertOk()->assertInertia(fn (Assert $p) => $p->has('tickets.data', 1));
        $this->actingAs($admin)->get('/admin/tickets?status=resolved')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->has('tickets.data', 1)->where('tickets.data.0.subject', 'Charlie problem'));
        $this->actingAs($admin)->get('/admin/tickets?status=bogus')->assertSessionHasErrors('status');
        $this->actingAs($admin)->get('/admin/tickets?sort=evil&dir=sideways')->assertOk()->assertInertia(fn (Assert $p) => $p->where('sort', 'created')->where('dir', 'desc'));
    }

    public function test_status_priority_and_assignment_changes_are_logged_and_stamp_resolution(): void
    {
        $ticket = $this->ticket();
        $admin = $this->admin();

        $this->actingAs($admin)->patchJson('/admin/tickets/'.$ticket->id, ['status' => 'resolved', 'priority' => 'high', 'assignee' => 'me'])
            ->assertOk()->assertJsonPath('ticket.status', 'resolved')->assertJsonPath('ticket.assignee', 'Support Admin');

        $ticket->refresh();
        $this->assertNotNull($ticket->resolved_at);
        $this->assertSame('high', $ticket->priority);
        $this->assertSame(3, $ticket->messages()->where('kind', 'event')->count());

        $this->patchJson('/admin/tickets/'.$ticket->id, ['status' => 'open'])->assertOk();
        $this->assertNull($ticket->fresh()->resolved_at);
        $this->patchJson('/admin/tickets/'.$ticket->id, ['status' => 'bogus'])->assertUnprocessable();
    }

    public function test_replies_are_emailed_and_set_the_first_response_while_notes_stay_internal(): void
    {
        Mail::fake();
        $ticket = $this->ticket();
        $admin = $this->admin();

        $this->actingAs($admin)->postJson('/admin/tickets/'.$ticket->id.'/messages', ['kind' => 'note', 'body' => 'Checking logs first.'])->assertOk();
        Mail::assertNothingSent();
        $this->assertNull($ticket->fresh()->first_response_at);

        $this->postJson('/admin/tickets/'.$ticket->id.'/messages', ['kind' => 'reply', 'body' => 'Please try clearing your cache.'])
            ->assertOk()->assertJsonPath('ticket.status', 'in_progress');
        Mail::assertSent(TicketReply::class, fn ($mail) => $mail->hasTo('ana@example.com'));
        $this->assertNotNull($ticket->fresh()->first_response_at);
        $this->assertTrue($ticket->messages()->where('kind', 'reply')->firstOrFail()->emailed);

        $this->postJson('/admin/tickets/'.$ticket->id.'/messages', ['kind' => 'reply', 'body' => 'x'])->assertUnprocessable();
        $this->postJson('/admin/tickets/'.$ticket->id.'/messages', ['kind' => 'event', 'body' => 'Sneaky audit entry'])->assertUnprocessable();
    }

    public function test_analytics_report_volume_backlog_and_response_times(): void
    {
        $this->withoutVite();
        $this->ticket(['subject' => 'Unanswered', 'created_at' => now()->subDays(2)]);
        $answered = $this->ticket(['subject' => 'Answered', 'created_at' => now()->subHours(10)]);
        $answered->forceFill(['first_response_at' => now()->subHours(8), 'status' => 'resolved', 'resolved_at' => now()->subHours(2)])->save();

        $this->actingAs($this->admin())->get('/admin/tickets?view=insights&range=7')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->where('view', 'insights')
            ->where('tickets', null)
            ->where('analytics.range.days', 7)
            ->has('analytics.trend.dates', 7)
            ->where('analytics.kpis.0.value', 2)
            ->where('analytics.kpis.1.value', 1)
            ->where('analytics.kpis.2.value', 2)
            ->where('analytics.kpis.3.value', 8)
            ->where('analytics.insights', fn ($items) => collect($items)->contains(fn ($i) => str_contains($i['title'], 'no reply yet'))));
    }

    public function test_the_ticket_list_no_longer_carries_the_insights(): void
    {
        $this->withoutVite();
        $this->ticket();

        $this->actingAs($this->admin())->get('/admin/tickets')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->where('view', 'tickets')->where('analytics', null)->has('tickets.data', 1));
    }

    public function test_a_ticket_can_be_assigned_to_another_admin_who_is_notified(): void
    {
        $ticket = $this->ticket();
        $admin = $this->admin();
        $colleague = User::factory()->create(['name' => 'Second Admin']);
        $colleague->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)->patchJson('/admin/tickets/'.$ticket->id, ['assignee' => $colleague->id])
            ->assertOk()->assertJsonPath('ticket.assignee', 'Second Admin')->assertJsonPath('ticket.assignedToId', $colleague->id);

        $this->assertSame($colleague->id, $ticket->fresh()->assigned_to);
        $this->assertStringContainsString('Assigned to Second Admin by Support Admin.', $ticket->messages()->where('kind', 'event')->value('body'));
        $this->assertSame(1, $colleague->unreadNotifications()->count());
        $this->assertSame('ticket-assigned', $colleague->unreadNotifications()->first()->data['kind']);

        $this->patchJson('/admin/tickets/'.$ticket->id, ['assignee' => $colleague->id])->assertOk();
        $this->assertSame(1, $colleague->unreadNotifications()->count(), 'Re-selecting the same person does not notify twice.');
        $this->assertSame(0, $admin->unreadNotifications()->count());

        $this->patchJson('/admin/tickets/'.$ticket->id, ['assignee' => 'none'])->assertOk()->assertJsonPath('ticket.assignee', null);
    }

    public function test_tickets_cannot_be_assigned_to_non_admins_or_unknown_users(): void
    {
        $ticket = $this->ticket();
        $member = User::factory()->create();

        $this->actingAs($this->admin())->patchJson('/admin/tickets/'.$ticket->id, ['assignee' => $member->id])->assertUnprocessable();
        $this->patchJson('/admin/tickets/'.$ticket->id, ['assignee' => '01ZZZZZZZZZZZZZZZZZZZZZZZZ'])->assertUnprocessable();
        $this->assertNull($ticket->fresh()->assigned_to);
    }

    public function test_the_desk_lists_admins_and_filters_by_a_specific_assignee(): void
    {
        $this->withoutVite();
        $admin = $this->admin();
        $colleague = User::factory()->create(['name' => 'Second Admin']);
        $colleague->forceFill(['is_admin' => true])->save();
        $this->ticket(['subject' => 'Theirs', 'assigned_to' => $colleague->id]);
        $this->ticket(['subject' => 'Nobody']);

        $this->actingAs($admin)->get('/admin/tickets?assigned='.$colleague->id)->assertOk()->assertInertia(fn (Assert $p) => $p
            ->has('tickets.data', 1)->where('tickets.data.0.subject', 'Theirs')
            ->where('options.staff', fn ($staff) => collect($staff)->pluck('name')->sort()->values()->all() === ['Second Admin', 'Support Admin']));
        $this->get('/admin/tickets?assigned='.User::factory()->create()->id)->assertSessionHasErrors('assigned');
    }

    public function test_the_ticket_table_paginates_with_a_whitelisted_page_size(): void
    {
        $this->withoutVite();
        foreach (range(1, 12) as $i) $this->ticket(['subject' => "Ticket $i"]);
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/tickets?per_page=10')->assertInertia(fn (Assert $p) => $p
            ->has('tickets.data', 10)->where('tickets.per_page', 10)->where('tickets.total', 12)->where('tickets.last_page', 2));
        $this->get('/admin/tickets?per_page=10&page=2')->assertInertia(fn (Assert $p) => $p->has('tickets.data', 2));
        $this->get('/admin/tickets?per_page=100000')->assertInertia(fn (Assert $p) => $p->where('tickets.per_page', 15)->has('tickets.data', 12));
    }

    public function test_dashboard_surfaces_open_tickets(): void
    {
        $this->withoutVite();
        $this->ticket();

        $this->actingAs($this->admin())->get('/admin/dashboard')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->where('overview.kpis', fn ($kpis) => collect($kpis)->contains(fn ($k) => $k['key'] === 'tickets' && $k['value'] === 1)));
    }
}
