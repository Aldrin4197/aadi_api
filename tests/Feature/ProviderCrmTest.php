<?php

namespace Tests\Feature;

use App\Models\MarketplaceRequest;
use App\Models\ProviderRecord;
use App\Models\ProviderRecordArchive;
use App\Models\User;
use App\Services\AccountDeletionService;
use App\Services\Marketplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProviderCrmTest extends TestCase
{
    use RefreshDatabase;

    private function provider(): User
    {
        return User::factory()->create(['landlord_status' => 'approved', 'vehicle_owner_status' => 'approved', 'service_provider_status' => 'approved']);
    }

    private function listing(User $owner, string $service)
    {
        return app(Marketplace::class)->save($owner, $service, [
            'title' => 'Test listing', 'description' => 'Test description', 'type' => Marketplace::TYPES[$service][0],
            'price' => 1000, 'status' => 'active', 'photos' => [],
            'location' => ['lat' => 12, 'lng' => 124, 'address' => 'Calbayog'],
            'details' => ['brand' => 'Toyota', 'model' => 'Van', 'year' => 2024],
        ]);
    }

    public function test_accepted_requests_create_private_records_and_cancellation_updates_them(): void
    {
        $this->withoutVite();
        $provider = $this->provider();
        $customer = User::factory()->create();
        $other = $this->provider();
        foreach (['spaces', 'haul', 'raketeer'] as $service) {
            $listing = $this->listing($provider, $service);
            $payload = ['requestedAt' => $service === 'spaces' ? now()->addDays(3)->format('Y-m-d\TH:i') : now()->addDays(3)->toDateString(),
                'shareContact' => true];
            if ($service !== 'spaces') $payload['requestedUntil'] = now()->addDays(4)->toDateString();
            $this->actingAs($customer)->post("/offers/$service/{$listing->id}/requests", $payload)->assertSessionHasNoErrors();
            $request = MarketplaceRequest::where('listing_id', $listing->id)->firstOrFail();
            $this->actingAs($provider)->patch("/requests/{$request->id}", ['action' => 'accept', 'shareContact' => true])->assertSessionHasNoErrors();
            $record = ProviderRecord::where('request_id', $request->id)->firstOrFail();
            $this->assertSame(match ($service) { 'spaces' => 'viewing', 'haul' => 'rental', 'raketeer' => 'job' }, $record->kind);
            $this->patch("/provider/records/{$record->id}", ['status' => 'cancelled'])->assertSessionHasErrors('status');
            $this->assertSame('accepted', $request->fresh()->status);
            $this->actingAs($customer)->get('/provider/records')->assertForbidden();
            $this->actingAs($other)->patch("/provider/records/{$record->id}", ['status' => 'active'])->assertForbidden();
            $this->actingAs($customer)->patch("/requests/{$request->id}", ['action' => 'cancel'])->assertSessionHasNoErrors();
            $this->assertSame('cancelled', $record->fresh()->status);
        }
        $this->actingAs($provider)->get('/provider/records')->assertInertia(fn ($page) => $page->has('records.data', 3));
    }

    public function test_tenancy_creation_conversion_and_provider_only_partial_payments(): void
    {
        $this->withoutVite();
        $provider = $this->provider();
        $other = $this->provider();
        $customer = User::factory()->create();
        $listing = $this->listing($provider, 'spaces');
        $viewing = ProviderRecord::create([
            'provider_id' => $provider->id, 'customer_id' => $customer->id, 'service' => 'spaces',
            'listing_id' => $listing->id, 'listing_title' => $listing->title,
            'kind' => 'viewing', 'status' => 'scheduled', 'starts_on' => '2027-01-01',
        ]);
        $this->actingAs($provider)->post("/provider/records/{$viewing->id}/tenancy", ['start' => '2027-02-01', 'amount' => '1234.56'])->assertSessionHasNoErrors();
        $tenancy = ProviderRecord::where('source_viewing_id', $viewing->id)->firstOrFail();
        $this->assertSame(123456, $tenancy->agreed_cents);
        $this->post("/provider/records/{$viewing->id}/tenancy", ['start' => '2027-02-01'])->assertSessionHasErrors('record');
        $this->post("/provider/records/{$tenancy->id}/payments", ['amount' => '500.01', 'paid_on' => '2027-02-02'])->assertSessionHasErrors('period');
        $this->post("/provider/records/{$tenancy->id}/payments", ['amount' => '500.01', 'paid_on' => '2027-02-02', 'period' => '2027-02'])->assertSessionHasNoErrors();
        $this->post("/provider/records/{$tenancy->id}/payments", ['amount' => '0', 'paid_on' => '2027-02-02', 'period' => '2027-02'])->assertSessionHasErrors('amount');
        $payment = $tenancy->payments()->firstOrFail();
        $this->assertSame(50001, $payment->amount_cents);
        $this->actingAs($customer)->get('/provider/records')->assertForbidden();
        $this->actingAs($other)->patch("/provider/records/{$tenancy->id}/payments/{$payment->id}/void")->assertForbidden();
        $this->actingAs($provider)->patch("/provider/records/{$tenancy->id}/payments/{$payment->id}/void")->assertSessionHasNoErrors();
        $this->assertNotNull($payment->fresh()->voided_at);
        $this->patch("/provider/records/{$tenancy->id}/payments/{$payment->id}/void")->assertSessionHasErrors('payment');
        $this->post('/provider/records/tenancies', ['listing' => $listing->id, 'email' => $customer->email, 'start' => '2027-03-01', 'amount' => '1000'])->assertSessionHasNoErrors();
        $this->assertSame(2, ProviderRecord::where('kind', 'tenancy')->count());
        $this->patch("/provider/records/{$tenancy->id}", ['status' => 'active', 'start' => '2027-02-01', 'end' => '2027-01-01'])->assertSessionHasErrors('end');
        $this->actingAs($other)->post('/provider/records/tenancies', ['listing' => $listing->id, 'email' => $customer->email, 'start' => '2027-03-01'])->assertForbidden();
    }

    public function test_role_revocation_and_request_cancellation_are_enforced(): void
    {
        $provider = $this->provider();
        $customer = User::factory()->create();
        $listing = $this->listing($provider, 'haul');
        $record = ProviderRecord::create(['provider_id' => $provider->id, 'customer_id' => $customer->id, 'service' => 'haul',
            'listing_id' => $listing->id, 'listing_title' => $listing->title, 'kind' => 'rental', 'status' => 'scheduled', 'starts_on' => '2027-01-01']);
        $provider->forceFill(['vehicle_owner_status' => 'pending'])->save();
        $this->actingAs($provider)->patch("/provider/records/{$record->id}", ['status' => 'active'])->assertForbidden();
        $this->get('/provider/records')->assertInertia(fn ($page) => $page->has('records.data', 0));
    }

    public function test_existing_accepted_requests_are_imported_without_turning_viewings_into_tenancies(): void
    {
        $migration = require database_path('migrations/2026_11_19_000001_create_provider_records.php');
        $migration->down();
        $provider = $this->provider();
        $customer = User::factory()->create();
        foreach (['spaces', 'haul', 'raketeer'] as $service) {
            MarketplaceRequest::create([
                'service' => $service, 'listing_id' => (string) \Illuminate\Support\Str::ulid(),
                'listing_title' => 'Past listing', 'customer_id' => $customer->id, 'provider_id' => $provider->id,
                'requested_at' => '2027-01-01', 'requested_until' => $service === 'spaces' ? null : '2027-01-04', 'status' => 'accepted',
            ]);
        }
        $migration->up();
        $this->assertSame('viewing', ProviderRecord::where('service', 'spaces')->firstOrFail()->kind);
        $this->assertSame('rental', ProviderRecord::where('service', 'haul')->firstOrFail()->kind);
        $this->assertSame('job', ProviderRecord::where('service', 'raketeer')->firstOrFail()->kind);
        $this->assertSame(3, ProviderRecord::count());
    }

    public function test_current_filter_calendar_and_payment_totals_remain_private(): void
    {
        $this->withoutVite();
        $provider = $this->provider();
        $customer = User::factory()->create();
        $listing = $this->listing($provider, 'spaces');
        $this->actingAs($provider)->post('/provider/records/tenancies', [
            'listing' => $listing->id, 'email' => $customer->email,
            'start' => now()->subDay()->toDateString(), 'end' => now()->addDay()->toDateString(), 'amount' => '1500.50',
        ])->assertSessionHasNoErrors();
        $record = ProviderRecord::firstOrFail();
        $this->post("/provider/records/{$record->id}/payments", [
            'amount' => '400.25', 'paid_on' => today()->toDateString(), 'period' => today()->format('Y-m'),
        ])->assertSessionHasNoErrors();
        $this->get('/provider/records?status=current')->assertInertia(fn ($page) => $page
            ->where('counts.current', 1)->where('counts.recordedCents', 40025)
            ->where('records.data.0.agreedCents', 150050)->where('records.data.0.payments.0.amountCents', 40025));
        $this->get('/calendar?month='.today()->format('Y-m'))->assertInertia(fn ($page) => $page
            ->where('events', fn ($events) => collect($events)->contains(fn ($event) =>
                $event['kind'] === 'tenancy' && $event['date'] === today()->toDateString())));
        $this->actingAs($customer)->get('/calendar?month='.today()->format('Y-m'))->assertInertia(fn ($page) => $page
            ->where('events', fn ($events) => collect($events)->doesntContain(fn ($event) => $event['kind'] === 'tenancy')));
        $customer->delete();
        $this->assertDatabaseMissing('provider_records', ['id' => $record->id]);
        $this->assertSame(0, DB::table('provider_payments')->count());
    }

    public function test_customer_deletion_retains_private_read_only_history_until_expiry(): void
    {
        $this->withoutVite();
        $this->travelTo(now()->startOfSecond());
        $provider = $this->provider();
        $other = $this->provider();
        $customer = User::factory()->create(['name' => 'Retained Customer']);
        $listing = $this->listing($provider, 'spaces');
        $record = ProviderRecord::create([
            'provider_id' => $provider->id, 'customer_id' => $customer->id, 'service' => 'spaces',
            'listing_id' => $listing->id, 'listing_title' => $listing->title,
            'kind' => 'tenancy', 'status' => 'active', 'starts_on' => today()->toDateString(), 'agreed_cents' => 150050,
        ]);
        $record->payments()->create(['amount_cents' => 40025, 'paid_on' => today(), 'period' => '2027-01', 'note' => 'Paid in cash']);
        $voided = $record->payments()->create(['amount_cents' => 100, 'paid_on' => today(), 'period' => '2027-01']);
        $voided->forceFill(['voided_at' => now()])->save();
        $deletion = app(AccountDeletionService::class);
        $deletion->schedule($customer, 'privacy', null, 'user');
        $this->assertSame(0, ProviderRecordArchive::count());
        $deletion->cancel($customer);
        $this->artisan('aadi:process-account-deletions')->assertSuccessful();
        $this->assertSame(0, ProviderRecordArchive::count());
        $deletion->schedule($customer->fresh(), 'privacy', null, 'user');
        $this->travel(30)->days();
        $this->artisan('aadi:process-account-deletions')->assertSuccessful();
        $archive = ProviderRecordArchive::firstOrFail();
        $deadline = $archive->retain_until->copy();
        $this->assertTrue($deadline->equalTo(now()->addMonthsNoOverflow(6)));
        $this->assertDatabaseMissing('provider_records', ['id' => $record->id]);
        $this->actingAs($provider)->get('/provider/records')->assertHeader('Cache-Control', 'no-store, private')
            ->assertInertia(fn ($page) => $page->where('counts.current', 0)->where('counts.recordedCents', 0)
                ->where('archives.data.0.customerName', 'Retained Customer')->where('archives.data.0.agreedCents', 150050)
                ->where('archives.data.0.payments.0.amountCents', 40025)->where('archives.data.0.payments.0.note', 'Paid in cash')
                ->where('archives.data.0.payments.1.voided', true));
        $this->patch("/provider/records/{$record->id}", ['status' => 'active'])->assertNotFound();
        $this->actingAs($other)->get('/provider/records')->assertInertia(fn ($page) => $page->has('archives.data', 0));
        $provider->forceFill(['landlord_status' => 'pending'])->save();
        $this->actingAs($provider->fresh())->get('/provider/records')->assertInertia(fn ($page) => $page->has('archives.data', 0));
        $provider->forceFill(['landlord_status' => 'approved'])->save();
        $this->artisan('aadi:process-account-deletions')->assertSuccessful();
        $this->assertDatabaseHas('provider_record_archives', ['id' => $archive->id]);
        $this->travelTo($deadline);
        $this->actingAs($provider->fresh())->get('/provider/records')->assertInertia(fn ($page) => $page->has('archives.data', 0));
        $this->assertDatabaseHas('provider_record_archives', ['id' => $archive->id]);
        $this->artisan('aadi:process-account-deletions')->assertSuccessful();
        $this->assertDatabaseMissing('provider_record_archives', ['id' => $archive->id]);
    }

    public function test_provider_deletion_and_later_customer_deletion_do_not_extend_history(): void
    {
        $this->travelTo(now()->startOfSecond());
        $admin = User::factory()->create(['is_admin' => true]);
        $provider = $this->provider();
        $customer = User::factory()->create(['name' => 'Named Customer']);
        $listing = $this->listing($provider, 'haul');
        $record = ProviderRecord::create([
            'provider_id' => $provider->id, 'customer_id' => $customer->id, 'service' => 'haul',
            'listing_id' => $listing->id, 'listing_title' => $listing->title,
            'kind' => 'rental', 'status' => 'active', 'starts_on' => today()->toDateString(),
        ]);
        $record->payments()->create(['amount_cents' => 500, 'paid_on' => today()]);
        $this->actingAs($admin)->patch("/admin/users/{$provider->id}/deletion", [
            'action' => 'delete_now', 'reason' => 'admin_other', 'confirm' => $provider->email,
        ])->assertSessionHasNoErrors();
        $archive = ProviderRecordArchive::firstOrFail();
        $this->assertNull($archive->provider_id);
        $this->assertSame('Named Customer', $archive->data['customerName']);
        $this->assertSame(500, $archive->data['payments'][0]['amountCents']);
        $deadline = $archive->retain_until->copy();
        $this->travel(1)->month();
        app(AccountDeletionService::class)->purge($customer);
        $this->assertSame(1, ProviderRecordArchive::count());
        $this->assertTrue($archive->fresh()->retain_until->equalTo($deadline));
    }

    public function test_later_provider_deletion_removes_access_without_extending_customer_archive(): void
    {
        $this->travelTo(now()->startOfSecond());
        $provider = $this->provider();
        $customer = User::factory()->create();
        $listing = $this->listing($provider, 'haul');
        ProviderRecord::create([
            'provider_id' => $provider->id, 'customer_id' => $customer->id, 'service' => 'haul',
            'listing_id' => $listing->id, 'listing_title' => $listing->title,
            'kind' => 'rental', 'status' => 'active', 'starts_on' => today()->toDateString(),
        ]);
        $deletion = app(AccountDeletionService::class);
        $deletion->purge($customer);
        $archive = ProviderRecordArchive::firstOrFail();
        $deadline = $archive->retain_until->copy();
        $this->travel(1)->month();
        $deletion->purge($provider);
        $this->assertSame(1, ProviderRecordArchive::count());
        $this->assertNull($archive->fresh()->provider_id);
        $this->assertTrue($archive->fresh()->retain_until->equalTo($deadline));
    }

    public function test_retention_deadline_does_not_overflow_shorter_months(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2028-08-31 10:00:00'));
        $provider = $this->provider();
        $customer = User::factory()->create();
        $listing = $this->listing($provider, 'haul');
        ProviderRecord::create([
            'provider_id' => $provider->id, 'customer_id' => $customer->id, 'service' => 'haul',
            'listing_id' => $listing->id, 'listing_title' => $listing->title,
            'kind' => 'rental', 'status' => 'active', 'starts_on' => today()->toDateString(),
        ]);
        app(AccountDeletionService::class)->purge($customer);
        $this->assertSame('2029-02-28 10:00:00', ProviderRecordArchive::firstOrFail()->retain_until->format('Y-m-d H:i:s'));
    }
}
