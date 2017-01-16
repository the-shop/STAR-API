<?php

namespace App\Listeners;

use App\Traits\DynamicListener;

class GenericModelCreate
{
    use DynamicListener;

    /**
     * Handle the event
     * @param \App\Events\GenericModelCreate $event
     */
    public function handle(\App\Events\GenericModelCreate $event)
    {
        $action = 'create';
        $this->triggerListeners($event, $action);
    }
}
