<?php
namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Services\Marketplace;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountDashboardController extends Controller
{
    public function __invoke(Request $request, Marketplace $market)
    {
        $inventory = []; $recent = collect();
        foreach (array_keys(Marketplace::TYPES) as $service) {
            $query = $market->model($service)::query()
                ->where($service === 'raketeer' ? 'provider_id' : 'owner_id', $request->user()->id);
            if ($service === 'spaces') $query->where('category', 'space_rental');
            $inventory[] = [
                'service' => $service,
                'roleStatus' => $request->user()->{$market->statusField($service)}?->value ?? 'none',
                'total' => (clone $query)->count(),
                'published' => (clone $query)->where('status', $service === 'spaces' ? 'active' : 'available')->count(),
                'drafts' => (clone $query)->where('status', $service === 'spaces' ? 'draft' : 'inactive')->count(),
            ];
            foreach ((clone $query)->with($service === 'raketeer' ? 'provider' : 'owner')->latest()->orderByDesc('id')->limit(4)->get() as $record) {
                $item = $market->dto($record, $service);
                $recent->push(collect($item)->only(['id','service','title','photos','price','priceUnit','status','createdAt'])->all());
            }
        }
        return Inertia::render('Account/Dashboard', [
            'inventory' => $inventory,
            'recentListings' => $recent->sortByDesc('createdAt')->take(4)->values(),
        ]);
    }
}
