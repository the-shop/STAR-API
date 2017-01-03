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
     * @param  TaskUpdate $event
     * @return void
     */
    public function handle(TaskUpdate $event)
    {
        $this->tasks = $event->tasks;

        //task user id
        $user_id = $this->tasks->task_history[0]['user'];

        $task_xp = $this->tasks->xp;

        $profile = Profile::find($user_id);

        //avoid milliseconds
        $submitted = $this->tasks->due_date - intval($this->tasks->task_history[0]['timestamp'] / 1000);

        $coefficient = ($this->tasks->due_date - $submitted) / $this->tasks->due_date;
        if ($profile->id == $user_id) {
            switch ($coefficient) {
                case ($coefficient <= 0.75):
                    $profile->xp += $task_xp + 3;
                    $profile->save();
                    break;
                case ($coefficient >= 0.75 && $coefficient <= 1):
                    $profile->xp += $task_xp;
                    $profile->save();
                    break;
                case ($coefficient >= 1.01 && $coefficient <= 1.1):
                    $profile->xp += $task_xp - 1;
                    $profile->save();
                    break;
                case ($coefficient >= 1.11 && $coefficient <= 1.25):
                    $profile->xp += $task_xp - 2;
                    $profile->save();
                    break;
                case ($coefficient >= 1.26 && $coefficient < 1.4):
                    $profile->xp += $task_xp - 3;
                    $profile->save();
                    break;
            }
        }
    }
}
