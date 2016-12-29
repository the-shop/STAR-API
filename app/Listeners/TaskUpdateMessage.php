<?php

namespace App\Listeners;

use App\Events\TaskUpdate;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class TaskUpdateMessage
{

    protected $task;
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
        $this->task = $event->task;
        echo "<b> Task ". $this->task . "updated";
    }
}
