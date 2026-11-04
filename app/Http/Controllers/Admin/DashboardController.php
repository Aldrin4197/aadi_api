<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\ListingView;
use App\Models\ServiceListing;
use App\Models\User;
use App\Models\VehicleListing;
use App\Models\ViewingRequest;
use App\Services\AdminAnalytics;
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

    public function analytics(Request $request, AdminAnalytics $analytics)
    {
        return Inertia::render('Admin/Analytics', [
            'analytics' => $analytics->build((int) $request->query('range', 30)),
        ]);
    }
}