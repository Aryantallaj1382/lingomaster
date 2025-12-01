<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::routes(['middleware' => ['auth:sanctum']]);

// اینجا لاگ بگیر که کاربر شناسایی شده یا نه
Broadcast::channel('chat.{user1_id}-{user2_id}', function ($user, $user1_id, $user2_id) {
    Log::info('Broadcast auth user:', [
        'id' => optional($user)->id,
        'user' => $user,
    ]);

    return $user->id == $user1_id || $user->id == $user2_id;
});
Broadcast::channel('chat-updates.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
