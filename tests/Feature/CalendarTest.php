<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\MarketplaceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_is_private_and_contains_only_accepted_requests_for_participants(): void
    {
        $this->withoutVite();
        $customer = User::factory()->create();
        $provider = User::factory()->create();
        $stranger = User::factory()->create();
        $request = MarketplaceRequest::create([
            'service' => 'spaces', 'listing_id' => (string) \Illuminate\Support\Str::ulid(),
            'listing_title' => 'Viewing', 'customer_id' => $customer->id, 'provider_id' => $provider->id,
            'requested_at' => '2030-02-15 14:30', 'status' => 'accepted',
        ]);
        MarketplaceRequest::create([
            'service' => 'haul', 'listing_id' => (string) \Illuminate\Support\Str::ulid(),
            'listing_title' => 'Pending', 'customer_id' => $customer->id, 'provider_id' => $provider->id,
            'requested_at' => '2030-02-16 00:00', 'status' => 'pending',
        ]);
        $event = new CalendarEvent(['title' => 'Private', 'event_date' => '2030-02-15']);
        $event->user_id = $customer->id;
        $event->save();
        $this->get('/calendar')->assertRedirect('/login');
        $this->actingAs($customer)->get('/calendar?month=2030-02')->assertInertia(fn (Assert $page) => $page
            ->component('Account/Calendar')->has('events', 2)
            ->where('events.0.title', 'Private')->where('events.1.title', 'Viewing')
            ->where('events.1.time', '14:30')->where('events.1.isProvider', false));
        $this->actingAs($provider)->get('/calendar?month=2030-02')->assertInertia(fn (Assert $page) => $page
            ->has('events', 1)->where('events.0.isProvider', true));
        $this->actingAs($stranger)->get('/calendar?month=2030-02')->assertInertia(fn (Assert $page) => $page->has('events', 0));
        $request->update(['status' => 'cancelled']);
        $this->actingAs($customer)->get('/calendar?month=2030-02')->assertInertia(fn (Assert $page) => $page->has('events', 1));
        $this->get('/calendar?month=2030-03')->assertInertia(fn (Assert $page) => $page->has('events', 0));
    }

    public function test_personal_event_validation_and_ownership(): void
    {
        $this->withoutVite();
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $data = ['title' => 'Reminder', 'event_date' => '2030-02-15', 'event_time' => '09:15', 'note' => 'Personal'];
        $this->actingAs($owner)->post('/calendar/events', [...$data, 'event_date' => '2030-02-30'])->assertSessionHasErrors('event_date');
        $this->post('/calendar/events', [...$data, 'event_time' => '25:00'])->assertSessionHasErrors('event_time');
        $this->post('/calendar/events', $data)->assertRedirect('/calendar?month=2030-02&date=2030-02-15');
        $event = CalendarEvent::firstOrFail();
        $this->assertSame((string) $owner->id, (string) $event->user_id);
        $this->actingAs($stranger)->patch('/calendar/events/'.$event->id, $data)->assertForbidden();
        $this->delete('/calendar/events/'.$event->id)->assertForbidden();
        $this->actingAs($owner)->patch('/calendar/events/'.$event->id, [...$data, 'event_date' => '2030-03-01', 'event_time' => null])
            ->assertRedirect('/calendar?month=2030-03&date=2030-03-01');
        $this->assertSame('2030-03-01', $event->fresh()->event_date->format('Y-m-d'));
        $this->delete('/calendar/events/'.$event->id)->assertRedirect();
        $this->assertDatabaseMissing('calendar_events', ['id' => $event->id]);
    }
}
