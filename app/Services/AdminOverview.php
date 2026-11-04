<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\ListingView;
use App\Models\ProviderApplication;
use App\Models\ServiceListing;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\VehicleListing;
use App\Models\ViewingRequest;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/** Builds the admin dashboard overview: KPIs, trend series, supply mix, provider pipeline and plain-language insights. */
class AdminOverview
{
    public const RANGES = [7, 30, 90];

    private const SERVICES = [
        'spaces' => ['label' => 'Spaces', 'model' => Listing::class, 'live' => 'active', 'status' => 'landlord_status'],
        'haul' => ['label' => 'Vehicles', 'model' => VehicleListing::class, 'live' => 'available', 'status' => 'vehicle_owner_status'],
        'raketeer' => ['label' => 'Rakets', 'model' => ServiceListing::class, 'live' => 'available', 'status' => 'service_provider_status'],
    ];

    public function build(int $days): array
    {
        $days = in_array($days, self::RANGES, true) ? $days : 30;
        $since = today()->subDays($days - 1);
        $previousSince = $since->copy()->subDays($days);
        $dates = collect(range(0, $days - 1))->map(fn ($i) => $since->copy()->addDays($i)->format('Y-m-d'))->all();

        $users = $this->window(User::query(), 'created_at', $since, $previousSince, $dates);
        $listingParts = collect(self::SERVICES)->map(fn ($s) => $this->window($s['model']::query(), 'created_at', $since, $previousSince, $dates));
        $listings = $this->merge($listingParts->all(), $dates);
        $views = $this->window(ListingView::query(), 'viewed_at', $since, $previousSince, $dates);
        $saves = $this->merge([
            $this->window(DB::table('favorites'), 'created_at', $since, $previousSince, $dates),
            $this->window(DB::table('marketplace_saves'), 'created_at', $since, $previousSince, $dates),
        ], $dates);
        $reviews = $this->window(DB::table('marketplace_reviews'), 'created_at', $since, $previousSince, $dates);
        $viewings = $this->window(ViewingRequest::query(), 'created_at', $since, $previousSince, $dates);
        $engagement = $this->merge([$saves, $reviews, $viewings], $dates);

        $supply = collect(self::SERVICES)->map(function ($s, $key) {
            $total = $s['model']::count();
            $live = $s['model']::where('status', $s['live'])->count();
            return ['key' => $key, 'label' => $s['label'], 'live' => $live, 'other' => $total - $live, 'total' => $total];
        })->values();

        $pipeline = collect(self::SERVICES)->map(function ($s, $key) {
            $counts = User::select($s['status'], DB::raw('count(*) as c'))->groupBy($s['status'])->pluck('c', $s['status'])
                ->mapWithKeys(fn ($count, $status) => [$status instanceof \BackedEnum ? $status->value : (string) $status => $count]);
            return ['key' => $key, 'label' => $s['label'], 'approved' => (int) ($counts['approved'] ?? 0), 'pending' => (int) ($counts['pending'] ?? 0), 'rejected' => (int) ($counts['rejected'] ?? 0)];
        })->values();

        $pending = $pipeline->sum('pending');
        $oldest = ProviderApplication::where('status', 'pending')->min('submitted_at');
        $oldestDays = $oldest ? (int) max(0, now()->diffInDays($oldest, true)) : null;
        $totalUsers = User::count();
        $verified = User::whereNotNull('email_verified_at')->count();
        $liveListings = $supply->sum('live');
        $ticketsActive = SupportTicket::whereIn('status', SupportTicket::ACTIVE)->count();
        $ticketsUnanswered = SupportTicket::whereIn('status', SupportTicket::ACTIVE)->whereNull('first_response_at')->count();

        $topListing = Listing::query()
            ->select('listings.id', 'listings.title')
            ->selectRaw('count(listing_views.id) as view_count')
            ->join('listing_views', 'listing_views.listing_id', '=', 'listings.id')
            ->where('listing_views.viewed_at', '>=', $since)
            ->groupBy('listings.id', 'listings.title')
            ->orderByDesc('view_count')
            ->first();

        return [
            'range' => ['days' => $days, 'from' => $since->toDateString(), 'to' => today()->toDateString(), 'options' => self::RANGES],
            'kpis' => [
                $this->kpi('users', 'Total users', $totalUsers, $users, 'New users', $users['current'], 'users'),
                $this->kpi('listings', 'Live listings', $liveListings, $listings, 'New listings', $listings['current'], 'listings', "{$supply->sum('total')} listings in total"),
                $this->kpi('views', 'Listing views', $views['current'], $views, null, null, 'views', 'Space listing page views'),
                $this->kpi('engagement', 'Engagement', $engagement['current'], $engagement, null, null, 'engagement', "{$saves['current']} saves · {$reviews['current']} reviews · {$viewings['current']} viewing requests"),
                [
                    'key' => 'approvals', 'label' => 'Awaiting approval', 'value' => $pending, 'delta' => null, 'series' => [], 'tone' => $pending > 0 ? 'warning' : 'good',
                    'hint' => $pending > 0 ? ($oldestDays !== null ? "Oldest waiting {$oldestDays} ".($oldestDays === 1 ? 'day' : 'days') : 'Provider roles to review') : 'Nothing waiting',
                    'href' => '/admin/approvals', 'icon' => 'approvals',
                ],
                [
                    'key' => 'tickets', 'label' => 'Open tickets', 'value' => $ticketsActive, 'delta' => null, 'series' => [], 'tone' => $ticketsUnanswered > 0 ? 'warning' : 'good', 'icon' => 'tickets',
                    'hint' => $ticketsActive === 0 ? 'No open requests' : ($ticketsUnanswered > 0 ? "{$ticketsUnanswered} without a reply" : 'All have a reply'), 'href' => '/admin/tickets',
                ],
                [
                    'key' => 'verified', 'label' => 'Email verified', 'value' => $totalUsers ? round($verified / $totalUsers * 100) : 0, 'suffix' => '%', 'delta' => null, 'series' => [], 'tone' => 'neutral',
                    'hint' => "{$verified} of {$totalUsers} users", 'icon' => 'verified',
                ],
            ],
            'trend' => [
                'dates' => $dates,
                'series' => ['users' => $users['daily'], 'listings' => $listings['daily'], 'views' => $views['daily'], 'engagement' => $engagement['daily']],
            ],
            'supply' => $supply,
            'pipeline' => $pipeline,
            'insights' => $this->insights($days, $users, $supply, $pending, $oldestDays, $totalUsers, $verified, $topListing, $ticketsActive, $ticketsUnanswered),
            'activity' => $this->activity(),
        ];
    }

