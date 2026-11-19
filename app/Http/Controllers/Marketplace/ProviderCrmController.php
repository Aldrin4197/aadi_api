<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\ProviderRecord;
use App\Models\ProviderRecordArchive;
use App\Models\User;
use App\Services\Marketplace;
use App\Services\ProviderRecords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ProviderCrmController extends Controller
{
    private function record(Request $request, ProviderRecord $record, Marketplace $market): void
    {
        abort_unless((string) $record->provider_id === (string) $request->user()->id
            && $market->approved($request->user(), $record->service), 403);
    }

    public function index(Request $request, Marketplace $market)
    {
        $user = $request->user();
        $services = array_values(array_filter(array_keys(Marketplace::TYPES), fn ($service) => $market->approved($user, $service)));
        $filters = $request->validate([
            'service' => ['nullable', Rule::in(array_keys(Marketplace::TYPES))],
            'status' => ['nullable', Rule::in(['current', 'scheduled', 'active', 'completed', 'cancelled'])],
            'listing' => ['nullable', 'string', 'max:26'],
        ]);
        $query = ProviderRecord::where('provider_id', $user->id)->whereIn('service', $services);
        $counts = [
            'viewings' => (clone $query)->where('kind', 'viewing')->where('status', 'scheduled')->count(),
            'current' => (clone $query)->whereIn('kind', ['tenancy', 'rental', 'job'])
                ->whereIn('status', ['scheduled', 'active'])->whereDate('starts_on', '<=', today())
                ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', today()))->count(),
            'upcoming' => (clone $query)->whereIn('status', ['scheduled', 'active'])->whereDate('starts_on', '>', today())->count(),
            'recordedCents' => (int) DB::table('provider_payments')
                ->join('provider_records', 'provider_records.id', '=', 'provider_payments.provider_record_id')
                ->where('provider_records.provider_id', $user->id)->whereIn('provider_records.service', $services)
                ->whereNull('provider_payments.voided_at')->sum('provider_payments.amount_cents'),
        ];
        $records = $query->withExists('tenancy')->with(['customer:id,name', 'payments' => fn ($q) => $q->orderByDesc('paid_on')])
            ->when($filters['service'] ?? null, fn ($q, $value) => $q->where('service', $value))
            ->when($filters['status'] ?? null, function ($q, $value) {
                if ($value === 'current') {
                    $q->whereIn('kind', ['tenancy', 'rental', 'job'])->whereIn('status', ['scheduled', 'active'])
                        ->whereDate('starts_on', '<=', today())
                        ->where(fn ($dates) => $dates->whereNull('ends_on')->orWhereDate('ends_on', '>=', today()));
                } else {
                    $q->where('status', $value);
                }
            })
            ->when($filters['listing'] ?? null, fn ($q, $value) => $q->where('listing_id', $value))
            ->orderByDesc('starts_on')->orderByDesc('id')->paginate(20)->withQueryString()
            ->through(fn ($record) => [
                'id' => $record->id, 'service' => $record->service, 'listingId' => $record->listing_id,
                'listingTitle' => $record->listing_title, 'customerName' => $record->customer->name,
                'kind' => $record->kind, 'status' => $record->status, 'start' => $record->starts_on->toDateString(),
                'end' => $record->ends_on?->toDateString(), 'agreedCents' => $record->agreed_cents,
                'converted' => $record->tenancy_exists,
                'payments' => $record->payments->map(fn ($payment) => [
                    'id' => $payment->id, 'amountCents' => $payment->amount_cents,
                    'paidOn' => $payment->paid_on->toDateString(), 'period' => $payment->period,
                    'note' => $payment->note, 'voided' => $payment->voided_at !== null,
                ]),
            ]);
        $properties = in_array('spaces', $services, true)
            ? $market->model('spaces')::where('owner_id', $user->id)->where('category', 'space_rental')->orderBy('title')->get(['id', 'title'])
            : collect();

        $archives = ProviderRecordArchive::where('provider_id', $user->id)->whereIn('service', $services)
            ->where('retain_until', '>', now())->orderByDesc('created_at')
            ->paginate(20, ['*'], 'archive_page')->withQueryString()
            ->through(fn ($archive) => [
                'id' => $archive->id, 'service' => $archive->service, ...$archive->data,
                'retainUntil' => $archive->retain_until->format('Y-m-d H:i T'),
            ]);

        return Inertia::render('Provider/Records', compact('records', 'counts', 'filters', 'properties', 'archives'))
            ->toResponse($request)->header('Cache-Control', 'private, no-store');
    }

    private function tenancyData(Request $request): array
    {
        return $request->validate([
            'start' => ['required', 'date_format:Y-m-d'],
            'end' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start'],
            'amount' => ['nullable', 'regex:/^\d{1,8}(\.\d{1,2})?$/'],
        ]);
    }

    public function createTenancy(Request $request, Marketplace $market)
    {
        $data = $this->tenancyData($request) + $request->validate([
            'listing' => ['required', 'string'], 'email' => ['required', 'email', 'max:255'],
        ]);
        abort_unless($market->approved($request->user(), 'spaces'), 403);
        $listing = $market->find('spaces', $data['listing']);
        $market->authorize($request->user(), $listing, 'spaces');
        abort_unless($listing->category?->value === 'space_rental' || $listing->category === 'space_rental', 404);
        $customer = User::where('email', $data['email'])->whereKeyNot($request->user()->id)->first();
        if (!$customer) throw ValidationException::withMessages(['email' => 'Choose a different registered Aadi account.']);
        ProviderRecord::create([
            'provider_id' => $request->user()->id, 'customer_id' => $customer->id, 'service' => 'spaces',
            'listing_id' => $listing->id, 'listing_title' => $listing->title, 'kind' => 'tenancy',
            'status' => 'active', 'starts_on' => $data['start'], 'ends_on' => $data['end'] ?? null,
            'agreed_cents' => isset($data['amount']) ? ProviderRecords::cents($data['amount']) : null,
        ]);
        return back()->with('success', 'Tenancy recorded.');
    }

    public function convert(Request $request, ProviderRecord $record, Marketplace $market)
    {
        $this->record($request, $record, $market);
        $data = $this->tenancyData($request);
        DB::transaction(function () use ($record, $data) {
            $viewing = ProviderRecord::whereKey($record->id)->lockForUpdate()->firstOrFail();
            if ($viewing->kind !== 'viewing' || $viewing->status === 'cancelled'
                || ProviderRecord::where('source_viewing_id', $viewing->id)->exists()) {
                throw ValidationException::withMessages(['record' => 'This viewing cannot be converted.']);
            }
            ProviderRecord::create([
                'provider_id' => $viewing->provider_id, 'customer_id' => $viewing->customer_id,
                'source_viewing_id' => $viewing->id, 'service' => 'spaces', 'listing_id' => $viewing->listing_id,
                'listing_title' => $viewing->listing_title, 'kind' => 'tenancy', 'status' => 'active',
                'starts_on' => $data['start'], 'ends_on' => $data['end'] ?? null,
                'agreed_cents' => isset($data['amount']) ? ProviderRecords::cents($data['amount']) : null,
            ]);
        });
        return back()->with('success', 'Tenancy recorded.');
    }

    public function update(Request $request, ProviderRecord $record, Marketplace $market)
    {
        $this->record($request, $record, $market);
        $data = $request->validate([
            'status' => ['required', Rule::in(['scheduled', 'active', 'completed', 'cancelled'])],
            'amount' => ['nullable', 'regex:/^\d{1,8}(\.\d{1,2})?$/'],
            'start' => [$record->kind === 'tenancy' ? 'required' : 'prohibited', 'date_format:Y-m-d'],
            'end' => [$record->kind === 'tenancy' ? 'nullable' : 'prohibited', 'date_format:Y-m-d', 'after_or_equal:start'],
        ]);
        DB::transaction(function () use ($record, $data) {
            $record = ProviderRecord::whereKey($record->id)->lockForUpdate()->firstOrFail();
            if ($record->status === 'cancelled' && $data['status'] !== 'cancelled') {
                throw ValidationException::withMessages(['status' => 'Cancelled records cannot be reopened.']);
            }
            if ($record->request_id && $data['status'] === 'cancelled' && $record->status !== 'cancelled') {
                throw ValidationException::withMessages(['status' => 'Cancel this request from the Requests page so both parties are notified.']);
            }
            $changes = ['status' => $data['status'], 'agreed_cents' => isset($data['amount']) ? ProviderRecords::cents($data['amount']) : null];
            if ($record->kind === 'tenancy') {
                $changes['starts_on'] = $data['start'];
                $changes['ends_on'] = $data['end'] ?? null;
            }
            $record->update($changes);
        });
        return back()->with('success', 'Record updated.');
    }

    public function payment(Request $request, ProviderRecord $record, Marketplace $market)
    {
        $this->record($request, $record, $market);
        abort_if($record->kind === 'viewing', 422, 'Viewings do not have payments.');
        $data = $request->validate([
            'amount' => ['required', 'regex:/^\d{1,8}(\.\d{1,2})?$/'],
            'paid_on' => ['required', 'date_format:Y-m-d'],
            'period' => [$record->kind === 'tenancy' ? 'required' : 'nullable', 'string', 'max:60'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $cents = ProviderRecords::cents($data['amount']);
        if ($cents <= 0) throw ValidationException::withMessages(['amount' => 'Payment must be greater than zero.']);
        DB::transaction(function () use ($record, $cents, $data) {
            $record = ProviderRecord::whereKey($record->id)->lockForUpdate()->firstOrFail();
            $record->payments()->create(['amount_cents' => $cents, 'paid_on' => $data['paid_on'], 'period' => $data['period'] ?? null, 'note' => $data['note'] ?? null]);
        });
        return back()->with('success', 'Payment recorded.');
    }

    public function voidPayment(Request $request, ProviderRecord $record, string $payment, Marketplace $market)
    {
        $this->record($request, $record, $market);
        DB::transaction(function () use ($record, $payment) {
            $record = ProviderRecord::whereKey($record->id)->lockForUpdate()->firstOrFail();
            $entry = $record->payments()->whereKey($payment)->firstOrFail();
            if ($entry->voided_at !== null) throw ValidationException::withMessages(['payment' => 'Payment is already voided.']);
            $entry->forceFill(['voided_at' => now()])->save();
        });
        return back()->with('success', 'Payment voided.');
    }
}
