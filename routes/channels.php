<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('users.{userId}', fn (User $user, string $userId) => (string) $user->id === $userId && !$user->isRestricted());
