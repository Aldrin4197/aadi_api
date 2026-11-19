<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('aadi:admin {email}', function () {
    $user = \App\Models\User::where('email', $this->argument('email'))->first();
    if (!$user) { $this->error('Account not found. Register the account first.'); return 1; }
    $user->forceFill(['is_admin' => true])->save();
    $this->info('Administrator access granted.');
    return 0;
})->purpose('Grant administrator access to an existing account');

Artisan::command('aadi:queue-health', function () {
    if (config('queue.default') !== 'database' || config('queue.failed.driver') !== 'database-uuids') {
        $this->error('Queue health requires the database queue and database-uuids failed-job driver.');
        return 1;
    }

    $queue = config('queue.connections.database');
    $oldest = DB::connection($queue['connection'])->table($queue['table'])
        ->where('queue', $queue['queue'])
        ->where('available_at', '<=', now()->timestamp)
        ->min('available_at');
    $failed = DB::connection(config('queue.failed.database'))->table(config('queue.failed.table'))
        ->where('failed_at', '>=', now()->subDay())->count();
    $age = $oldest === null ? 0 : max(0, now()->timestamp - (int) $oldest);

    if ($age >= 300 || $failed > 0) {
        Log::warning('Queue health requires attention.', ['oldest_ready_job_seconds' => $age, 'failed_jobs_last_24_hours' => $failed]);
        $this->error("Queue needs attention: oldest ready job {$age}s; failed jobs in the last 24 hours: {$failed}.");
        return 1;
    }

    $this->info("Queue healthy: oldest ready job {$age}s; no failed jobs in the last 24 hours.");
    return 0;
})->purpose('Check for stalled and recently failed database queue jobs');

Artisan::command('aadi:process-account-deletions', function (App\Services\AccountDeletionService $deletions) {
    $result = $deletions->processDue();
    $this->info("Deleted {$result['deleted']} due account(s); removed {$result['expired_records']} expired deletion record(s) and {$result['expired_provider_records']} expired provider record(s).");
})->purpose('Permanently delete accounts whose grace period ended and remove deletion records past retention');

Schedule::command('aadi:process-account-deletions')->dailyAt('02:00')->withoutOverlapping(30);

if (config('queue.hostinger_cron')) {
    Schedule::command('queue:work --stop-when-empty --max-time=45 --timeout=60 --tries=3')
        ->everyMinute()->withoutOverlapping(5)->appendOutputTo(storage_path('logs/queue-worker.log'));
    Schedule::command('aadi:queue-health')
        ->everyFiveMinutes()->withoutOverlapping(5)->appendOutputTo(storage_path('logs/queue-health.log'));
}
