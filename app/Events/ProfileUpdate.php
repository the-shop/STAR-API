<?php

namespace App\Events;

use App\Profile;
use Illuminate\Queue\SerializesModels;

class ProfileUpdate extends Event
{
    use SerializesModels;

    public $profile;

    /**
     * ProfileUpdate constructor.
     * @param Profile $profile
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
