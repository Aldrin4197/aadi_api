<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureApprovedProvider
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->isApprovedProvider(), 403, 'An approved provider role is required.');

        return $next($request);
    }
}
