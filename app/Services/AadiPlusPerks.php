<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-controlled Aadi Plus perks. A perk only applies to members while it is enabled; a disabled
 * perk gives members the free-tier behaviour. A null limit means unlimited.
 */
class AadiPlusPerks
{
    public const DEFINITIONS = [
        'listing_photos' => ['label' => 'More listing photos', 'audience' => 'provider', 'type' => 'limit', 'unit' => 'photos per listing', 'free' => 6, 'plus' => 12, 'max' => 12,
            'description' => 'Members can add more photos to each listing. The hard ceiling is 12.'],
        'active_listings' => ['label' => 'More active listings', 'audience' => 'provider', 'type' => 'limit', 'unit' => 'published listings', 'free' => 3, 'plus' => 20,
            'description' => 'How many listings a provider can have published at the same time, across all categories.'],
        'priority_placement' => ['label' => 'Priority placement', 'audience' => 'provider', 'type' => 'toggle',
            'description' => 'Member listings are listed first within each page of Explore results.'],
        'provider_analytics' => ['label' => 'Listing analytics', 'audience' => 'provider', 'type' => 'toggle',
            'description' => 'Views and saves for each listing on My listings.'],
        'saved_listings' => ['label' => 'More saved listings', 'audience' => 'seeker', 'type' => 'limit', 'unit' => 'saved listings', 'free' => 20, 'plus' => null,
            'description' => 'How many listings a user can save. Leave the member limit empty for unlimited.'],
        'saved_searches' => ['label' => 'More saved searches', 'audience' => 'seeker', 'type' => 'limit', 'unit' => 'saved searches', 'free' => 3, 'plus' => 25,
            'description' => 'How many searches a user can save.'],
    ];

    private ?array $rows = null;

    public function enabled(): bool
    {
        return (bool) config('aadi_plus.enabled', false);
    }

    public function memberVisible(?User $user): bool
    {
        return $this->enabled() && (bool) $user?->hasAadiPlus();
    }

    public function all(): array
    {
        $this->rows ??= Schema::hasTable('aadi_plus_perks') ? DB::table('aadi_plus_perks')->get()->keyBy('key')->all() : [];

        $perks = [];
        foreach (self::DEFINITIONS as $key => $definition) {
            $row = $this->rows[$key] ?? null;
            $perks[$key] = [
                'key' => $key, ...$definition,
                'enabled' => $row ? (bool) $row->enabled : true,
                'freeLimit' => $row ? $row->free_limit : ($definition['free'] ?? null),
                'plusLimit' => $row ? $row->plus_limit : ($definition['plus'] ?? null),
            ];
        }

        return $perks;
    }

    public function update(string $key, bool $enabled, ?int $freeLimit, ?int $plusLimit): void
    {
        abort_unless(isset(self::DEFINITIONS[$key]), 404);
        $isLimit = self::DEFINITIONS[$key]['type'] === 'limit';
        DB::table('aadi_plus_perks')->upsert([[
            'key' => $key, 'enabled' => $enabled,
            'free_limit' => $isLimit ? $freeLimit : null, 'plus_limit' => $isLimit ? $plusLimit : null,
            'created_at' => now(), 'updated_at' => now(),
        ]], ['key'], ['enabled', 'free_limit', 'plus_limit', 'updated_at']);
        $this->rows = null;
    }

    /** Whether a toggle perk applies to this user. */
    public function allows(string $key, ?User $user): bool
    {
        return $this->memberVisible($user) && $this->all()[$key]['enabled'];
    }

    /** The limit that applies to this user, or null for unlimited. */
    public function limit(string $key, ?User $user): ?int
    {
        if (!$this->enabled()) return null;

        $perk = $this->all()[$key];

        return $this->allows($key, $user) ? $perk['plusLimit'] : $perk['freeLimit'];
    }

    /** Limits the current user sees in the interface. */
    public function summary(?User $user): array
    {
        return [
            'enabled' => $this->enabled(),
            'listingPhotos' => $this->limit('listing_photos', $user),
            'activeListings' => $this->limit('active_listings', $user),
            'savedListings' => $this->limit('saved_listings', $user),
            'savedSearches' => $this->limit('saved_searches', $user),
            'analytics' => !$this->enabled() || $user?->is_admin || $this->allows('provider_analytics', $user),
        ];
    }
}
