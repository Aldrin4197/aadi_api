<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountDeletion;
use App\Models\User;
use App\Services\AccountDeletionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AccountDeletionController extends Controller
{
    public function update(Request $request, User $user, AccountDeletionService $deletions)
    {
        abort_if($user->is_admin, 403, 'Administrator accounts cannot be deleted here.');
        $action = $request->input('action');
        $data = $request->validate([
            'action' => ['required', Rule::in(['schedule', 'cancel', 'delete_now'])],
            'reason' => [Rule::requiredIf($action !== 'cancel'), 'nullable', Rule::in(array_keys(AccountDeletion::labels()))],
            'note' => ['nullable', 'string', 'max:500'],
            'when' => [Rule::requiredIf($action === 'schedule'), 'nullable', 'date', 'after:now'],
            'confirm' => [Rule::requiredIf($action === 'delete_now'), 'nullable', 'string'],
        ]);

        if ($data['action'] === 'delete_now' && strcasecmp(trim($data['confirm']), $user->email) !== 0) {
            return back()->withErrors(['confirm' => 'Type the account email address to confirm permanent deletion.']);
        }

        $event = ['user_id' => $user->id, 'admin_id' => $request->user()->id, 'created_at' => now()];
        match ($data['action']) {
            'schedule' => $this->schedule($deletions, $user, $data, $event),
            'cancel' => $this->cancel($deletions, $user, $event),
            'delete_now' => $deletions->purge($user, $data['reason'], $data['note'] ?? null, 'admin'),
        };

        return $data['action'] === 'delete_now'
            ? redirect('/admin/users')->with('success', 'Account permanently deleted.')
            : back()->with('success', $data['action'] === 'schedule' ? 'Account deactivated and scheduled for deletion.' : 'Scheduled deletion cancelled.');
    }

    private function schedule(AccountDeletionService $deletions, User $user, array $data, array $event): void
    {
        $when = Carbon::parse($data['when'])->setTimezone(config('app.timezone'));
        $deletions->schedule($user, $data['reason'], $data['note'] ?? null, 'admin', $when);
        DB::table('account_restriction_events')->insert($event + ['action' => 'schedule_deletion', 'reason' => null, 'until' => $when]);
    }

    private function cancel(AccountDeletionService $deletions, User $user, array $event): void
    {
        abort_unless($user->isScheduledForDeletion(), 422, 'This account is not scheduled for deletion.');
        $deletions->cancel($user);
        DB::table('account_restriction_events')->insert($event + ['action' => 'cancel_deletion', 'reason' => null, 'until' => null]);
    }
}
