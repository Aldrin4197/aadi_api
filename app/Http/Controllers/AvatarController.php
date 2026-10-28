<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
class AvatarController extends Controller {
    public function store(Request $request) {
        $request->validate(['avatar'=>'required|image|mimes:jpg,jpeg,png,webp|max:5120|dimensions:max_width=6000,max_height=6000']);
        $path = $request->file('avatar')->store('avatars/'.$request->user()->id, 'public');
        abort_unless($path, 500, 'Could not save the avatar.');
        try {
            $old = DB::transaction(function () use ($request, $path) {
                $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                $old = $user->avatar_path;
                $user->forceFill(['avatar_path'=>$path,'avatar_url'=>Storage::disk('public')->url($path)])->save();
                return $old;
            });
        } catch (\Throwable $e) { Storage::disk('public')->delete($path); throw $e; }
        if ($old) Storage::disk('public')->delete($old);
        return $request->is('api/*') ? response()->json(['avatarUrl'=>Storage::disk('public')->url($path)]) : back()->with('success','Profile photo updated.');
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
