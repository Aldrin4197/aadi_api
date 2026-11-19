<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteAccountRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Services\AccountDeletionService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'profile' => $request->user()->only(['name','email','phone','bio','city','address','birth_date','sex_assigned_at_birth','avatar_url','email_verified_at']),
            'profileIncomplete' => !$request->user()->hasCompleteProfile(),
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ])->toResponse($request)->header('Cache-Control', 'private, no-store');
    }

    public function settings(Request $request): InertiaResponse
    {
        return Inertia::render('Account/Settings', [
            'socialProviders' => app(\App\Services\SocialLogin::class)->providers(),
            'connectedProviders' => $connected = \Illuminate\Support\Facades\DB::table('social_accounts')->where('user_id', $request->user()->id)->pluck('provider'),
            'email' => $request->user()->email,
            'usesSocialLogin' => $connected->isNotEmpty(),
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(DeleteAccountRequest $request, AccountDeletionService $deletion): RedirectResponse
    {
        $user = $request->user();

        $record = $deletion->schedule($user, $request->validated('reason'), $request->validated('note'), 'user');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/login')->with('status', 'Your account is deactivated and will be permanently deleted on '.$record->scheduled_for->format('F j, Y').'. To keep it, contact info@aadirentals.com before then.');
    }
}
