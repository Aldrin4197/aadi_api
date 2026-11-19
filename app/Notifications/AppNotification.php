<?php

namespace App\Notifications;

use App\Models\User;
use App\Services\RealtimePublisher;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Throwable;

/** An in-app notification stored in the database. */
class AppNotification extends Notification
{
    public function __construct(
        public string $kind,
        public string $title,
        public string $text = '',
        public ?string $href = null,
        public string $tone = 'info',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['kind' => $this->kind, 'title' => $this->title, 'text' => $this->text, 'href' => $this->href, 'tone' => $this->tone];
    }

    public function afterSending(object $notifiable, string $channel, mixed $response): void
    {
        if ($channel === 'database' && $notifiable instanceof User) {
            app(RealtimePublisher::class)->publish((string) $notifiable->id, $this->kind === 'message' ? 'message' : 'notification');

            if (config('mail.activity_notifications')) {
                DB::afterCommit(function () use ($notifiable) {
                    try {
                        $notifiable->notify(new ActivityEmail);
                    } catch (Throwable $exception) {
                        report($exception);
                    }
                });
            }
        }
    }
}
