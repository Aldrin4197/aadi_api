<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\ListingConversation;
use App\Models\ListingMessage;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\Marketplace;
use App\Services\RealtimePublisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ListingConversationController extends Controller
{
    public function index(Request $request)
    {
        $data = [
            'conversations' => $this->conversationsFor($request->user()),
        ];

        return $request->wantsJson()
            ? response()->json($data)->header('Cache-Control', 'private, no-store')
            : Inertia::render('Account/Messages', $data);
    }

    public function show(Request $request, ListingConversation $conversation, Marketplace $market)
    {
        $this->authorizeParticipant($request, $conversation);
        $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'conversation_page' => ['sometimes', 'integer', 'min:1'],
        ]);
        $conversation->load(['customer:id,name,avatar_path,avatar_url', 'provider:id,name,avatar_path,avatar_url']);
        $other = $conversation->customer_id === $request->user()->id ? $conversation->provider : $conversation->customer;
        $listing = $market->model($conversation->service)::find($conversation->listing_id);
        $canViewListing = $listing && ($market->listingStatus($listing, $conversation->service) === 'active'
            || $request->user()->is_admin || $market->owns($request->user(), $listing, $conversation->service));
        $messages = $conversation->messages()->orderByDesc('id')->paginate(40);
        $messages->setCollection($messages->getCollection()->reverse()->values()->map(fn (ListingMessage $message) => [
            'id' => $message->id, 'body' => $message->body, 'mine' => $message->sender_id === $request->user()->id,
            'createdAt' => $message->created_at->toIso8601String(),
        ]));

        $data = [
            'conversations' => $this->conversationsFor($request->user(), 'conversation_page'),
            'conversation' => [
                'id' => $conversation->id, 'listingTitle' => $conversation->listing_title,
                'listingUrl' => $canViewListing ? "/offers/{$conversation->service}/{$conversation->listing_id}" : null,
                'other' => ['id' => $other->id, 'name' => $other->name, 'avatar_url' => $other->avatar_url],
            ],
            'messages' => $messages,
        ];

        return $request->wantsJson()
            ? response()->json($data)->header('Cache-Control', 'private, no-store')
            : Inertia::render('Account/MessageThread', $data);
    }

    public function start(Request $request, Marketplace $market, string $service, string $id)
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $listing = $market->find($service, $id);
        $provider = User::findOrFail($listing->{$market->ownerColumn($service)});
        if ($market->listingStatus($listing, $service) !== 'active' || $listing->availability_paused || $listing->is_sample || $provider->isRestricted()) {
            throw ValidationException::withMessages(['body' => 'This listing is not available for new conversations.']);
        }
        abort_if($market->owns($request->user(), $listing, $service), 403, 'You cannot message yourself.');

        $conversation = DB::transaction(function () use ($request, $data, $listing, $provider, $service) {
            $conversation = ListingConversation::firstOrCreate([
                'service' => $service, 'listing_id' => $listing->id,
                'customer_id' => $request->user()->id, 'provider_id' => $provider->id,
            ], ['listing_title' => $listing->title]);
            $this->sendMessage($conversation, $request->user(), $provider, $data['body']);
            return $conversation;
        });

        return redirect("/messages/{$conversation->id}");
    }

    public function reply(Request $request, ListingConversation $conversation)
    {
        $this->authorizeParticipant($request, $conversation);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $recipient = User::findOrFail($conversation->customer_id === $request->user()->id
            ? $conversation->provider_id : $conversation->customer_id);
        if ($recipient->isRestricted()) {
            throw ValidationException::withMessages(['body' => 'This account cannot receive messages right now.']);
        }
        DB::transaction(fn () => $this->sendMessage($conversation, $request->user(), $recipient, $data['body']));

        return redirect("/messages/{$conversation->id}");
    }

    private function authorizeParticipant(Request $request, ListingConversation $conversation): void
    {
        abort_unless(in_array($request->user()->id, [$conversation->customer_id, $conversation->provider_id], true), 403);
    }

    private function conversationsFor(User $user, string $pageName = 'page')
    {
        return ListingConversation::query()
            ->where(fn ($query) => $query->where('customer_id', $user->id)->orWhere('provider_id', $user->id))
            ->with(['customer:id,name,avatar_path,avatar_url', 'provider:id,name,avatar_path,avatar_url'])
            ->addSelect([
                'latest_body' => ListingMessage::select('body')
                    ->whereColumn('listing_conversation_id', 'listing_conversations.id')->latest('id')->limit(1),
                'latest_message_id' => ListingMessage::select('id')
                    ->whereColumn('listing_conversation_id', 'listing_conversations.id')->latest('id')->limit(1),
            ])
            ->orderByDesc('latest_message_id')
            ->paginate(20, ['*'], $pageName)
            ->through(function (ListingConversation $conversation) use ($user) {
                $other = $conversation->customer_id === $user->id ? $conversation->provider : $conversation->customer;
                return [
                    'id' => $conversation->id, 'listingTitle' => $conversation->listing_title,
                    'other' => ['id' => $other->id, 'name' => $other->name, 'avatar_url' => $other->avatar_url],
                    'latestBody' => $conversation->latest_body, 'updatedAt' => $conversation->updated_at->toIso8601String(),
                ];
            })
            ->withQueryString();
    }

    private function sendMessage(ListingConversation $conversation, User $sender, User $recipient, string $body): void
    {
        $conversation->messages()->create(['sender_id' => $sender->id, 'body' => $body]);
        $conversation->touch();
        $recipient->notify(new AppNotification('message', 'New listing message', $sender->name.' sent a message about '.$conversation->listing_title.'.', "/messages/{$conversation->id}"));
        app(RealtimePublisher::class)->publish((string) $sender->id, 'message');
    }
}
