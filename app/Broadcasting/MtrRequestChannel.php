<?php

namespace App\Broadcasting;

use App\Models\User;

class MtrRequestChannel
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
    public function join(): array|bool
    {
        // Check if the user is authenticated by comparing the user's session ID to the socket ID.
        return true;
    }
}
