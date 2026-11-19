<?php
namespace App\Http\Controllers\Marketplace;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MarketplaceRequest;
use App\Services\Marketplace;
use App\Services\LandingPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MarketplaceController extends Controller {
    public function __construct(private Marketplace $market) {}
    private function filters(Request $r): array {
        return $r->validate(['service'=>['sometimes',Rule::in(array_keys(Marketplace::TYPES))], 'locationName'=>'nullable|string|max:255', 'q'=>'nullable|string|max:150', 'type'=>'nullable|string|max:80',
            'lat'=>'nullable|required_with:lng|numeric|between:-90,90','lng'=>'nullable|required_with:lat|numeric|between:-180,180',
            'radiusKm'=>'nullable|numeric|min:1|max:200','minPrice'=>'nullable|numeric|min:0','maxPrice'=>['nullable','numeric','min:0',...($r->filled('minPrice') ? ['gte:minPrice'] : [])],'page'=>'nullable|integer|min:1']);
    }
    public function index(Request $r) {
        // Signed-in users go straight to the map; the marketing landing page is for visitors.
        if ($r->user() && $r->path() === '/') return redirect(route('map', absolute: false).($r->getQueryString() ? '?'.$r->getQueryString() : ''));
        $filters = $this->filters($r);
        return Inertia::render('Marketplace/Explore', [
            'results'=>$this->market->search($filters, $r->user()), 'filters'=>(object)$filters, 'types'=>Marketplace::TYPES,
            ...($r->path() === '/' ? ['landing'=>fn () => app(LandingPage::class)->content()] : []),
        ]);
    }
    public function search(Request $r) { return response()->json($this->market->search($this->filters($r), $r->user())); }
    public function show(Request $r, string $service, string $id) {
        $r->validate(['reviewPage'=>'sometimes|integer|min:1']);
        $record = $this->market->find($service,$id); $item = $this->market->dto($record,$service,$r->user());
        if ($item['status'] !== 'active') { abort_unless($r->user(),404); $this->market->authorize($r->user(),$record,$service); }
        else {
            try { app(\App\Services\ListingEngagement::class)->recordMarketplaceView($service, (string) $record->id, $r->user(), $r->ip(), $item['ownerId']); }
            catch (\Throwable $error) { report($error); }
        }
        $unavailableDates = $service === 'spaces' ? [] : MarketplaceRequest::where('service', $service)
            ->where('listing_id', $id)->where('status', 'accepted')
            ->where(fn ($query) => $query->whereDate('requested_until', '>=', today())->orWhere(fn ($query) => $query->whereNull('requested_until')->whereDate('requested_at', '>=', today())))
            ->orderBy('requested_at')->get(['requested_at', 'requested_until'])
            ->map(fn ($booking) => ['start' => $booking->requested_at->toDateString(), 'end' => ($booking->requested_until ?? $booking->requested_at)->toDateString()])->all();
        return $r->is('api/*') ? response()->json(['data'=>$item, 'unavailableDates'=>$unavailableDates]) : Inertia::render('Marketplace/Show', ['listing'=>$item, 'unavailableDates'=>$unavailableDates, 'engagement'=>app(\App\Services\ListingEngagement::class)->summary($service, $id, $r->user()?->id), 'timezone'=>config('app.timezone')]);
    }
    public function manage(Request $r) {
        $listings = collect(); $statuses = [];
        foreach (array_keys(Marketplace::TYPES) as $service) {
            $statuses[$service] = $r->user()->{$this->market->statusField($service)}?->value ?? 'none';
            $records = $this->market->model($service)::with($service === 'raketeer' ? 'provider' : 'owner')
                ->where($service === 'raketeer' ? 'provider_id' : 'owner_id',$r->user()->id)->latest()->get();
            $badges = app(\App\Services\ListingBadgeService::class)->forRecords($records, $service, $r->user());
            foreach ($records as $record) $listings->push($this->market->dto($record,$service,$r->user(),$badges[(string) $record->id]));
        }
        foreach (array_keys(Marketplace::TYPES) as $service) {
            if (!app(\App\Services\AadiPlusPerks::class)->summary($r->user())['analytics']) break;
            $ids = $listings->where('service', $service)->pluck('id')->all();
            if (!$ids) continue;
            $views = DB::table($service === 'spaces' ? 'listing_views' : 'marketplace_listing_views')
                ->whereIn('listing_id', $ids)->where('viewed_at', '>=', now()->subDays(7))
                ->when($service !== 'spaces', fn ($query) => $query->where('service', $service))
                ->selectRaw('listing_id, count(*) as total')->groupBy('listing_id')->pluck('total', 'listing_id');
            $saves = DB::table($service === 'spaces' ? 'favorites' : 'marketplace_saves')
                ->whereIn('listing_id', $ids)
                ->when($service !== 'spaces', fn ($query) => $query->where('service', $service))
                ->selectRaw('listing_id, count(*) as total')->groupBy('listing_id')->pluck('total', 'listing_id');
            $listings = $listings->map(function ($item) use ($service, $views, $saves) {
                if ($item['service'] === $service) $item['analytics'] = ['views7Days' => (int) ($views[$item['id']] ?? 0), 'currentSaves' => (int) ($saves[$item['id']] ?? 0)];
                return $item;
            });
        }
        return Inertia::render('Marketplace/Manage',['listings'=>$listings,'statuses'=>$statuses]);
    }
    public function form(Request $r, string $service, ?string $id = null) {
        $this->market->model($service);
        if (!$this->market->approved($r->user(),$service) && !$r->user()->is_admin) return redirect('/provider-applications/'.$service);
        $record = $id ? $this->market->find($service,$id) : null;
        if ($record) $this->market->authorize($r->user(),$record,$service);
        return Inertia::render('Marketplace/Form',['service'=>$service,'listing'=>$record ? $this->market->dto($record,$service) : null,'types'=>Marketplace::TYPES[$service], 'approved'=>$this->market->approved($r->user(),$service)]);
    }
    public function store(Request $r, string $service) {
        $record=$this->market->save($r->user(),$service,$this->market->validated($r,$service));
        return $r->is('api/*') ? response()->json(['data'=>$this->market->dto($record,$service)],201) : redirect('/my-listings')->with('success','Listing created.');
    }
    public function update(Request $r,string $service,string $id) {
        $record=$this->market->find($service,$id); $this->market->authorize($r->user(),$record,$service);
        $record=$this->market->save($r->user(),$service,$this->market->validated($r,$service,$record),$record);
        return $r->is('api/*') ? response()->json(['data'=>$this->market->dto($record,$service)]) : redirect('/my-listings')->with('success','Listing updated.');
    }
    public function availability(Request $r, string $service, string $id) {
        $data = $r->validate(['paused' => ['required', 'boolean']]);
        $record = $this->market->find($service, $id);
        $this->market->authorize($r->user(), $record, $service);
        abort_unless($r->user()->is_admin || $this->market->approved($r->user(), $service), 403);
        $record->availability_paused = $data['paused'];
        $record->save();
        if ($r->is('api/*')) return response()->json(['data' => $this->market->dto($record, $service)]);
        return back()->with('success', $data['paused'] ? 'Listing availability paused.' : 'Listing availability resumed.');
    }
    public function destroy(Request $r,string $service,string $id) {
        $record=$this->market->find($service,$id); $this->market->authorize($r->user(),$record,$service); $record->delete();
        return $r->is('api/*') ? response()->noContent() : back()->with('success','Listing deleted.');
    }
    public function upload(Request $r) {
        $r->validate(['photo'=>'required|image|mimes:jpg,jpeg,png,webp|max:5120|dimensions:max_width=6000,max_height=6000']);
        $path=$r->file('photo')->store('listing-photos/'.$r->user()->id,'public');
        if (!$path) throw \Illuminate\Validation\ValidationException::withMessages(['photo'=>'Could not save the photo. Please try again or contact support.']);
        return response()->json(['url'=>\App\Services\ListingPhotos::url($path)],201);
    }
}
