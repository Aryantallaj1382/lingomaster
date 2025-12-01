<?php

namespace App\Broadcasting;

use App\Models\User;

class ChatChannel
{
    /**
     * Create a new channel instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Authenticate the user's access to the channel.
     */
    public function join(User $user, $chatId)
    {
        // فقط اعضای اون چت دسترسی داشته باشن
        return true; // یا مثلاً: return $user->id === (int)$chatId;
    }

}
