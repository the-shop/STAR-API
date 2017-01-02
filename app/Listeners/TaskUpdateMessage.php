<?php

namespace App\Listeners;

use App\Events\TaskUpdate;
use App\GenericModel;
use App\Profile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class TaskUpdateMessage
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
     * @param  TaskUpdate  $event
     * @return void
     */
    public function handle(TaskUpdate $event)
    {
        $this->tasks = $event->tasks;

//        //estimated task time
//        $estimate_help = $this->tasks->estimatedHours * 3600;

        //task user id
        $user_id = $this->tasks->task_history[0]['user'];

        $profile = Profile::where('id', $user_id)->get();
        var_dump($profile);

        //avoid milliseconds
        $submitted = $this->tasks->due_date - intval($this->tasks->task_history[0]['timestamp'] / 1000);

        $coefficient = ($this->tasks->due_date - $submitted) / $this->tasks->due_date;
        $xp = $this->tasks->xp;


        switch ($coefficient) {
            case ($coefficient <= 0.75):
                $profile->xp += 3;
                $profile->save();
                break;
            case ($coefficient >= 0.75 && $coefficient <= 1):
                $profile->xp += 0;
                $profile->save();
                break;
            case ($coefficient >= 1.01 && $coefficient <= 1.1):
                $profile->xp -= 1;
                $profile->save();
                break;
            case ($coefficient >= 1.11 && $coefficient <= 1.25):
                $profile->xp -= 2;
                $profile->save();
                break;
            case ($coefficient >= 1.26 && $coefficient < 1.4):
                $profile->xp -= 3;
                $profile->save();
                break;
        }
    }
}
