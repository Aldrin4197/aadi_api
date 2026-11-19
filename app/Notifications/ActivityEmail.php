<?php

namespace App\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ActivityEmail extends Notification implements ShouldQueue
{
    public int $tries = 3;

    public int $backoff = 60;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Aadi notification')
            ->greeting('Hello,')
            ->line('You have a new notification on Aadi.')
            ->action('Open Aadi', url('/explore'))
            ->line('Sign in and open your notifications to see the details.');
    }
}
