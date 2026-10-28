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
        $item = $this->market->dto($this->market->find($service, $id), $service);
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
        DB::table($service === 'spaces' ? 'favorites' : 'marketplace_saves')->upsert(
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
        DB::table('marketplace_reviews')->upsert([[
            'service'=>$service, 'listing_id'=>$id, 'user_id'=>$r->user()->id,
            ...$data, 'created_at'=>now(), 'updated_at'=>now(),
        ]], ['user_id','service','listing_id'], ['rating','body','updated_at']);
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
        $rows->through(function ($row) {
            $record = $this->market->model($row->service)::find($row->listing_id);
            $item = $record ? $this->market->dto($record, $row->service) : null;
            return $item && $item['status'] === 'active' ? $item : ['id'=>$row->listing_id, 'service'=>$row->service, 'unavailable'=>true];
        });
        return $r->is('api/*') ? response()->json($rows)->header('Cache-Control','private, no-store')
            : Inertia::render('Account/Saved', ['results'=>$rows]);
    }
}
