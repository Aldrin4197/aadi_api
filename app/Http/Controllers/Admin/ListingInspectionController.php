<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Marketplace;
use App\Support\AdminTable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ListingInspectionController extends Controller {
    /** Per-category column map and the statuses the marketplace can store. */
    private const SCHEMA = [
        'spaces' => ['type'=>'type', 'price'=>'price', 'area'=>['city','address'], 'owner'=>'owner_id', 'relation'=>'owner', 'role'=>'landlord_status', 'live'=>'active', 'statuses'=>['draft','active','reserved','rented','archived']],
        'haul' => ['type'=>'vehicle_type', 'price'=>'price_per_day', 'area'=>['location'], 'owner'=>'owner_id', 'relation'=>'owner', 'role'=>'vehicle_owner_status', 'live'=>'available', 'statuses'=>['available','inactive','rented','maintenance']],
        'raketeer' => ['type'=>'service_type', 'price'=>'price_amount', 'area'=>['service_area'], 'owner'=>'provider_id', 'relation'=>'provider', 'role'=>'service_provider_status', 'live'=>'available', 'statuses'=>['available','inactive','busy','rented']],
    ];
    public function spaces(Request $request, Marketplace $market) { return $this->index($request,$market,'spaces','Listings'); }
    public function vehicles(Request $request, Marketplace $market) { return $this->index($request,$market,'haul','Vehicles'); }
    public function services(Request $request, Marketplace $market) { return $this->index($request,$market,'raketeer','Services'); }
    private function index(Request $request, Marketplace $market, string $service, string $page) {
        $schema=self::SCHEMA[$service]; $relation=$schema['relation'];
        $model=$market->model($service);
        $base=fn()=>$service==='spaces' ? $model::where('category','space_rental') : $model::query();
        $existing=fn(string $column)=>$base()->toBase()->distinct()->pluck($column)->filter()->map(fn($v)=>$v instanceof \BackedEnum ? $v->value : (string) $v);
        $statuses=collect($schema['statuses'])->merge($existing('status'))->unique()->values();
        $types=collect(Marketplace::TYPES[$service])->merge($existing($schema['type']))->unique()->values();
        $filters=$request->validate([
            'q'=>'nullable|string|max:150',
            'status'=>['nullable',Rule::in($statuses->all())],
            'type'=>['nullable',Rule::in($types->all())],
            'minPrice'=>'nullable|numeric|min:0','maxPrice'=>'nullable|numeric|min:0',
            'provider'=>['nullable',Rule::in(['approved','unapproved'])],
        ]);
        $query=$base()->with($relation);
        if (!empty($filters['q'])) {
            $like=AdminTable::like($filters['q']);
            $query->where(function($q) use ($like,$schema,$relation){
                $q->where('title','like',$like);
                foreach ($schema['area'] as $column) $q->orWhere($column,'like',$like);
                $q->orWhereHas($relation,fn($u)=>$u->where('name','like',$like)->orWhere('email','like',$like));
            });
        }
        if (!empty($filters['status'])) $query->where('status',$filters['status']);
        if (!empty($filters['type'])) $query->where($schema['type'],$filters['type']);
        if (isset($filters['minPrice'])) $query->where($schema['price'],'>=',$filters['minPrice']);
        if (isset($filters['maxPrice'])) $query->where($schema['price'],'<=',$filters['maxPrice']);
        if (($filters['provider'] ?? null)==='approved') $query->whereHas($relation,fn($u)=>$u->where($schema['role'],'approved'));
        if (($filters['provider'] ?? null)==='unapproved') $query->whereDoesntHave($relation,fn($u)=>$u->where($schema['role'],'approved'));
        $table=$query->getModel()->getTable();
        $sort=AdminTable::sort($request,$query,[
            'title'=>'title','type'=>$schema['type'],'price'=>$schema['price'],'status'=>'status',
            'owner'=>fn($q,$dir)=>$q->orderBy(User::select('name')->whereColumn('users.id',$table.'.'.$schema['owner']),$dir),
            'created'=>'created_at','updated'=>'updated_at',
        ],'created');
        $listings=$query->paginate(15)->withQueryString()->through(function ($record) use ($market,$service,$relation) {
            return [...$market->dto($record,$service),
                'sourceStatus'=>$record->status instanceof \BackedEnum ? $record->status->value : $record->status,
                'updatedAt'=>$record->updated_at?->toIso8601String(),
                'adminOwner'=>$record->{$relation}?->only(['id','name','email']) ?? ['id'=>null,'name'=>'Owner unavailable','email'=>null],
            ];
        });
        return Inertia::render('Admin/'.$page.'/Index',[
            'listings'=>$listings,'filters'=>$filters,...$sort,
            'options'=>['statuses'=>$statuses,'types'=>$types],
            'totals'=>['all'=>$base()->count(),'live'=>$base()->where('status',$schema['live'])->count()],
            'service'=>$service,
        ]);
    }
}
