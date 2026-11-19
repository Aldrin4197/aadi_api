<?php

namespace App\Services;

use App\Events\UserActivityChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RealtimePublisher
{
    public static function clientConfig(): ?array
    {
        $connection = config('broadcasting.connections.pusher');
        if (config('broadcasting.default') !== 'pusher' || empty($connection['key']) || empty($connection['secret'])
            || empty($connection['app_id']) || empty($connection['options']['cluster'])) {
            return null;
        }

        return ['key' => $connection['key'], 'cluster' => $connection['options']['cluster']];
    }

    public function publish(string $userId, string $type): void
    {
        if (config('broadcasting.default') !== 'pusher') {
            return;
        }
        if (!self::clientConfig()) {
            Log::warning('Pusher is enabled but its configuration is incomplete.');
            return;
        }

        DB::afterCommit(function () use ($userId, $type) {
            try {
                UserActivityChanged::dispatch($userId, $type);
            } catch (Throwable $exception) {
                report($exception);
            }
        });
    }
}