    public function window($query, string $column, CarbonInterface $since, CarbonInterface $previousSince, array $dates): array
    {
        $current = (clone $query)->where($column, '>=', $since)->count();
        $previous = (clone $query)->where($column, '>=', $previousSince)->where($column, '<', $since)->count();
        $rows = (clone $query)->where($column, '>=', $since)->selectRaw("DATE($column) as d, COUNT(*) as c")->groupBy('d')->pluck('c', 'd');

        return [
            'current' => $current,
            'previous' => $previous,
            'daily' => array_map(fn ($date) => (int) ($rows[$date] ?? 0), $dates),
        ];
    }

    public function merge(array $parts, array $dates): array
    {
        return [
            'current' => array_sum(array_column($parts, 'current')),
            'previous' => array_sum(array_column($parts, 'previous')),
            'daily' => array_map(fn ($i) => array_sum(array_column(array_column($parts, 'daily'), $i)), array_keys($dates)),
        ];
    }

    public function delta(int $current, int $previous): ?int
    {
        return $previous > 0 ? (int) round(($current - $previous) / $previous * 100) : null;
    }

    public function kpi(string $key, string $label, int|float $value, array $window, ?string $sub, ?int $subValue, string $icon, ?string $hint = null): array
    {
        $delta = $this->delta($window['current'], $window['previous']);

        return [
            'key' => $key, 'label' => $label, 'value' => $value, 'delta' => $delta, 'series' => $window['daily'],
            'tone' => $delta === null ? 'neutral' : ($delta >= 0 ? 'good' : 'warning'), 'icon' => $icon,
            'newInRange' => $window['current'], 'previousInRange' => $window['previous'],
            'hint' => $hint ?? ($sub ? "{$subValue} ".strtolower($sub) : null),
        ];
    }

