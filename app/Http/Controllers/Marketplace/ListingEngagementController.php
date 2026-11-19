<?php
namespace App\Http\Controllers\Marketplace;
use App\Http\Controllers\Controller;
use App\Services\{Marketplace, ListingEngagement};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ListingEngagementController extends Controller {
    public function __construct(private Marketplace $market, private ListingEngagement $engagement) {}
    private function active(string $service, string $id): array {
        $item = $this->market->dto($this->market->find($service, $id), $service, null, []);
        abort_unless($item['status'] === 'active', 404);
        return $item;
    }
    public function show(Request $r, string $service, string $id) {
        $this->active($service, $id);
        $r->validate(['reviewPage'=>'sometimes|integer|min:1']);
        return response()->json($this->engagement->summary($service, $id, $r->user()?->id))->header('Cache-Control', 'private, no-store');
    }
    public function save(Request $r, string $service, string $id) {
        $this->active($service, $id);
        $key = ['user_id'=>$r->user()->id, 'listing_id'=>$id];
        if ($service !== 'spaces') $key['service'] = $service;
        $table = $service === 'spaces' ? 'favorites' : 'marketplace_saves';
        $limit = app(\App\Services\AadiPlusPerks::class)->limit('saved_listings', $r->user());
        if ($limit !== null && !DB::table($table)->where($key)->exists()) {
            $count = DB::table('favorites')->where('user_id', $r->user()->id)->count() + DB::table('marketplace_saves')->where('user_id', $r->user()->id)->count();
            if ($count >= $limit) throw \Illuminate\Validation\ValidationException::withMessages(['save' => "You can save up to {$limit} listings. ".($r->user()->hasAadiPlus() ? '' : 'Upgrade to Aadi Plus to save more.')]);
        }
        DB::table($table)->upsert(
            [[...$key, 'created_at'=>now(), 'updated_at'=>now()]], array_keys($key), ['updated_at']);
        return $this->done($r);
    }
    public function unsave(Request $r, string $service, string $id) {
        $this->market->model($service);
        $this->engagement->saves($service, $id)->where('user_id', $r->user()->id)->delete();
        return $this->done($r);
    }
    public function review(Request $r, string $service, string $id) {
        $item = $this->active($service, $id);
        abort_if($item['ownerId'] === (string) $r->user()->id, 403);
        $data = $r->validate(['rating'=>'required|integer|between:1,5', 'body'=>'required|string|min:10|max:2000']);
        $isNew = !$this->engagement->reviews($service, $id)->where('user_id', $r->user()->id)->exists();
        DB::table('marketplace_reviews')->upsert([[
            'service'=>$service, 'listing_id'=>$id, 'user_id'=>$r->user()->id,
            ...$data, 'created_at'=>now(), 'updated_at'=>now(),
        ]], ['user_id','service','listing_id'], ['rating','body','updated_at']);
        if ($isNew && ($owner = \App\Models\User::find($item['ownerId']))) app(\App\Services\Notifier::class)->reviewReceived($owner, $item['title'], (int) $data['rating'], "/offers/$service/$id");
        return $this->done($r);
    }
    public function deleteReview(Request $r, string $service, string $id) {
        $this->market->model($service);
        $this->engagement->reviews($service, $id)->where('user_id', $r->user()->id)->delete();
        return $this->done($r);
    }
    private function done(Request $r) {
        return $r->is('api/*') ? response()->noContent() : back(303);
    }
    public function saved(Request $r) {
        $rows = DB::table('favorites')->where('user_id', $r->user()->id)
            ->select('listing_id', 'created_at')->selectRaw("'spaces' as service")
            ->unionAll(DB::table('marketplace_saves')->where('user_id', $r->user()->id)->select('listing_id','created_at','service'))
            ->orderByDesc('created_at')->paginate(18)->withQueryString();
        $recordsByService = [];
        $badgesByService = [];
        foreach (array_keys(Marketplace::TYPES) as $service) {
            $ids = $rows->getCollection()->where('service', $service)->pluck('listing_id');
            if ($ids->isEmpty()) continue;
            $records = $this->market->model($service)::with($service === 'raketeer' ? 'provider' : 'owner')
                ->whereIn('id', $ids)->get()->keyBy(fn ($record) => (string) $record->id);
            $recordsByService[$service] = $records;
            $badgesByService[$service] = app(\App\Services\ListingBadgeService::class)
                ->forRecords($records, $service, $r->user());
        }
        $rows->through(function ($row) use ($recordsByService, $badgesByService, $r) {
            $record = ($recordsByService[$row->service] ?? collect())->get((string) $row->listing_id);
            $badges = $badgesByService[$row->service][(string) ($record?->id ?? '')] ?? [];
            $item = $record ? $this->market->dto($record, $row->service, $r->user(), $badges) : null;
            return $item && $item['status'] === 'active' ? $item : ['id'=>$row->listing_id, 'service'=>$row->service, 'unavailable'=>true];
        });
        return $r->is('api/*') ? response()->json($rows)->header('Cache-Control','private, no-store')
            : Inertia::render('Account/Saved', ['results'=>$rows]);
    }
}
