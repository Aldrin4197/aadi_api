<?php

namespace App\Services;

use App\Models\{Listing, ServiceListing, User, VehicleListing};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ListingBadgeService
{
    /**
     * @param iterable<Model> $records
     * @return array<string, array<string, bool>>
     */
    public function forRecords(iterable $records, string $service, ?User $viewer = null): array
    {
        $records = collect($records)->values();
        if ($records->isEmpty()) return [];

        $model = match ($service) {
            'spaces' => Listing::class,
            'haul' => VehicleListing::class,
            'raketeer' => ServiceListing::class,
            default => abort(404),
        };
        $space = $service === 'spaces';
        $activeStatus = $space ? 'active' : 'available';
        $ids = $records->map(fn (Model $record) => (string) $record->getKey())->all();

        $topRatedIds = DB::table('marketplace_reviews')
            ->where('service', $service)
            ->whereIn('listing_id', $ids)
            ->select('listing_id')
            ->groupBy('listing_id')
            ->havingRaw('COUNT(*) >= 5 AND AVG(rating) >= 4.5')
            ->pluck('listing_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $activeListings = $model::query()->where('status', $activeStatus);
        if ($space) $activeListings->where('category', 'space_rental');
        $activeCount = (clone $activeListings)->count();
        $topPopularCount = max(1, (int) ceil($activeCount * 0.1));
        $viewCounts = DB::table($space ? 'listing_views' : 'marketplace_listing_views')
            ->where('viewed_at', '>=', now()->subDays(30))
            ->when(!$space, fn ($query) => $query->where('service', $service))
            ->whereIn('listing_id', (clone $activeListings)->select('id'))
            ->selectRaw('listing_id, COUNT(*) AS views')
            ->groupBy('listing_id')
            ->orderByDesc('views')
            ->limit($topPopularCount)
            ->get();
        $popularIds = $viewCounts
            ->filter(fn ($row) => (int) $row->views >= 20)
            ->map(fn ($row) => (string) $row->listing_id)
            ->all();

        $featuredQuery = $model::query()->where('status', $activeStatus);
        if ($space) $featuredQuery->where('category', 'space_rental');
        $featuredIds = $featuredQuery->latest()->orderByDesc('id')->limit(2)
            ->pluck('id')->map(fn ($id) => (string) $id)->all();

        $topRated = array_flip($topRatedIds);
        $popular = array_flip($popularIds);
        $featured = array_flip($featuredIds);

        $badges = [];
        foreach ($records as $record) {
            $id = (string) $record->getKey();
            $status = $record->status instanceof \BackedEnum ? $record->status->value : $record->status;
            $owner = $service === 'raketeer' ? $record->provider : $record->owner;
            $isAvailable = $status === $activeStatus
                && !(bool) $record->availability_paused
                && !($owner && $owner->isRestricted());

            $badges[$id] = [
                'featured' => isset($featured[$id]),
                'top-rated' => isset($topRated[$id]),
                'new' => $status === $activeStatus
                    && ($record->published_at?->greaterThanOrEqualTo(now()->subDays(30)) ?? false),
                'popular' => isset($popular[$id]),
                'available-now' => $isAvailable,
                'recommended' => $isAvailable && $this->matchesPreferences($record, $service, $viewer),
            ];
        }

        return $badges;
    }

    private function matchesPreferences(Model $record, string $service, ?User $viewer): bool
    {
        if (!$viewer || $viewer->onboarding_status !== 'completed') return false;
        if ((string) ($service === 'raketeer' ? $record->provider_id : $record->owner_id) === (string) $viewer->id) return false;

        $preferences = $viewer->onboarding_data ?? [];
        $interests = $preferences['interests'] ?? [];
        if (!is_array($interests) || !in_array($service, $interests, true)) return false;

        $space = $service === 'spaces';
        $vehicle = $service === 'haul';
        $price = (float) ($space ? $record->price : ($vehicle ? $record->price_per_day : $record->price_amount));
        $unit = $space ? 'month' : ($vehicle ? 'day' : ($record->price_type === 'hourly' ? 'hour' : 'project'));
        $budgetUnit = $preferences['budget_unit'] ?? null;
        if ($budgetUnit && $budgetUnit !== $unit) return false;

        if (isset($preferences['budget_min']) && $price < (float) $preferences['budget_min']) return false;
        if (isset($preferences['budget_max']) && $price > (float) $preferences['budget_max']) return false;

        $city = trim((string) ($preferences['city'] ?? ''));
        if ($city !== '') {
            $listingCity = $space ? (string) $record->city : (string) ($vehicle ? $record->location : $record->service_area);
            if ($space) {
                if (mb_strtolower(trim($listingCity)) !== mb_strtolower($city)) return false;
            } elseif (!preg_match('/(?<![\pL\pN])'.preg_quote($city, '/').'(?![\pL\pN])/iu', $listingCity)) {
                return false;
            }
        }

        return true;
    }
}
