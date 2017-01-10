<?php

namespace App\Listeners;

use App\Events\ModelUpdate;
use App\GenericModel;
use App\Profile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class TaskUpdateXP
{

    protected $model;

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
     * @param  ModelUpdate $event
     * @return void
     */
    public function handle(ModelUpdate $event)
    {
        $this->model = $event->model;

        //task user id
        $userId = $this->model->task_history[0]['user'];

        //project owner id
        $projectOwnerId = $this->model->ownerId;

        $userProfile = Profile::find($userId);
        $ownerProfile = Profile::find($projectOwnerId);


        //get task's XP value
        $taskXp = $this->model->xp;

        $work = 0;
        $review = 0;
        for ($i = count($this->model->task_history) - 1; $i >= 0; $i--) {
            if (((($this->model->task_history[$i]['event'] == "Task returned for development")) || $this->model->task_history[$i]['event'] == "Task passed QA!") && ($i > 0)) {
                    $review += floor($this->model->task_history[$i]['timestamp'] / 1000) - floor($this->model->task_history[$i - 1]['timestamp'] / 1000);
            } elseif (($this->model->task_history[$i]['event'] == "Task submitted for QA") && ($i > 0)) {
                    $work += floor($this->model->task_history[$i]['timestamp'] / 1000) - floor($this->model->task_history[$i - 1]['timestamp'] / 1000);
            }
        }

        //if time spent reviewing code more than 1 day, deduct project/task owner 3 XP
        if ($review > 24 * 60 * 60) {
            $ownerProfile->xp -= 3;
            $ownerProfile->save();
        }

        //apply xp change
        $coefficient = number_format(($work / ($this->model->estimatedHours * 60 * 60)), 5);
        switch ($coefficient) {
            case ($coefficient <= 0.75):
                $userProfile->xp += $taskXp + 3;
                $userProfile->save();
                break;
            case ($coefficient >= 0.75 && $coefficient <= 1):
                $userProfile->xp += $taskXp;
                $userProfile->save();
                break;
            case ($coefficient >= 1.01 && $coefficient <= 1.1):
                $userProfile->xp += -1;
                $userProfile->save();
                break;
            case ($coefficient >= 1.11 && $coefficient <= 1.25):
                $userProfile->xp += -2;
                $userProfile->save();
                break;
            case ($coefficient >= 1.26 && $coefficient < 1.4):
                $userProfile->xp += -3;
                $userProfile->save();
                break;
        }
    }
}
