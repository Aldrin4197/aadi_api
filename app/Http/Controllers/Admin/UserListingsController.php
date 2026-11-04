<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Marketplace;
use App\Support\AdminTable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
class UserListingsController extends Controller {
    private const ROLE_FIELDS = ['spaces'=>'landlord_status','haul'=>'vehicle_owner_status','raketeer'=>'service_provider_status'];
    public function users(Request $request) {
        $filters=$request->validate([
            'q'=>'nullable|string|max:150',
            'role'=>['nullable',Rule::in(array_keys(self::ROLE_FIELDS))],
            'roleStatus'=>['nullable',Rule::in(['approved','pending','rejected','none'])],
            'listings'=>['nullable',Rule::in(['with','without'])],
            'email'=>['nullable',Rule::in(['verified','unverified'])],
        ]);
        $query=User::query()->select(['id','name','email','email_verified_at','is_admin','avatar_path','avatar_url','landlord_status','vehicle_owner_status','service_provider_status','created_at'])
            ->withCount(['listings'=>fn($q)=>$q->where('category','space_rental'),'vehicleListings','serviceListings']);
        if (!empty($filters['q'])) { $like=AdminTable::like($filters['q']); $query->where(fn($q)=>$q->where('name','like',$like)->orWhere('email','like',$like)); }
        $fields=!empty($filters['role']) ? [self::ROLE_FIELDS[$filters['role']]] : array_values(self::ROLE_FIELDS);
        $status=$filters['roleStatus'] ?? null;
        if ($status==='none') foreach ($fields as $field) $query->where(fn($q)=>$q->where($field,'none')->orWhereNull($field));
        elseif ($status) $query->where(function($q) use ($fields,$status){ foreach ($fields as $field) $q->orWhere($field,$status); });
        elseif (!empty($filters['role'])) $query->where($fields[0],'!=','none')->whereNotNull($fields[0]);
        $total='(listings_count + vehicle_listings_count + service_listings_count)';
        $spaces=fn($q)=>$q->where('category','space_rental');
        if (($filters['listings'] ?? null)==='with') $query->where(fn($q)=>$q->whereHas('listings',$spaces)->orHas('vehicleListings')->orHas('serviceListings'));
        if (($filters['listings'] ?? null)==='without') $query->whereDoesntHave('listings',$spaces)->doesntHave('vehicleListings')->doesntHave('serviceListings');
        if (($filters['email'] ?? null)==='verified') $query->whereNotNull('email_verified_at');
        if (($filters['email'] ?? null)==='unverified') $query->whereNull('email_verified_at');
        $approved='(CASE WHEN landlord_status=\'approved\' THEN 1 ELSE 0 END + CASE WHEN vehicle_owner_status=\'approved\' THEN 1 ELSE 0 END + CASE WHEN service_provider_status=\'approved\' THEN 1 ELSE 0 END)';
        $sort=AdminTable::sort($request,$query,[
            'name'=>'name','email'=>'email','joined'=>'created_at',
            'listings'=>fn($q,$dir)=>$q->orderByRaw("$total $dir"),
            'roles'=>fn($q,$dir)=>$q->orderByRaw("$approved $dir"),
        ],'joined');
        $summary=[
            'total'=>User::count(),
            'providers'=>User::where(fn($q)=>$q->where('landlord_status','approved')->orWhere('vehicle_owner_status','approved')->orWhere('service_provider_status','approved'))->count(),
            'pending'=>User::where(fn($q)=>$q->where('landlord_status','pending')->orWhere('vehicle_owner_status','pending')->orWhere('service_provider_status','pending'))->count(),
        ];
        return Inertia::render('Admin/Users/Index',['users'=>$query->paginate(15)->withQueryString(),'filters'=>$filters,...$sort,'summary'=>$summary]);
    }
    public function show(Request $request, User $user, Marketplace $market) {
        $filters=$request->validate(['service'=>['sometimes',Rule::in(array_keys(Marketplace::TYPES))],'page'=>'sometimes|integer|min:1']);
        $service=$filters['service'] ?? 'spaces';$counts=[];
        foreach (array_keys(Marketplace::TYPES) as $type) {
            $q=$market->model($type)::where($type==='raketeer'?'provider_id':'owner_id',$user->id);
            if ($type==='spaces') $q->where('category','space_rental');
            $counts[$type]=$q->count();
        }
        $query=$market->model($service)::where($service==='raketeer'?'provider_id':'owner_id',$user->id)->with($service==='raketeer'?'provider':'owner');
        if ($service==='spaces') $query->where('category','space_rental');
        $results=$query->latest()->paginate(12)->withQueryString()->through(fn($record)=>[
            ...$market->dto($record,$service),
            'sourceStatus'=>$record->status instanceof \BackedEnum ? $record->status->value : $record->status,
            'updatedAt'=>$record->updated_at?->toIso8601String(),
        ]);
        return Inertia::render('Admin/Users/Listings',[
            'owner'=>$user->only(['id','name','email','avatar_url','landlord_status','vehicle_owner_status','service_provider_status']),
            'results'=>$results,'counts'=>$counts,'service'=>$service,
        ]);
    }
}
