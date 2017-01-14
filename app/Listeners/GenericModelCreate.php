<?php

namespace App\Listeners;

class GenericModelCreate
{
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
