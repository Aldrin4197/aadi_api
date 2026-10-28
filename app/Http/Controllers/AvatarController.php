<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
class AvatarController extends Controller {
    public function show(User $user, string $filename) {
        $path = $user->avatar_path;
        abort_unless($path && basename($path) === $filename && str_starts_with($path, 'avatars/'.$user->id.'/'), 404);
        abort_unless(Storage::disk('public')->exists($path), 404);
        return Storage::disk('public')->response($path, null, ['Cache-Control'=>'public, max-age=3600', 'X-Content-Type-Options'=>'nosniff']);
    }
    public function store(Request $request) {
        $request->validate(['avatar'=>'required|image|mimes:jpg,jpeg,png,webp|max:5120|dimensions:max_width=6000,max_height=6000']);
        $path = $request->file('avatar')->store('avatars/'.$request->user()->id, 'public');
        if (!$path) throw \Illuminate\Validation\ValidationException::withMessages(['avatar'=>'Could not save your photo. Please try again or contact support.']);
        try {
            $old = DB::transaction(function () use ($request, $path) {
                $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                $old = $user->avatar_path;
                $user->forceFill(['avatar_path'=>$path,'avatar_url'=>route('avatars.show', ['user'=>$user->id, 'filename'=>basename($path)])])->save();
                return $old;
            });
        } catch (\Throwable $e) { Storage::disk('public')->delete($path); throw $e; }
        if ($old) Storage::disk('public')->delete($old);
        return $request->is('api/*') ? response()->json(['avatarUrl'=>$request->user()->fresh()->avatar_url]) : back()->with('success','Profile photo updated.');
    }
    public function destroy(Request $request) {
        $old = DB::transaction(function () use ($request) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $old = $user->avatar_path;
            $user->forceFill(['avatar_path'=>null,'avatar_url'=>null])->save();
            return $old;
        });
        if ($old) Storage::disk('public')->delete($old);
        return $request->is('api/*') ? response()->noContent() : back()->with('success','Profile photo removed.');
    }
}
