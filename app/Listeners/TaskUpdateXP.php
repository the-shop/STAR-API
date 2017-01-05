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
        // I don't always make PRs, but when I do I make one with every code change I have, even work in progress
        return $this;
        $this->tasks = $event->tasks;

        //task user id
        $user_id = $this->tasks->task_history[0]['user'];

        //project owner id
        $project_owner_id = $this->tasks->owner;

        //get task's XP value
        $task_xp = $this->tasks->xp;

        $user_profile = Profile::find($user_id);
        $owner_profile = Profile::find($project_owner_id);

        $end = 0;
        $review = 0;
        for ($i = 0; $i < count($this->tasks->task_history); $i++) {
            if ($this->tasks->submitted_for_qa === true) {
                $end += $this->tasks->task_history[$i]['timestamp'] - $this->tasks->task_history[$i - 1]['timestamp'];
                echo $end;
            } elseif ($this->tasks->submitted_for_qa === false) {
                $review += $this->tasks->task_history[$i]['timestamp'] - $this->tasks->task_history[$i - 1]['timestamp'];
            }
        }




        $coefficient = ($end / $this->tasks->estimatedHours);

        echo $coefficient;
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
