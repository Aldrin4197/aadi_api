<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\ListingView;
use App\Models\ServiceListing;
use App\Models\User;
use App\Models\VehicleListing;
use App\Models\ViewingRequest;
use Illuminate\Support\Facades\DB;

/** Admin analytics: how people use the marketplace, with period comparisons and plain-language insights. */
class AdminAnalytics
{
    private const CATEGORIES = [
        'spaces' => ['label' => 'Spaces', 'model' => Listing::class, 'type' => 'type', 'live' => 'active'],
        'haul' => ['label' => 'Vehicles', 'model' => VehicleListing::class, 'type' => 'vehicle_type', 'live' => 'available'],
        'raketeer' => ['label' => 'Rakets', 'model' => ServiceListing::class, 'type' => 'service_type', 'live' => 'available'],
    ];

    public function __construct(private AdminOverview $overview) {}

    public function build(int $days): array
    {
        $o = $this->overview;
        $days = in_array($days, AdminOverview::RANGES, true) ? $days : 30;
        $since = today()->subDays($days - 1);
        $previousSince = $since->copy()->subDays($days);
        $dates = collect(range(0, $days - 1))->map(fn ($i) => $since->copy()->addDays($i)->format('Y-m-d'))->all();
        $window = fn ($query, string $column) => $o->window($query, $column, $since, $previousSince, $dates);

        $views = $window(ListingView::query(), 'viewed_at');
        $spaceSaves = $window(DB::table('favorites'), 'created_at');
        $otherSaves = $window(DB::table('marketplace_saves'), 'created_at');
        $saves = $o->merge([$spaceSaves, $otherSaves], $dates);
        $reviews = $window(DB::table('marketplace_reviews'), 'created_at');
        $spaceReviews = $window(DB::table('marketplace_reviews')->where('service', 'spaces'), 'created_at');
        $viewings = $window(ViewingRequest::query(), 'created_at');
        $users = $window(User::query(), 'created_at');
        $listings = $o->merge(array_map(fn ($c) => $window($c['model']::query(), 'created_at'), self::CATEGORIES), $dates);

        $totalListings = 0;
        $liveListings = 0;
        foreach (self::CATEGORIES as $category) {
            $totalListings += $category['model']::count();
            $liveListings += $category['model']::where('status', $category['live'])->count();
        }
        $totalUsers = User::count();
        $rating = DB::table('marketplace_reviews')->avg('rating');
        $kpi = fn (...$args) => [...$o->kpi(...$args), 'scope' => "Last {$days} days"];

        $types = $this->types();
        $top = $this->topListings($since);
        $funnel = $this->funnel($views['current'], $spaceSaves['current'], $viewings['current'], $spaceReviews['current']);

        return [
            'range' => ['days' => $days, 'from' => $since->toDateString(), 'to' => today()->toDateString(), 'options' => AdminOverview::RANGES],
            'kpis' => [
                $kpi('views', 'Listing views', $views['current'], $views, null, null, 'views', 'Property page views'),
                $kpi('saves', 'Saves', $saves['current'], $saves, null, null, 'saves', "{$spaceSaves['current']} properties · {$otherSaves['current']} vehicles & rakets"),
                $kpi('reviews', 'Reviews', $reviews['current'], $reviews, null, null, 'reviews', $rating ? 'Average rating '.number_format((float) $rating, 1).' of 5' : 'No reviews yet'),
                $kpi('viewings', 'Viewing requests', $viewings['current'], $viewings, null, null, 'viewings', 'Requests to see a property'),
                $kpi('users', 'New users', $users['current'], $users, null, null, 'users', number_format($totalUsers).' accounts in total'),
                $kpi('listings', 'New listings', $listings['current'], $listings, null, null, 'listings', number_format($liveListings).' of '.number_format($totalListings).' listings are live'),
            ],
            'trend' => [
                'dates' => $dates,
                'series' => ['views' => $views['daily'], 'saves' => $saves['daily'], 'reviews' => $reviews['daily'], 'viewings' => $viewings['daily'], 'users' => $users['daily'], 'listings' => $listings['daily']],
            ],
            'funnel' => $funnel,
            'types' => $types['rows'],
            'topListings' => $top,
            'insights' => $this->insights($days, $views, $users, $funnel, $top, $types['missing'], $rating, $reviews['current'], $totalUsers),
        ];
    }

    private function funnel(int $views, int $saves, int $viewings, int $reviews): array
    {
        $steps = [['key' => 'views', 'label' => 'Viewed a property', 'count' => $views], ['key' => 'saves', 'label' => 'Saved it', 'count' => $saves], ['key' => 'viewings', 'label' => 'Asked for a viewing', 'count' => $viewings], ['key' => 'reviews', 'label' => 'Left a review', 'count' => $reviews]];
        $max = max(1, ...array_column($steps, 'count'));

        return array_map(fn ($s) => [...$s, 'width' => round($s['count'] / $max * 100, 1), 'per100' => $views > 0 && $s['key'] !== 'views' && $s['count'] <= $views ? round($s['count'] / $views * 100, 1) : null], $steps);
    }

