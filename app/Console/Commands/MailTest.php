<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MailTest extends Command
{
    protected $signature = 'aadi:mail-test {to : Address that should receive the test message} {--verification : Send the real "Confirm your email" message (the link only works if that address belongs to an existing user)}';

    protected $description = 'Check the mail settings by sending a test email and reporting the exact delivery error, if any';

    public function handle(): int
    {
        $to = (string) $this->argument('to');
        $mailer = (string) config('mail.default');
        $this->line("Mailer: {$mailer}".($mailer === 'smtp' ? ' ('.config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port').', scheme '.(config('mail.mailers.smtp.scheme') ?: 'default').')' : ''));
        $this->line('From:   '.config('mail.from.name').' <'.config('mail.from.address').'>');

        if ($mailer === 'smtp' && !config('mail.mailers.smtp.password')) {
            $this->error('MAIL_PASSWORD is empty in .env. Add the mailbox password, then run: php artisan config:clear');

            return self::FAILURE;
        }

        try {
            if ($this->option('verification')) {
                $user = User::where('email', $to)->first() ?? tap(new User(['name' => 'Test recipient', 'email' => $to]), fn ($u) => $u->id = '00000000000000000000000000');
                $user->notify(new VerifyEmail);
            } else {
                Mail::raw('This is a test message from Aadi. If you can read it, your mail settings work.', fn ($message) => $message->to($to)->subject('Aadi mail test'));
            }
        } catch (\Throwable $error) {
            $this->error('Delivery failed: '.$error->getMessage());
            $this->line('Common causes: wrong mailbox password, MAIL_FROM_ADDRESS not matching the mailbox, or a blocked port (try 587 with MAIL_SCHEME=smtp).');

            return self::FAILURE;
        }

        $this->info("Sent. Check the inbox (and spam folder) of {$to}.");

        return self::SUCCESS;
    }
}
