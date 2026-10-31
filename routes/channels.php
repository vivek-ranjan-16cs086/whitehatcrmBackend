<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.user.{id}', function (User $user, $id) {
    return $user->enable && (int) $user->id === (int) $id;
});
