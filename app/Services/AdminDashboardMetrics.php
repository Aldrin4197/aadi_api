<?php

namespace App\Services;

use App\Models\{Listing, ServiceListing, User, VehicleListing, ViewingRequest};
use Carbon\CarbonImmutable;

class AdminDashboardMetrics
{
    public function summarize(int $days): array
    {
        $now = CarbonImmutable::now();
        $start = $now->startOfDay()->subDays($days - 1);
        $previousStart = $start->subDays($days);
        $sources = [
            ['Spaces', Listing::where('category', 'space_rental'), 'active', '/admin/listings'],
            ['Vehicles', VehicleListing::query(), 'available', '/admin/vehicles'],
            ['Rakets', ServiceListing::query(), 'available', '/admin/services'],
        ];
        $period = function ($query) use ($start, $previousStart, $now) {
            return [
                'current' => (clone $query)->whereBetween('created_at', [$start, $now])->count(),
                'previous' => (clone $query)->where('created_at', '>=', $previousStart)->where('created_at', '<', $start)->count(),
            ];
        };
        $inventory = [];
        $newListings = ['current' => 0, 'previous' => 0];
        foreach ($sources as [$label, $query, $available, $url]) {
            $counts = (clone $query)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
            $inventory[] = ['label' => $label, 'total' => (int) $counts->sum(), 'available' => (int) ($counts[$available] ?? 0), 'url' => $url];
            $growth = $period($query);
            foreach ($growth as $key => $value) $newListings[$key] += $value;
        }
        $roles = [
            ['Landlords', 'landlord_status'], ['Vehicle owners', 'vehicle_owner_status'], ['Raketeers', 'service_provider_status'],
        ];
        $pending = array_map(fn ($role) => ['label' => $role[0], 'count' => User::where($role[1], 'pending')->count()], $roles);
        $signupCounts = User::whereBetween('created_at', [$start, $now])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')->groupByRaw('DATE(created_at)')->pluck('total', 'day');
        $trend = [];
        for ($day = $start; $day->lte($now); $day = $day->addDay()) {
            $trend[] = ['date' => $day->toDateString(), 'count' => (int) ($signupCounts[$day->toDateString()] ?? 0)];
        }
        $approvedProviders = User::where(fn ($q) => $q->where('landlord_status', 'approved')
            ->orWhere('vehicle_owner_status', 'approved')->orWhere('service_provider_status', 'approved'))->count();
        return [
            'days' => $days, 'start' => $start->toDateString(), 'end' => $now->toDateString(),
            'timezone' => config('app.timezone'), 'updatedAt' => $now->toIso8601String(),
            'totalUsers' => User::count(), 'approvedProviders' => $approvedProviders,
            'availableListings' => array_sum(array_column($inventory, 'available')),
            'pendingRoles' => array_sum(array_column($pending, 'count')),
            'newUsers' => $period(User::query()), 'newListings' => $newListings,
            'viewingRequests' => $period(ViewingRequest::query()),
            'pendingViewings' => ViewingRequest::where('status', 'pending')->count(),
            'inventory' => $inventory, 'pending' => $pending, 'signups' => $trend,
        ];
    }
}
