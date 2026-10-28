<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Services\Marketplace;
use Inertia\Inertia;
class ListingInspectionController extends Controller {
    public function spaces(Marketplace $market) { return $this->index($market,'spaces','Listings'); }
    public function vehicles(Marketplace $market) { return $this->index($market,'haul','Vehicles'); }
    public function services(Marketplace $market) { return $this->index($market,'raketeer','Services'); }
    private function index(Marketplace $market, string $service, string $page) {
        $relation=$service==='raketeer'?'provider':'owner';
        $query=$market->model($service)::with($relation);
        if ($service==='spaces') $query->where('category','space_rental');
        $listings=$query->latest()->paginate(15)->withQueryString()->through(function ($record) use ($market,$service,$relation) {
            return [...$market->dto($record,$service),
                'sourceStatus'=>$record->status instanceof \BackedEnum ? $record->status->value : $record->status,
                'updatedAt'=>$record->updated_at?->toIso8601String(),
                'adminOwner'=>$record->{$relation}?->only(['id','name','email']) ?? ['id'=>null,'name'=>'Owner unavailable','email'=>null],
            ];
        });
        return Inertia::render('Admin/'.$page.'/Index',['listings'=>$listings]);
    }
}
