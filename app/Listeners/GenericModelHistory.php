<?php

namespace App\Listeners;

class GenericModelHistory
{
    /**
     * Handle the event.
     * @param \App\Events\GenericModelHistory $event
     */
    public function handle(\App\Events\GenericModelHistory $event)
    {
        if ($event->model->isDirty()) {
            $newValues = $event->model->getDirty();
            $oldValues = $event->model->getOriginal();

            foreach ($oldValues as $oldField => $oldValue) {
                if (key_exists($oldField, $newValues)) {
                    $history = $event->model->history;
                    $history[] = [
                        'profileId' => \Auth::user()->id,
                        'filedName' => $oldField,
                        'oldValue' => $oldValue,
                        'newValue' => $newValues[$oldField]
                    ];
                    $event->model->history = $history;
                    $event->model->save();
                }
            }
        }
    }
}
