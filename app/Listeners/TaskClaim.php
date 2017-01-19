<?php

namespace App\Listeners;

use App\Exceptions\UserInputException;
use App\GenericModel;

class TaskClaim
{
    /**
     * Handle the event.
     * @param \App\Events\TaskClaim $event
     * @throws UserInputException
     */
    public function handle(\App\Events\TaskClaim $event)
    {
        $task = $event->model;

        $previousTasks = [];

        if ($task->isDirty()) {
            $preSetCollection = GenericModel::getCollection();
            $updatedFields = $task->getDirty();
            if ($task['collection'] === 'tasks' && key_exists('owner', $updatedFields)) {
                GenericModel::setCollection('tasks');
                $allTasks = GenericModel::where('_id', '!=', $task->_id)->get();
                foreach ($allTasks as $item) {
                    if (empty($item->owner) || !empty($item->owner) && $item->owner === $updatedFields['owner'] &&
                        $item->passed_qa === true || $item->submitted_for_qa === true
                    ) {
                        continue;
                    }
                    $previousTasks[] = $item;
                }
            }

            if (count($previousTasks) > 0) {
                throw new UserInputException('Permission denied. There are unfinished previous tasks.');
            }

            GenericModel::setCollection($preSetCollection);
        }
    }
}
