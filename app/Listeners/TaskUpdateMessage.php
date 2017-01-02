<?php

namespace App\Listeners;

use App\Events\TaskUpdate;
use App\GenericModel;
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

        $submitted = $this->tasks->due_date - intval($this->tasks->task_history[0]['timestamp'] / 1000);

        $coefficient = ($this->tasks->due_date - $submitted) / $this->tasks->due_date;
        $xp = $this->tasks->xp;
        switch ($coefficient) {
            case ($coefficient <= 0.75):
                $xp += 3;
                echo $xp;
                break;
            case ($coefficient >= 0.75 && $coefficient <= 1):
//                $xp = $this->tasks->xp;
                echo $xp;
                break;
            case ($coefficient >= 1.01 && $coefficient <= 1.1):
                $xp -= 1;
                echo $xp;
                break;
            case ($coefficient >= 1.11 && $coefficient <= 1.25):
                $xp -= 2;
                echo  $xp;
                break;
            case ($coefficient >= 1.26 && $coefficient < 1.4):
                $xp -= 3;
                echo $xp;
                break;
        }
    }
}
