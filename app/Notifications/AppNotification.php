<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/** An in-app notification shown in the navbar bell. Stored in the database only; nothing is emailed. */
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
}
