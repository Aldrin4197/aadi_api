<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AccountRestrictionController extends Controller
{
    public const REASONS = [
        'suspicious_activity' => 'Suspicious activity',
        'spam_abuse' => 'Spam or abuse',
        'fraud_scams' => 'Fraud or scams',
        'harassment' => 'Harassment',
        'policy_violation' => 'Policy violation',
        'security_concern' => 'Account security concern',
        'other' => 'Other',
    ];

    public function update(Request $request, User $user)
    {
        abort_if($user->is_admin, 403, 'Administrator accounts cannot be restricted here.');
        $data = $request->validate([
            'action' => ['required', Rule::in(['lock', 'disable', 'restore'])],
            'reason' => [Rule::requiredIf($request->input('action') !== 'restore'), 'nullable', Rule::in(array_keys(self::REASONS))],
            'until' => [Rule::requiredIf($request->input('action') === 'lock'), 'nullable', 'date', 'after:now'],
        ]);
        DB::transaction(function () use ($request, $user, $data) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_if($user->is_admin, 403, 'Administrator accounts cannot be restricted here.');
            $user->restriction_type = match ($data['action']) {
                'lock' => 'locked', 'disable' => 'disabled', default => null
            };
            $user->restricted_until = $data['action'] === 'lock' ? Carbon::parse($data['until'])->setTimezone(config('app.timezone')) : null;
            $user->restriction_reason = $data['action'] === 'restore' ? null : $data['reason'];
            if ($data['action'] !== 'restore') {
                $user->remember_token = Str::random(60);
                $user->tokens()->delete();
            }
            $user->save();
            DB::table('account_restriction_events')->insert([
                'user_id' => $user->id, 'admin_id' => $request->user()->id,
                'action' => $data['action'], 'reason' => $user->restriction_reason,
                'until' => $user->restricted_until, 'created_at' => now(),
            ]);
        });

        return back()->with('success', 'Account access updated.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $status = Password::sendResetLink(['email' => $user->email]);
        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages(['reset' => trans($status)]);
        }

        return back()->with('success', 'Password reset link sent to the registered email address.');
    }
}
