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
        $userProfile = Profile::find($userId);

        //get project owner id
        GenericModel::setCollection('projects');
        $project = GenericModel::where('_id', $this->model->project_id)->first();
        $projectOwner = Profile::find($project->acceptedBy);

        //get task's XP value
        $taskXp = $this->model->xp;

        $returned = "Task failed QA";
        $passed = "Task passed QA";
        $submitted = "Task ready for QA";

        $work = 0;
        $review = 0;
        for ($i = count($this->model->task_history) - 1; $i >= 0; $i--) {
            if (($this->model->task_history[$i]['event'] == $returned) || ($this->model->task_history[$i]['event'] == $passed)) {
                for ($j = $i; $j > 0; $j--) {
                    if (($this->model->task_history[$j]['event'] == $returned) || ($this->model->task_history[$j]['event'] == $passed)) {
                        $review += floor($this->model->task_history[$j]['timestamp'] / 1000) - floor($this->model->task_history[$j - 1]['timestamp'] / 1000);
                    }
                    break;
                }
            } elseif (($this->model->task_history[$i]['event'] == $submitted)) {
                for ($j = $i; $j > 0; $j++) {
                    if ($this->model->task_history[$j]['event'] == $submitted) {
                        $work += floor($this->model->task_history[$j]['timestamp'] / 1000) - floor($this->model->task_history[$j - 1]['timestamp'] / 1000);
                    }
                    break;
                }
            }
        }

        //if time spent reviewing code more than 1 day, deduct project/task owner 3 XP
        if ($review > 24 * 60 * 60) {
            $projectOwner->xp -= 3;
            $projectOwner->save();
        }

        //apply xp change
        $coefficient = number_format(($work / ($this->model->estimatedHours * 60 * 60)), 5);
        switch ($coefficient) {
            case ($coefficient < 0.75):
                $userProfile->xp += $taskXp + 3;
                $userProfile->save();
                break;
            case ($coefficient >= 0.75 && $coefficient <= 1):
                $userProfile->xp += $taskXp;
                $userProfile->save();
                break;
            case ($coefficient > 1 && $coefficient <= 1.1):
                $userProfile->xp = -1;
                $userProfile->save();
                break;
            case ($coefficient > 1.1 && $coefficient <= 1.25):
                $userProfile->xp = -2;
                $userProfile->save();
                break;
            case ($coefficient > 1.25 && $coefficient <= 1.4):
                $userProfile->xp -= 3;
                $userProfile->save();
                break;
        }
    }
}
