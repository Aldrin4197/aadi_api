<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAccountAvailable
{
    public function handle(Request $request, Closure $next)
    {
        $api = $request->is('api/*');
        $user = $api ? Auth::guard('sanctum')->user() : $request->user();
        if ($user && $user->isRestricted()) {
            $message = $user->isScheduledForDeletion()
                ? 'This account is deactivated and scheduled for permanent deletion on '.$user->deletion_scheduled_for->format('F j, Y').'. Contact info@aadirentals.com to keep it.'
                : 'This account is currently restricted. Please contact support.';
            if ($api || $request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->with('status', $message);
        }
        return $next($request);
    }
}
