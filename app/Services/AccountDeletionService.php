<?php

namespace App\Services;

use App\Models\AccountDeletion;
use App\Models\ProviderRecord;
use App\Models\ProviderRecordArchive;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AccountDeletionService
{
    /** Deactivates the account immediately and queues permanent deletion for $when (default: after the grace period). */
    public function schedule(User $user, string $reason, ?string $note, string $source, ?Carbon $when = null): AccountDeletion
    {
        $when ??= now()->addDays(AccountDeletion::GRACE_DAYS);

        return DB::transaction(function () use ($user, $reason, $note, $source, $when) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $record = $this->pending($user) ?? new AccountDeletion(['user_id' => $user->id, 'requested_at' => now(), 'status' => 'scheduled']);
            $record->fill([
                'reason' => $reason,
                'note' => $note !== null && trim($note) !== '' ? Str::limit(trim($note), 500, '') : null,
                'source' => $source,
                'account_age_days' => (int) $user->created_at->diffInDays(now()),
                'scheduled_for' => $when,
            ])->save();

            $user->forceFill(['deletion_scheduled_for' => $when, 'remember_token' => Str::random(60)])->save();
            $this->revokeAccess($user);

            return $record;
        });
    }

    public function cancel(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $this->pending($user)?->update([
                'status' => 'cancelled', 'user_id' => null, 'cancelled_at' => now(),
                'retain_until' => now()->addMonths(AccountDeletion::RETENTION_MONTHS),
            ]);
            $user->forceFill(['deletion_scheduled_for' => null])->save();
        });
    }

    /** Permanently deletes the account. Pass a reason when no deletion was scheduled first (administrator deleting at once). */
    public function purge(User $user, ?string $reason = null, ?string $note = null, string $source = 'admin'): void
    {
        $id = $user->id;

        DB::transaction(function () use ($user, $reason, $note, $source) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $record = $this->pending($user) ?? new AccountDeletion([
                'reason' => $reason ?? 'admin_other', 'note' => $note, 'source' => $source,
                'requested_at' => now(), 'account_age_days' => (int) $user->created_at->diffInDays(now()),
            ]);
            $record->fill([
                'status' => 'completed', 'user_id' => null, 'completed_at' => now(),
                'retain_until' => now()->addMonths(AccountDeletion::RETENTION_MONTHS),
            ])->save();

            $this->archiveProviderRecords($user);
            $this->revokeAccess($user);
            $user->notifications()->delete();
            $user->delete();
        });

        // Related rows are removed by database cascades; uploaded files are not.
        Storage::disk('public')->deleteDirectory('avatars/'.$id);
        Storage::disk('public')->deleteDirectory('listing-photos/'.$id);
        Storage::disk('local')->deleteDirectory('provider-documents/'.$id);
    }

    /** @return array{deleted:int, expired_records:int, expired_provider_records:int} */
    public function processDue(): array
    {
        $deleted = 0;
        User::whereNotNull('deletion_scheduled_for')->where('deletion_scheduled_for', '<=', now())->each(function (User $user) use (&$deleted) {
            if ($user->is_admin) {
                $this->cancel($user);
                return;
            }
            $this->purge($user);
            $deleted++;
        });
        $expired = AccountDeletion::where('retain_until', '<', now())->delete();
        $expiredProviderRecords = ProviderRecordArchive::where('retain_until', '<=', now())->delete();

        return ['deleted' => $deleted, 'expired_records' => $expired, 'expired_provider_records' => $expiredProviderRecords];
    }

    private function archiveProviderRecords(User $user): void
    {
        $retainUntil = now()->addMonthsNoOverflow(AccountDeletion::RETENTION_MONTHS);
        ProviderRecord::where(fn ($query) => $query->where('provider_id', $user->id)->orWhere('customer_id', $user->id))
            ->with(['customer:id,name', 'payments'])->lockForUpdate()->chunkById(200, function ($records) use ($user, $retainUntil) {
                foreach ($records as $record) {
                    ProviderRecordArchive::create([
                        'provider_id' => (string) $record->provider_id === (string) $user->id ? null : $record->provider_id,
                        'service' => $record->service,
                        'retain_until' => $retainUntil,
                        'data' => [
                            'listingTitle' => $record->listing_title,
                            'customerName' => $record->customer->name,
                            'kind' => $record->kind,
                            'status' => $record->status,
                            'start' => $record->starts_on->toDateString(),
                            'end' => $record->ends_on?->toDateString(),
                            'agreedCents' => $record->agreed_cents,
                            'payments' => $record->payments->map(fn ($payment) => [
                                'amountCents' => $payment->amount_cents,
                                'paidOn' => $payment->paid_on->toDateString(),
                                'period' => $payment->period,
                                'note' => $payment->note,
                                'voided' => $payment->voided_at !== null,
                            ])->all(),
                        ],
                    ]);
                }
            });
    }

    private function pending(User $user): ?AccountDeletion
    {
        return AccountDeletion::where('user_id', $user->id)->where('status', 'scheduled')->first();
    }

    private function revokeAccess(User $user): void
    {
        $user->tokens()->delete();
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
    }
}
