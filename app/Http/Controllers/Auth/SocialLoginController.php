<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SocialLogin;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SocialLoginController extends Controller
{
    public function redirect(Request $request, string $provider, SocialLogin $social)
    {
        abort_unless($social->enabled($provider), 404);
        $state = Str::random(64);
        $verifier = Str::random(96);
        $request->session()->put('social_login', ['state'=>$state, 'verifier'=>$verifier, 'provider'=>$provider, 'expires'=>time()+600, 'user_id'=>$request->user()?->id]);
        return redirect()->away($social->authorizeUrl($provider, $state, $verifier));
    }

    public function connect(Request $request, string $provider, SocialLogin $social)
    {
        return $this->redirect($request, $provider, $social);
    }

    public function callback(Request $request, string $provider, SocialLogin $social)
    {
        abort_unless($social->enabled($provider), 404);
        $destination = $request->user() ? '/profile' : '/login';
        $flow = $request->session()->pull('social_login');
        if (!is_array($flow) || ($flow['provider'] ?? '') !== $provider || ($flow['expires'] ?? 0) < time() ||
            ($flow['user_id'] ?? null) !== $request->user()?->id ||
            !is_string($request->query('state')) || !hash_equals($flow['state'], $request->query('state'))) {
            return redirect($destination)->with('status', 'This sign-in request expired. Please try again.');
        }
        if ($request->has('error') || !is_string($request->query('code'))) {
            return redirect($destination)->with('status', 'Social sign-in was cancelled. You can try again or use your email.');
        }
        try {
            $identity = $social->identity($provider, $request->query('code'), $flow['verifier']);
            [$user, $created] = DB::transaction(function () use ($provider, $identity, $request, $flow) {
                $account = DB::table('social_accounts')->where('provider', $provider)->where('provider_id', $identity['id'])->first();
                if ($flow['user_id'] ?? null) {
                    $user = $request->user();
                    if ($account && $account->user_id !== $user->id) {
                        throw ValidationException::withMessages(['social'=>'That social account is already connected to a different Aadi account.']);
                    }
                    $existing = DB::table('social_accounts')->where('user_id', $user->id)->where('provider', $provider)->first();
                    if ($existing && $existing->provider_id !== $identity['id']) {
                        throw ValidationException::withMessages(['social'=>'A different account from this provider is already connected.']);
                    }
                    if (!$account) DB::table('social_accounts')->insert(['user_id'=>$user->id, 'provider'=>$provider, 'provider_id'=>$identity['id'], 'created_at'=>now(), 'updated_at'=>now()]);
                    return [$user, false];
                }
                if ($account) return [User::findOrFail($account->user_id), false];
                if (!filter_var($identity['email'], FILTER_VALIDATE_EMAIL) || strlen($identity['email']) > 255) {
                    throw ValidationException::withMessages(['social'=>'The provider did not share an email address. Please register with your email instead.']);
                }
                // Never attach an identity to an existing account using email alone.
                if (User::whereRaw('LOWER(email) = ?', [$identity['email']])->exists()) {
                    throw ValidationException::withMessages(['social'=>'An Aadi account already uses this email. Sign in with your existing password, then connect this provider in Profile & settings. You can reset your password if needed.']);
                }
                $user = User::create(['name'=>$identity['name'], 'email'=>$identity['email'], 'password'=>Str::random(64)]);
                if ($identity['verified']) $user->markEmailAsVerified();
                DB::table('social_accounts')->insert(['user_id'=>$user->id, 'provider'=>$provider, 'provider_id'=>$identity['id'], 'created_at'=>now(), 'updated_at'=>now()]);
                return [$user, true];
            });
        } catch (ValidationException $exception) {
            return redirect($destination)->with('status', collect($exception->errors())->flatten()->first());
        } catch (\Throwable $exception) {
            // OAuth responses may contain credentials: do not log exception bodies or URLs.
            return redirect($destination)->with('status', 'Social sign-in could not be completed. Please try again or use email sign-in.');
        }
        if ($flow['user_id'] ?? null) return redirect('/profile')->with('status', 'Social login connected.');
        Auth::login($user);
        $request->session()->regenerate();
        if ($created) {
            try { event(new Registered($user)); }
            catch (\Throwable $exception) { return redirect()->route('verification.notice')->with('status', 'delivery-failed'); }
        }
        return $user->hasVerifiedEmail() ? redirect()->intended('/dashboard') : redirect()->route('verification.notice');
    }
}
