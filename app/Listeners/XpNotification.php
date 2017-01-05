<?php

namespace App\Listeners;

use App\Events\XpNotify;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class XpNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  XpNotify  $event
     * @return void
     */
    public function handle(XpNotify $event)
    {
        //
    }
}
