<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        
        // Disable resource wrapping for API responses
        JsonResource::withoutWrapping();

        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            $minutes = (int) config('auth.verification.expire', 60);

            return (new MailMessage)
                ->subject('Confirm your Aadi email address')
                ->greeting('Hi '.($notifiable->name ?: 'there').',')
                ->line('Thanks for joining Aadi. Please confirm your email address so we know it is really you. It also earns you a verified badge on your profile.')
                ->action('Confirm email address', $url)
                ->line("This link expires in {$minutes} minutes and only works for your account. If you did not create an Aadi account, you can safely ignore this email.")
                ->salutation('The Aadi team');
        });
    }
}