    private function insights(int $days, array $users, $supply, int $pending, ?int $oldestDays, int $totalUsers, int $verified, $topListing, int $ticketsActive = 0, int $ticketsUnanswered = 0): array
    {
        $items = [];

        if ($ticketsUnanswered > 0) {
            $items[] = ['tone' => 'warning', 'title' => "{$ticketsUnanswered} support ".($ticketsUnanswered === 1 ? 'ticket has' : 'tickets have').' no reply', 'text' => 'People who write in expect an answer within a day.', 'href' => '/admin/tickets', 'cta' => 'Open tickets'];
        }

        if ($pending > 0) {
            $wait = $oldestDays ? " The oldest has waited {$oldestDays} ".($oldestDays === 1 ? 'day' : 'days').'.' : '';
            $items[] = ['tone' => 'warning', 'title' => "{$pending} provider ".($pending === 1 ? 'application needs' : 'applications need').' review', 'text' => 'Providers cannot publish until approved.'.$wait, 'href' => '/admin/approvals', 'cta' => 'Review applications'];
        } else {
            $items[] = ['tone' => 'good', 'title' => 'Approval queue is clear', 'text' => 'Every provider application has been decided.'];
        }

        $delta = $this->delta($users['current'], $users['previous']);
        if ($users['current'] === 0) {
            $items[] = ['tone' => 'warning', 'title' => "No new sign-ups in the last {$days} days", 'text' => 'Check that registration and your marketing links are working.'];
        } elseif ($delta === null) {
            $items[] = ['tone' => 'info', 'title' => "{$users['current']} new ".($users['current'] === 1 ? 'user' : 'users').' joined', 'text' => "There were none in the previous {$days} days."];
        } else {
            $direction = $delta >= 0 ? 'up' : 'down';
            $items[] = ['tone' => $delta >= 10 ? 'good' : ($delta <= -10 ? 'warning' : 'info'), 'title' => "Sign-ups are {$direction} ".abs($delta).'%', 'text' => "{$users['current']} new users versus {$users['previous']} in the previous {$days} days."];
        }

        foreach ($supply->where('live', 0) as $row) {
            $items[] = ['tone' => 'warning', 'title' => "No live {$row['label']} listings", 'text' => 'Visitors searching this category will see empty results.', 'href' => match ($row['key']) { 'spaces' => '/admin/listings', 'haul' => '/admin/vehicles', default => '/admin/services' }, 'cta' => 'Open listings'];
        }

        $notLive = $supply->sum('other');
        if ($notLive > 0) {
            $items[] = ['tone' => 'info', 'title' => "{$notLive} ".($notLive === 1 ? 'listing is' : 'listings are').' not live', 'text' => 'These are drafts, reserved, rented or inactive, and are hidden from search.'];
        }

        if ($totalUsers > 0 && $verified < $totalUsers) {
            $unverified = $totalUsers - $verified;
            $items[] = ['tone' => 'info', 'title' => "{$unverified} ".($unverified === 1 ? 'user has' : 'users have').' not verified their email', 'text' => 'Verified emails are required to build trust badges.'];
        }

        if ($topListing) {
            $items[] = ['tone' => 'good', 'title' => 'Most viewed space', 'text' => "{$topListing->title} had {$topListing->view_count} ".($topListing->view_count == 1 ? 'view' : 'views')." in the last {$days} days."];
        }

        return $items;
    }

    private function activity(): array
    {
        $feed = collect();

        User::latest()->limit(6)->get()->each(fn ($u) => $feed->push(['type' => 'user', 'title' => $u->name, 'meta' => 'Joined Aadi', 'at' => $u->created_at, 'href' => '/admin/users']));
        foreach (self::SERVICES as $key => $service) {
            $path = match ($key) { 'spaces' => '/admin/listings', 'haul' => '/admin/vehicles', default => '/admin/services' };
            $service['model']::latest()->limit(4)->get()->each(fn ($l) => $feed->push(['type' => $key, 'title' => $l->title, 'meta' => "New {$service['label']} listing", 'at' => $l->created_at, 'href' => $path]));
        }
        ProviderApplication::with('user:id,name')->latest('submitted_at')->limit(4)->get()->each(fn ($a) => $feed->push([
            'type' => 'application', 'title' => $a->display_name ?: ($a->user?->name ?? 'Applicant'), 'meta' => self::SERVICES[$a->service]['label'].' provider application · '.$a->status,
            'at' => $a->submitted_at, 'href' => '/admin/approvals',
        ]));

        return $feed->filter(fn ($i) => $i['at'])->sortByDesc('at')->take(7)->map(fn ($i) => [...$i, 'at' => $i['at']->toIso8601String()])->values()->all();
    }
}
