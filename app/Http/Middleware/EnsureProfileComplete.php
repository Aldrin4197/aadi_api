<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureProfileComplete
{
    public function handle(Request $request, Closure $next)
    {
        $middleware = $request->route()?->gatherMiddleware() ?? [];
        $api = $request->is('api/*');
        $protected = in_array($api ? 'auth:sanctum' : 'auth', $middleware, true);
        $user = $api ? Auth::guard('sanctum')->user() : $request->user();

        if (!$protected || !$user || $user->hasCompleteProfile()) {
            return $next($request);
        }

        if ($api && $request->is('api/auth/logout', 'api/me', 'api/v1/profile', 'api/v1/email-verification*')) {
            return $next($request);
        }
        if (!$api && $request->is('profile', 'settings', 'settings/*', 'password', 'confirm-password', 'logout', 'verify-email*', 'email/*', 'legal/*')) {
            return $next($request);
        }

        if ($api || ($request->expectsJson() && !$request->header('X-Inertia'))) {
            return response()->json(['message' => 'Complete your profile before using this feature.', 'profileUrl' => $api ? '/api/v1/profile' : '/profile'], 428);
        }

        return redirect()->route('profile.edit', status: 303);
    }
}
