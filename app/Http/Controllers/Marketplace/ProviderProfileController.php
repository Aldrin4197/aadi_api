<?php
namespace App\Http\Controllers\Marketplace;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\{ListingBadgeService, Marketplace};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $viewer = $request->is('api/*') ? Auth::guard('sanctum')->user() : $request->user();
        $query = $market->model($service)::where($service === 'raketeer' ? 'provider_id' : 'owner_id', $user->id)
            ->where('status', $service === 'spaces' ? 'active' : 'available');
        if ($service === 'spaces') $query->where('category', 'space_rental');
        $page = $query->with($service === 'raketeer' ? 'provider' : 'owner')->latest()->paginate(12)->withQueryString();
        $badges = app(ListingBadgeService::class)->forRecords($page->getCollection(), $service, $viewer);
        $results = $page->through(function ($record) use ($market, $service, $viewer, $badges) {
            $item = $market->dto($record, $service, $viewer, $badges[(string) $record->id]);
            // Public profile cards expose only preview fields, never exact coordinates or full description.
            return array_intersect_key($item, array_flip(['id','service','title','type','price','priceUnit','currency','photos','ownerId','ownerName','ownerAvatarUrl','ownerEmailVerified','ownerAadiPlus','providerApproved','availabilityPaused','badges']))
                + ['location'=>['city'=>$service === 'spaces' ? $record->city : null]];
        });
        $likes = DB::table('profile_likes')->where('profile_id', $user->id);
        $data = ['provider'=>[
            'id'=>(string)$user->id, 'name'=>$user->name, 'avatar_url'=>$user->avatar_url,
            'emailVerified'=>$user->email_verified_at !== null, 'bio'=>$user->bio, 'city'=>$user->city, 'memberSince'=>$user->created_at?->format('Y'),
            'approvedRoles'=>$approved, 'aadiPlus'=>app(\App\Services\AadiPlusPerks::class)->memberVisible($user),
        ], 'profileLikes'=>['count'=>(clone $likes)->count(), 'liked'=>$viewer ? (clone $likes)->where('user_id', $viewer->id)->exists() : false],
            'counts'=>$counts, 'service'=>$service, 'results'=>$results];
        return $request->is('api/*') ? response()->json($data)->header('Cache-Control', 'private, no-store') : Inertia::render('Marketplace/ProviderProfile', $data);
    }
}
