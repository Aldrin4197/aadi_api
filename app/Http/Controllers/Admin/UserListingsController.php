<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Marketplace;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
class UserListingsController extends Controller {
    public function users(Request $request) {
        $filters=$request->validate(['q'=>'nullable|string|max:150']);
        $query=User::query()->select(['id','name','email','avatar_path','avatar_url','landlord_status','vehicle_owner_status','service_provider_status','created_at'])
            ->withCount(['listings'=>fn($q)=>$q->where('category','space_rental'),'vehicleListings','serviceListings']);
        if (!empty($filters['q'])) $query->where(fn($q)=>$q->where('name','like','%'.$filters['q'].'%')->orWhere('email','like','%'.$filters['q'].'%'));
        return Inertia::render('Admin/Users/Index',['users'=>$query->latest()->paginate(15)->withQueryString(),'filters'=>$filters]);
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
