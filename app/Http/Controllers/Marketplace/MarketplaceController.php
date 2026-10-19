<?php
namespace App\Http\Controllers\Marketplace;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Marketplace;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MarketplaceController extends Controller {
    public function __construct(private Marketplace $market) {}
    private function filters(Request $r): array {
        return $r->validate(['service'=>['sometimes',Rule::in(array_keys(Marketplace::TYPES))], 'q'=>'nullable|string|max:150', 'type'=>'nullable|string|max:80',
            'lat'=>'nullable|required_with:lng|numeric|between:-90,90','lng'=>'nullable|required_with:lat|numeric|between:-180,180',
            'radiusKm'=>'nullable|numeric|min:1|max:200','minPrice'=>'nullable|numeric|min:0','maxPrice'=>['nullable','numeric','min:0',...($r->filled('minPrice') ? ['gte:minPrice'] : [])],'page'=>'nullable|integer|min:1']);
    }
    public function index(Request $r) {
        $filters = $this->filters($r);
        return Inertia::render('Marketplace/Explore', ['results'=>$this->market->search($filters),'filters'=>(object)$filters,'types'=>Marketplace::TYPES]);
    }
    public function search(Request $r) { return response()->json($this->market->search($this->filters($r))); }
    public function show(Request $r, string $service, string $id) {
        $record = $this->market->find($service,$id); $item = $this->market->dto($record,$service);
        if ($item['status'] !== 'active') { abort_unless($r->user(),404); $this->market->authorize($r->user(),$record,$service); }
        return $r->is('api/*') ? response()->json(['data'=>$item]) : Inertia::render('Marketplace/Show',['listing'=>$item]);
    }
    public function manage(Request $r) {
        $listings = collect(); $statuses = [];
        foreach (array_keys(Marketplace::TYPES) as $service) {
            $statuses[$service] = $r->user()->{$this->market->statusField($service)}?->value ?? 'none';
            $records = $this->market->model($service)::where($service === 'raketeer' ? 'provider_id' : 'owner_id',$r->user()->id)->latest()->get();
            foreach ($records as $record) $listings->push($this->market->dto($record,$service));
        }
        return Inertia::render('Marketplace/Manage',['listings'=>$listings,'statuses'=>$statuses]);
    }
    public function form(Request $r, string $service, ?string $id = null) {
        $this->market->model($service); $record = $id ? $this->market->find($service,$id) : null;
        if ($record) $this->market->authorize($r->user(),$record,$service);
        return Inertia::render('Marketplace/Form',['service'=>$service,'listing'=>$record ? $this->market->dto($record,$service) : null,'types'=>Marketplace::TYPES[$service], 'approved'=>$this->market->approved($r->user(),$service)]);
    }
    public function store(Request $r, string $service) {
        $record=$this->market->save($r->user(),$service,$this->market->validated($r,$service));
        return $r->is('api/*') ? response()->json(['data'=>$this->market->dto($record,$service)],201) : redirect('/my-listings')->with('success','Listing created.');
    }
    public function update(Request $r,string $service,string $id) {
        $record=$this->market->find($service,$id); $this->market->authorize($r->user(),$record,$service);
        $record=$this->market->save($r->user(),$service,$this->market->validated($r,$service),$record);
        return $r->is('api/*') ? response()->json(['data'=>$this->market->dto($record,$service)]) : redirect('/my-listings')->with('success','Listing updated.');
    }
    public function destroy(Request $r,string $service,string $id) {
        $record=$this->market->find($service,$id); $this->market->authorize($r->user(),$record,$service); $record->delete();
        return $r->is('api/*') ? response()->noContent() : back()->with('success','Listing deleted.');
    }
    public function upload(Request $r) {
        $r->validate(['photo'=>'required|image|mimes:jpg,jpeg,png,webp|max:5120']);
        $path=$r->file('photo')->store('listing-photos/'.$r->user()->id,'public');
        return response()->json(['url'=>\Illuminate\Support\Facades\Storage::disk('public')->url($path)],201);
    }
    public function apply(Request $r,string $service) {
        $field=$this->market->statusField($service);
        abort_if(in_array($r->user()->$field?->value,['pending','approved']),422,'Application already submitted.');
        $r->user()->update([$field=>'pending']);
        return back()->with('success','Application submitted. An administrator will review your account.');
    }
    public function approvals() {
        return Inertia::render('Marketplace/Approvals',['users'=>User::where(fn($q)=>$q->where('landlord_status','pending')->orWhere('vehicle_owner_status','pending')->orWhere('service_provider_status','pending'))->get(['id','name','email','landlord_status','vehicle_owner_status','service_provider_status'])]);
    }
    public function decide(Request $r,string $id,string $service) {
        $data=$r->validate(['status'=>['required',Rule::in(['approved','rejected'])]]);
        $user=User::findOrFail($id); $field=$this->market->statusField($service);
        abort_unless($user->$field?->value === 'pending',422,'No pending application.');
        $user->update([$field=>$data['status']]); return back()->with('success','Application reviewed.');
    }
}
