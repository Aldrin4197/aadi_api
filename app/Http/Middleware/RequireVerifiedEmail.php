<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

// Provider roles are reviewed by humans, so the applicant's email must be confirmed first.
class RequireVerifiedEmail {
    public const MESSAGE = 'Confirm your email address before applying for a provider role. We sent a link when you registered, and you can request a new one from the banner at the top of the page.';

    public function handle(Request $request, Closure $next) {
        if ($request->user() && ! $request->user()->hasVerifiedEmail()) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => self::MESSAGE, 'code' => 'email_unverified'], 403);
            }
            return back()->withErrors(['application' => self::MESSAGE]);
        }
        return $next($request);
    }
}
