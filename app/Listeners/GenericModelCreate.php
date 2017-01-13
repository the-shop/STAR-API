<?php

namespace App\Listeners;

use App\GenericModel;
use Illuminate\Support\Facades\App;

class GenericModelCreate
{
    /**
     * Handle the event
     * @param \App\Events\GenericModelCreate $event
     */
    public function handle(\App\Events\GenericModelCreate $event)
    {
        $app = App::getFacadeRoot();
        $dispatcher = $app->events;

        $presetCollection = GenericModel::getCollection();
        GenericModel::setCollection('listenerRules');
        $listenerRules = GenericModel::all();

        $eventsListeners = [];

        foreach ($listenerRules as $rule) {
            if (!empty($rule->resource) && $rule->resource === $presetCollection && !empty($rule->event) &&
                $rule->event === 'create'
            ) {
                foreach ($rule->listeners as $events => $listeners) {
                    $eventsListeners[$events] = $listeners;
                }
            }
        }

        if (!empty($eventsListeners)) {
            foreach ($eventsListeners as $eventName => $listeners) {
                foreach ($eventsListeners[$eventName] as $listener) {
                    $dispatcher->listen($eventName, $listener);
                    event(new $eventName($event->model));
                }
            }
        }

        GenericModel::setCollection($presetCollection);
    }
}
