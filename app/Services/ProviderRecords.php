<?php

namespace App\Services;

use App\Models\MarketplaceRequest;
use App\Models\ProviderRecord;

class ProviderRecords
{
    public function accepted(MarketplaceRequest $request): void
    {
        ProviderRecord::firstOrCreate(['request_id' => $request->id], [
            'provider_id' => $request->provider_id, 'customer_id' => $request->customer_id,
            'service' => $request->service, 'listing_id' => $request->listing_id, 'listing_title' => $request->listing_title,
            'kind' => match ($request->service) { 'spaces' => 'viewing', 'haul' => 'rental', 'raketeer' => 'job' },
            'status' => 'scheduled', 'starts_on' => $request->requested_at->toDateString(),
            'ends_on' => $request->requested_until?->toDateString(),
        ]);
    }

    public function cancelled(MarketplaceRequest $request): void
    {
        ProviderRecord::where('request_id', $request->id)->whereIn('status', ['scheduled', 'active'])->update(['status' => 'cancelled']);
    }

    public static function cents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }
}
