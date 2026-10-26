<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Public reads accept Sanctum tokens; invalid credentials never unlock details. */
class OptionalMarketplaceAuth
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->bearerToken()) {
            if (!Auth::guard('sanctum')->check()) {
                throw new AuthenticationException('Unauthenticated.', ['sanctum']);
            }
            Auth::shouldUse('sanctum');
        }

        return $next($request);
    }
}
