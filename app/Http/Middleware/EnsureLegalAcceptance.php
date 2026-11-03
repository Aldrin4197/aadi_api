<?php
namespace App\Http\Middleware;
use App\Services\LegalDocuments;
use Closure;
use Illuminate\Http\Request;

class EnsureLegalAcceptance
{
    public function handle(Request $request, Closure $next) {
        $legal=app(LegalDocuments::class);
        // Public browsing, verification, logout and account deletion remain accessible.
        if (!$legal->enabled() || !$request->user() || $request->is('legal/*','logout','admin/*','auth/*','verify-email*','email/*','forgot-password','reset-password*')
            || ($request->isMethod('get') && $request->is('/','explore','profile','providers/*','media/*','avatars/*'))
            || ($request->isMethod('delete') && $request->is('profile'))) return $next($request);
        abort_unless($legal->published(),503,'Account agreements are being prepared. Please try again later.');
        $context='account';
        if ($legal->accepted((string)$request->user()->id,'account')) {
            if (!$request->is('provider-applications/*')) return $next($request);
            $context=(string)$request->segment(2);
            if (!in_array($context,['spaces','haul','raketeer'],true)) return $next($request);
            if ($legal->accepted((string)$request->user()->id,$context)) return $next($request);
        }
        if ($request->isMethod('get')) $request->session()->put('legal.return','/'.$request->path());
        if ($request->expectsJson() && !$request->header('X-Inertia')) return response()->json(['message'=>'Please review the current account agreement.','agreementUrl'=>'/legal/accept?context='.$context],428);
        return redirect('/legal/accept?context='.$context,303);
    }
}
