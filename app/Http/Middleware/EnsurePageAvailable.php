<?php

namespace App\Http\Middleware;

use App\Services\PageAvailability;
use App\Support\ErrorPage;
use Closure;
use Illuminate\Http\Request;

class EnsurePageAvailable
{
    public function handle(Request $request, Closure $next, string $page)
    {
        if (!app(PageAvailability::class)->enabled($page)) {
            return ErrorPage::response($request, 503, true);
        }

        return $next($request);
    }
}
