<?php

namespace App\Listeners;

use App\Exceptions\UserInputException;
use App\GenericModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

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

        $taskOwnerId = Auth::user()->id;

        if ($task->isDirty()) {
            $preSetCollection = GenericModel::getCollection();
            $updatedFields = $task->getDirty();
            if ($task['collection'] === 'tasks'
                && (key_exists('owner', $updatedFields) || key_exists('reservationsBy', $updatedFields))
            ) {
                GenericModel::setCollection('tasks');
                $allTasks = GenericModel::where('_id', '!=', $task->_id)
                    ->get();
                $taskReservationTime = Config::get('sharedSettings.internalConfiguration.tasks.reservation.maxReservationTime');
                $currentUnixTime = (new \DateTime())->format('U');

                if (key_exists('owner', $updatedFields)) {
                    $taskOwnerId = $updatedFields['owner'];
                }

                foreach ($allTasks as $item) {
                    if (!empty($item->reservationsBy)) {
                        foreach ($item->reservationsBy as $userReservation) {
                            if ($currentUnixTime - $userReservation['timestamp'] <= ($taskReservationTime * 60)
                                && $userReservation['user_id'] === $taskOwnerId
                            ) {
                                throw new UserInputException('Permission denied. There is reserved previous task.');
                            }
                        }
                    }
                    if ($item->owner === $taskOwnerId
                        && $item->passed_qa === false
                        && $item->submitted_for_qa === false
                    ) {
                        throw new UserInputException('Permission denied. There are unfinished previous tasks.');
                    }
                }
            }

            GenericModel::setCollection($preSetCollection);
        }
    }
}
