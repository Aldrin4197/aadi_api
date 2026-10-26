<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PrivateMarketplaceResponse
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->setVary(['Authorization', 'Cookie'], false);
        return $response;
    }
}
