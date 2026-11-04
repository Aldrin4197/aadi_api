<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/** The signed-in user's own notification inbox, used by the navbar bell. */
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $items = $user->notifications()->latest()->limit(20)->get()->map(fn ($n) => [
            'id' => $n->id,
            'kind' => $n->data['kind'] ?? 'info',
            'title' => $n->data['title'] ?? '',
            'text' => $n->data['text'] ?? '',
            'href' => $n->data['href'] ?? null,
            'tone' => $n->data['tone'] ?? 'info',
            'read' => $n->read_at !== null,
            'createdAt' => $n->created_at->toIso8601String(),
        ])->values();

        return $this->respond(['unread' => $user->unreadNotifications()->count(), 'items' => $items]);
    }

    public function count(Request $request)
    {
        return $this->respond(['unread' => $request->user()->unreadNotifications()->count()]);
    }

    public function read(Request $request, string $id)
    {
        $request->user()->notifications()->whereKey($id)->firstOrFail()->markAsRead();

        return $this->count($request);
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return $this->count($request);
    }

    private function respond(array $data)
    {
        return response()->json($data)->header('Cache-Control', 'private, no-store');
    }
}
