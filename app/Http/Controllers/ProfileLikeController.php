<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileLikeController extends Controller
{
    public function index(Request $request, User $user)
    {
        $request->validate(['page' => ['sometimes', 'integer', 'min:1']]);
        $likes = User::query()->join('profile_likes', 'users.id', '=', 'profile_likes.user_id')
            ->where('profile_likes.profile_id', $user->id)
            ->orderByDesc('profile_likes.created_at')->orderBy('users.id')
            ->select(['users.id', 'users.name', 'users.avatar_path', 'users.avatar_url'])
            ->paginate(20)->through(fn ($liker) => [
                'id' => (string) $liker->id, 'name' => $liker->name, 'avatar_url' => $liker->avatar_url,
            ]);

        return response()->json($likes)->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, User $user)
    {
        abort_if($request->user()->is($user), 403, 'You cannot like your own profile.');
        abort_if($request->user()->is_admin, 403, 'Administrators cannot like profiles.');
        DB::table('profile_likes')->upsert([[
            'profile_id' => $user->id, 'user_id' => $request->user()->id, 'created_at' => now(),
        ]], ['profile_id', 'user_id'], ['user_id']);

        return $request->is('api/*') ? response()->noContent() : back(303);
    }

    public function destroy(Request $request, User $user)
    {
        abort_if($request->user()->is($user), 403, 'You cannot like your own profile.');
        DB::table('profile_likes')->where('profile_id', $user->id)->where('user_id', $request->user()->id)->delete();

        return $request->is('api/*') ? response()->noContent() : back(303);
    }
}
