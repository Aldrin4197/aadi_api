<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;

class UserController extends Controller
{
    public function show(string $id)
    {
        $user = User::with('badges')->find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Return public profile (you can customize which fields to hide)
        return new UserResource($user);
    }
}
