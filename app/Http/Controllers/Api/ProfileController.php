<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\Request;
class ProfileController extends Controller {
    public function show(Request $request) {
        return response()->json(['data'=>$request->user()->only(['id','name','email','phone','bio','city','address','avatar_url','email_verified_at'])])->header('Cache-Control','private, no-store');
    }
    public function update(ProfileUpdateRequest $request) {
        $user = $request->user();$user->fill($request->validated());
        if ($user->isDirty('email')) $user->email_verified_at = null;
        $user->save();
        return $this->show($request);
    }
}
