<?php

namespace App\Listeners;

use App\Events\TaskUpdate;
use App\GenericModel;
use App\Profile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class TaskUpdateXP
{

    protected $tasks;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  TaskUpdate $event
     * @return void
     */
    public function handle(TaskUpdate $event)
    {
        $this->tasks = $event->tasks;

        //task user id
        $user_id = $this->tasks->task_history[0]['user'];

        //project owner id
        $project_owner_id = $this->tasks->ownerId;

        //get task's XP value
        $task_xp = $this->tasks->xp;

        $user_profile = Profile::find($user_id);
        $owner_profile = Profile::find($project_owner_id);


        $timeSpentReviewing = 0;
        $timeSpentWorking = 0;
        $tempWork = 0;
        $tempReview = 0;
        for ($i = count($this->tasks->task_history) - 1; $i >= 0; $i--) {
            switch ($this->tasks->task_history[$i]['event']) {
                case ($this->tasks->task_history[$i]['event'] == 'Task submitted for QA'):
                    $endWork = $tempWork - floor($this->tasks->task_history[$i]['timestamp'] / 1000);
                    if ($endWork > 0) {
                        $timeSpentWorking += $endWork;
                    }
                    $tempWork = floor($this->tasks->task_history[$i]['timestamp'] / 1000);
                    break;
                case ($this->tasks->task_history[$i]['event'] == 'Task returned for development'):
                    $endReview = $tempReview - floor($this->tasks->task_history[$i]['timestamp'] / 1000);
                    if ($endReview > 0) {
                        $timeSpentReviewing += $endReview;
                    }
                    $tempReview = floor($this->tasks->task_history[$i]['timestamp'] / 1000);
                    break;
            }
        }

        //if time spent reviewing code more than 1 day, deduct project/task owner 5 XP
        if ($timeSpentReviewing > 24 * 60 * 60) {
            $owner_profile->xp += -5;
            $owner_profile->save();
        }

        //apply xp change
        $coefficient = number_format(($timeSpentWorking / ($this->tasks->estimatedHours * 60 * 60)), 5);
        switch ($coefficient) {
            case ($coefficient <= 0.75):
                $user_profile->xp += $task_xp + 3;
                $user_profile->save();
                break;
            case ($coefficient >= 0.75 && $coefficient <= 1):
                $user_profile->xp += $task_xp;
                $user_profile->save();
                break;
            case ($coefficient >= 1.01 && $coefficient <= 1.1):
                $user_profile->xp += -1;
                $user_profile->save();
                break;
            case ($coefficient >= 1.11 && $coefficient <= 1.25):
                $user_profile->xp += -2;
                $user_profile->save();
                break;
            case ($coefficient >= 1.26 && $coefficient < 1.4):
                $user_profile->xp += -3;
                $user_profile->save();
                break;
        }
    }
}
