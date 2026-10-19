<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

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
