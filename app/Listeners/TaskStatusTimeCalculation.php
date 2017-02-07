<?php

namespace App\Listeners;


class TaskStatusTimeCalculation
{
    /**
     * Handle the event
     * @param \App\Events\TaskStatusTimeCalculation $event
     */
    public function handle(\App\Events\TaskStatusTimeCalculation $event)
    {
        $task = $event->model;

        if ($task['collection'] === 'tasks' && $task->isDirty()) {
            $updatedFields = $task->getDirty();
            $unixTime = (new \DateTime())->format('U');

            //create work field when new user claimed or is assigned to task
            if (key_exists('owner', $updatedFields) && empty($task->work)) {
                $task->work = [
                    $updatedFields['owner'] => [
                        'worked' => 0,
                        'paused' => 0,
                        'qa' => 0,
                        'blocked' => 0,
                        'workTrackTimestamp' => $unixTime
                    ]
                ];
            }
            /*create new record in work field if task is reassigned and if user existed on task, push it to end of array
            so we can know task last active user*/
            if (key_exists('owner', $updatedFields) && !empty($task->work)) {
                $work = $task->work;
                if (key_exists($updatedFields['owner'], $work)) {
                    $oldUserRecord = $work[$updatedFields['owner']];
                    unset($work[$updatedFields['owner']]);
                    $work[$updatedFields['owner']] = $oldUserRecord;
                } else {
                    $work[$updatedFields['owner']] = [
                        'worked' => 0,
                        'paused' => 0,
                        'qa' => 0,
                        'blocked' => 0,
                        'workTrackTimestamp' => $unixTime
                    ];
                }
                $task->work = $work;
            }

            //when task status is paused/resumed calculate time for worked/paused
            if (key_exists('paused', $updatedFields) && !key_exists('submitted_for_qa', $updatedFields)) {
                $work = $task->work;
                $calculatedTime = (int)($unixTime - $work[$task->owner]['workTrackTimestamp']);
                $updatedFields['paused'] === true ?
                    $work[$task->owner]['worked'] += $calculatedTime :
                    $work[$task->owner]['paused'] += $calculatedTime;
                $work[$task->owner]['workTrackTimestamp'] = $unixTime;
                $task->work = $work;

            }

            //when task status is blocked/unblocked calculate time for worked/blocked
            if (key_exists('blocked', $updatedFields) && !key_exists('submitted_for_qa', $updatedFields)) {
                $work = $task->work;
                $calculatedTime = (int)($unixTime - $work[$task->owner]['workTrackTimestamp']);
                $updatedFields['blocked'] === true ?
                    $work[$task->owner]['worked'] += $calculatedTime :
                    $work[$task->owner]['blocked'] += $calculatedTime;
                $work[$task->owner]['workTrackTimestamp'] = $unixTime;
                $task->work = $work;

            }

            //when task status is submitted_for_qa/failed_qa calculate time for worked/qa
            if (key_exists('submitted_for_qa', $updatedFields)) {
                $work = $task->work;
                $calculatedTime = (int)($unixTime - $work[$task->owner]['workTrackTimestamp']);
                $updatedFields['submitted_for_qa'] === true ?
                    $work[$task->owner]['worked'] += $calculatedTime :
                    $work[$task->owner]['qa'] += $calculatedTime;
                $work[$task->owner]['workTrackTimestamp'] = $unixTime;
                $task->work = $work;
            }

            //when task status is passed_qa calculate time for qa
            if (key_exists('passed_qa', $updatedFields) && $updatedFields['passed_qa'] === true) {
                $work = $task->work;
                $calculatedTime = (int)($unixTime - $work[$task->owner]['workTrackTimestamp']);
                $work[$task->owner]['qa'] += $calculatedTime;
                $work[$task->owner]['workTrackTimestamp'] = $unixTime;
                $task->work = $work;
            }
        }
    }
}
