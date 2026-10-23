<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceInquiry;
use App\Services\Marketplace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InquiryController extends Controller
{
    public function __construct(private Marketplace $market) {}

    public function index(Request $request) {
        $request->validate(['page'=>'nullable|integer|min:1']);
        $inquiries = MarketplaceInquiry::forUser($request->user())->with(['customer','owner','latestMessage'])
            ->orderByDesc('updated_at')->orderByDesc('id')->paginate(20)->withQueryString();
        $data = ['data'=>$inquiries->getCollection()->map(fn ($item) => $item->summary($request->user()))->values(),
            'meta'=>['currentPage'=>$inquiries->currentPage(), 'lastPage'=>$inquiries->lastPage(), 'total'=>$inquiries->total()]];
        return $request->is('api/*') ? response()->json($data) : Inertia::render('Marketplace/Inquiries', ['inquiries'=>$data]);
    }

    public function store(Request $request, string $service, string $id) {
        $data = $request->validate(['message'=>'required|string|min:5|max:3000']);
        $inquiry = DB::transaction(function () use ($request, $service, $id, $data) {
            $listing = $this->market->model($service)::whereKey($id)->lockForUpdate()->firstOrFail();
            abort_unless($this->market->isPublished($listing, $service), 404);
            $ownerId = $listing->{$service === 'raketeer' ? 'provider_id' : 'owner_id'};
            abort_if((string) $ownerId === (string) $request->user()->id, 403, 'You cannot inquire about your own listing.');
            $inquiry = MarketplaceInquiry::firstOrCreate([
                'service'=>$service, 'listing_id'=>$listing->id, 'customer_id'=>$request->user()->id,
            ], ['owner_id'=>$ownerId, 'listing_title'=>$listing->title]);
            // Ownership changes must not send a new inquiry to a former owner.
            abort_unless((string) $inquiry->owner_id === (string) $ownerId, 409, 'The listing owner has changed.');
            $inquiry->messages()->create(['sender_id'=>$request->user()->id, 'body'=>$data['message']]);
            $inquiry->touch();
            return $inquiry;
        });
        $inquiry->load(['customer','owner','latestMessage']);
        return $request->is('api/*')
            ? response()->json(['data'=>$inquiry->summary($request->user())], 201)
            : redirect('/inquiries/'.$inquiry->id)->with('success', 'Your inquiry was sent.');
    }

    public function show(Request $request, string $id) {
        $request->validate(['page'=>'nullable|integer|min:1']);
        $inquiry = MarketplaceInquiry::forUser($request->user())->with(['customer','owner','latestMessage'])->findOrFail($id);
        $messages = $inquiry->messages()->orderByDesc('id')->paginate(30)->withQueryString();
        $listing = $this->market->model($inquiry->service)::find($inquiry->listing_id);
        $data = ['inquiry'=>$inquiry->summary($request->user()),
            'listingAvailable'=>$listing && $this->market->isPublished($listing, $inquiry->service),
            'messages'=>['data'=>$messages->getCollection()->reverse()->values()->map->toData(),
                'meta'=>['currentPage'=>$messages->currentPage(),'lastPage'=>$messages->lastPage(),'total'=>$messages->total()]]];
        return $request->is('api/*') ? response()->json(['data'=>$data]) : Inertia::render('Marketplace/Inquiry', $data);
    }

    public function reply(Request $request, string $id) {
        $inquiry = MarketplaceInquiry::forUser($request->user())->findOrFail($id);
        $data = $request->validate(['message'=>'required|string|min:1|max:3000']);
        $message = DB::transaction(function () use ($request, $inquiry, $data) {
            $message = $inquiry->messages()->create(['sender_id'=>$request->user()->id, 'body'=>$data['message']]);
            $inquiry->touch();
            return $message;
        });
        return $request->is('api/*') ? response()->json(['data'=>$message->toData()], 201)
            : redirect('/inquiries/'.$inquiry->id)->with('success', 'Reply sent.');
    }
}
