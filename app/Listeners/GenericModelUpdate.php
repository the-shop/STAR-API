<?php

namespace App\Listeners;

use App\Traits\DynamicListener;

class GenericModelUpdate
{
    use DynamicListener;
    /**
     * Handle the event.
     * @param \App\Events\GenericModelUpdate $event
     */
    public function handle(\App\Events\GenericModelUpdate $event)
    {
        $action = 'update';
        $this->triggerListeners($event, $action);
    }
}
