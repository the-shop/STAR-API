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
            $newAllAttributes = $event->model->getAttributes();
            $newValues = $event->model->getDirty();
            $oldValues = $event->model->getOriginal();

            $history = $event->model->history;
            foreach ($newValues as $newField => $newValue) {
                if (key_exists($newField, $oldValues)) {
                    $history[] = [
                        'profileId' => \Auth::user()->id,
                        'filedName' => $newField,
                        'oldValue' => $oldValues[$newField],
                        'newValue' => $newValue
                    ];
                } else {
                    $history[] = [
                        'profileId' => \Auth::user()->id,
                        'fieldName' => $newField,
                        'oldValue' => null,
                        'newValue' => $newValue
                    ];
                }
            }

            foreach ($oldValues as $oldFieldName => $oldFieldValue) {
                if (!key_exists($oldFieldName, $newAllAttributes)) {
                    $history[] = [
                        'profileId' => \Auth::user()->id,
                        'fieldName' => $oldFieldName,
                        'oldValue' => $oldFieldValue,
                        'newValue' => null
                    ];
                }
            }

            $event->model->history = $history;
            $event->model->save();
        }
    }
}
