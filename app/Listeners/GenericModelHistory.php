<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\App;

class GenericModelHistory
{
    /**
     * Handle the event.
     *
     * @param  GenericModelHistory  $event
     * @return void
     */
    public function handle(\App\Events\GenericModelHistory $event)
    {
        //
    }
}
