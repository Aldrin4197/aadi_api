<?php
namespace App\Http\Controllers\Marketplace;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Marketplace;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
class ProviderProfileController extends Controller {
    public function __invoke(Request $request, User $user, Marketplace $market) {
        $input = $request->validate(['service'=>['sometimes',Rule::in(array_keys(Marketplace::TYPES))], 'page'=>'sometimes|integer|min:1']);
        $counts = []; $approved = [];
        foreach (array_keys(Marketplace::TYPES) as $service) {
            $query = $market->model($service)::where($service === 'raketeer' ? 'provider_id' : 'owner_id', $user->id)
                ->where('status', $service === 'spaces' ? 'active' : 'available');
            if ($service === 'spaces') $query->where('category', 'space_rental');
            $counts[$service] = $query->count();
            if ($market->approved($user, $service)) $approved[] = $service;
        }
        $service = $input['service'] ?? (array_search(true, array_map(fn ($count) => $count > 0, $counts), true) ?: 'spaces');
        $query = $market->model($service)::where($service === 'raketeer' ? 'provider_id' : 'owner_id', $user->id)
            ->where('status', $service === 'spaces' ? 'active' : 'available');
        if ($service === 'spaces') $query->where('category', 'space_rental');
        $results = $query->latest()->paginate(12)->withQueryString()->through(function ($record) use ($market, $service) {
            $item = $market->dto($record, $service);
            // Public profile cards expose only preview fields, never exact coordinates or full description.
            return array_intersect_key($item, array_flip(['id','service','title','type','price','priceUnit','currency','photos','ownerId','ownerName','providerApproved']))
                + ['location'=>['city'=>$service === 'spaces' ? $record->city : null]];
        });
        $data = ['provider'=>[
            'id'=>(string)$user->id, 'name'=>$user->name, 'avatar_url'=>$user->avatar_url,
            'bio'=>$user->bio, 'city'=>$user->city, 'memberSince'=>$user->created_at?->format('Y'),
            'approvedRoles'=>$approved,
        ], 'counts'=>$counts, 'service'=>$service, 'results'=>$results];
        return $request->is('api/*') ? response()->json($data) : Inertia::render('Marketplace/ProviderProfile', $data);
    }
}
