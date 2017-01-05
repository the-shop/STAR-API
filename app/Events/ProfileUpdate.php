<?php

namespace App\Events;

use App\Profile;
use Illuminate\Queue\SerializesModels;

class ProfileUpdate extends Event
{
    use SerializesModels;

    public $profile;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Profile $profile)
    {
        $this->profile = $profile;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}