    /** Listings by type across all categories, plus the allowed types that have no listing yet. */
    private function types(): array
    {
        $rows = collect();
        $missing = [];
        foreach (self::CATEGORIES as $key => $category) {
            $query = $category['model']::query()->whereNotNull($category['type']);
            if ($key === 'spaces') $query->where('category', 'space_rental');
            $counts = $query->select($category['type'].' as t', DB::raw('count(*) as c'))->groupBy($category['type'])->pluck('c', 't')
                ->mapWithKeys(fn ($count, $type) => [$type instanceof \BackedEnum ? $type->value : (string) $type => (int) $count]);
            foreach ($counts as $type => $count) $rows->push(['category' => $key, 'categoryLabel' => $category['label'], 'type' => $type, 'count' => $count]);
            foreach (array_diff(Marketplace::TYPES[$key], $counts->keys()->all()) as $type) $missing[] = ['category' => $category['label'], 'type' => $type];
        }

        return ['rows' => $rows->sortByDesc('count')->values()->all(), 'missing' => $missing];
    }

    private function topListings($since): array
    {
        $top = Listing::query()->where('category', 'space_rental')
            ->withCount(['views as window_views' => fn ($q) => $q->where('viewed_at', '>=', $since)])
            ->orderByDesc('window_views')->limit(5)->get(['id', 'title', 'city'])->filter(fn ($l) => $l->window_views > 0)->values();
        $saves = $top->isEmpty() ? collect() : DB::table('favorites')->whereIn('listing_id', $top->pluck('id'))->selectRaw('listing_id, count(*) as c')->groupBy('listing_id')->pluck('c', 'listing_id');

        return $top->map(fn ($l) => ['id' => $l->id, 'title' => $l->title, 'city' => $l->city, 'views' => (int) $l->window_views, 'saves' => (int) ($saves[$l->id] ?? 0)])->all();
    }

    private function insights(int $days, array $views, array $users, array $funnel, array $top, array $missing, $rating, int $reviewCount, int $totalUsers): array
    {
        $items = [];
        $viewsDelta = $this->overview->delta($views['current'], $views['previous']);
        if ($views['current'] === 0) {
            $items[] = ['tone' => 'warning', 'title' => "No property views in the last {$days} days", 'text' => 'Views are recorded when a signed-in or anonymous visitor opens a property. Share the site to start seeing traffic.'];
        } elseif ($viewsDelta === null) {
            $items[] = ['tone' => 'info', 'title' => number_format($views['current']).' property '.($views['current'] === 1 ? 'view' : 'views'), 'text' => "There were none in the previous {$days} days."];
        } else {
            $items[] = ['tone' => $viewsDelta >= 10 ? 'good' : ($viewsDelta <= -10 ? 'warning' : 'info'), 'title' => 'Property views are '.($viewsDelta >= 0 ? 'up ' : 'down ').abs($viewsDelta).'%', 'text' => number_format($views['current'])." views versus ".number_format($views['previous'])." in the previous {$days} days."];
        }

        $saveRate = collect($funnel)->firstWhere('key', 'saves')['per100'] ?? null;
        if ($saveRate !== null && $views['current'] >= 10) {
            $items[] = ['tone' => $saveRate >= 5 ? 'good' : 'info', 'title' => "{$saveRate} saves per 100 views", 'text' => $saveRate >= 5 ? 'Visitors like what they see. Strong listings get saved.' : 'Few visitors save a listing. Better photos and clearer prices usually help.'];
        }

        if ($top) {
            $items[] = ['tone' => 'good', 'title' => 'Most viewed: '.$top[0]['title'], 'text' => "{$top[0]['views']} ".($top[0]['views'] === 1 ? 'view' : 'views').($top[0]['city'] ? " · {$top[0]['city']}" : '').'.', 'href' => '/admin/listings', 'cta' => 'Open properties'];
        }

        if ($missing) {
            $label = fn (string $type) => strtolower(trim(str_replace('_', ' ', preg_replace('/([A-Z])/', ' $1', $type))));
            $groups = collect($missing)->groupBy('category')->map(fn ($rows, $category) => $category.': '.$rows->pluck('type')->map($label)->take(3)->implode(', ').($rows->count() > 3 ? ' +'.($rows->count() - 3) : ''))->implode('; ');
            $items[] = ['tone' => 'info', 'title' => count($missing).' listing '.(count($missing) === 1 ? 'type has' : 'types have').' no listings', 'text' => "Visitors who filter by these find nothing. {$groups}."];
        }

        if ($reviewCount > 0 && $rating) {
            $items[] = ['tone' => $rating >= 4 ? 'good' : 'info', 'title' => 'Average rating '.number_format((float) $rating, 1).' of 5', 'text' => number_format($reviewCount).' new '.($reviewCount === 1 ? 'review' : 'reviews').' in the last '.$days.' days.'];
        }

        if ($users['current'] > 0 && $totalUsers > 0) {
            $providers = User::where(fn ($q) => $q->where('landlord_status', 'approved')->orWhere('vehicle_owner_status', 'approved')->orWhere('service_provider_status', 'approved'))->count();
            $share = (int) round($providers / $totalUsers * 100);
            $items[] = ['tone' => 'info', 'title' => "{$share}% of users are approved providers", 'text' => 'A healthy marketplace needs both people looking and people listing.'];
        }

        return $items;
    }
}
