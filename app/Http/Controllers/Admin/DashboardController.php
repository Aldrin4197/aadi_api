<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\ListingView;
use App\Models\ServiceListing;
use App\Models\User;
use App\Models\VehicleListing;
use App\Models\ViewingRequest;
use App\Services\AdminOverview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request, AdminOverview $overview)
    {
        return Inertia::render('Admin/Dashboard', [
            'overview' => $overview->build((int) $request->query('range', 30)),
        ]);
    }

    public function users()
    {
        $users = User::withCount('listings')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    public function listings()
    {
        $listings = Listing::with('owner')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('Admin/Listings/Index', [
            'listings' => $listings,
        ]);
    }

    public function vehicleListings()
    {
        $listings = VehicleListing::with('owner')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('Admin/Vehicles/Index', [
            'listings' => $listings,
        ]);
    }

    public function serviceListings()
    {
        $listings = ServiceListing::with('provider')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('Admin/Services/Index', [
            'listings' => $listings,
        ]);
    }

    public function analytics()
    {
        // Overview stats
        $overview = [
            'totalListings' => Listing::count(),
            'activeListings' => Listing::where('status', 'active')->count(),
            'totalUsers' => User::count(),
            'totalViewings' => ViewingRequest::count(),
            'totalViews' => ListingView::count(),
            'totalFavorites' => DB::table('favorites')->count(),
        ];

        // Listings by type
        $listingsByType = Listing::select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->type->value => $item->count];
            });

        // Recent 30 days activity
        $dailyViews = ListingView::where('viewed_at', '>=', now()->subDays(30))
            ->select(DB::raw('DATE(viewed_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $dailyUsers = User::where('created_at', '>=', now()->subDays(30))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top listings by views
        $topListings = Listing::withCount('views')
            ->orderBy('views_count', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('Admin/Analytics', [
            'overview' => $overview,
            'listingsByType' => $listingsByType,
            'dailyViews' => $dailyViews,
            'dailyUsers' => $dailyUsers,
            'topListings' => $topListings,
        ]);
    }
}
