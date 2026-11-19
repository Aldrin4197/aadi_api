<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class UserActivityChanged implements ShouldBroadcast
{
    use Dispatchable;

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(public string $userId, public string $type) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('users.'.$this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'activity.changed';
    }

    public function broadcastWith(): array
    {
        return ['type' => $this->type];
    }
}
