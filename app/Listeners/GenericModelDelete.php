<?php

namespace App\Listeners;

use App\Traits\DynamicListener;

class GenericModelDelete
{
    use DynamicListener;

    /**
     * Handle the event.
     * @param \App\Events\GenericModelDelete $event
     */
    public function handle(\App\Events\GenericModelDelete $event)
    {
        $action = 'delete';
        $this->triggerListeners($event, $action);
    }
}
