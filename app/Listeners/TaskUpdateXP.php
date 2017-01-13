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

        $webDomain = \Config::get('sharedSettings.internalConfiguration.webDomain');
        $taskLink = $webDomain
            . 'projects/'
            . $event->model->project_id
            . '/sprints/'
            . $event->model->sprint_id
            . '/tasks/'
            . $event->model->_id;

        GenericModel::setCollection('xp');
        // If time spent reviewing code more than 1 day, deduct project/task owner 3 XP
        if ($review > 24 * 60 * 60) {
            if (!$projectOwner->xp_id) {
                $userXP = new GenericModel(['records' => []]);
                $userXP->save();
                $projectOwner->xp_id = $userXP->_id;
            } else {
                $userXP = GenericModel::find($projectOwner->xp_id);
            }

            $records = $userXP->records;
            $records[] = [
                'xp' => -3,
                'details' => 'Failed to review PR in time for ' . $taskLink,
                'timestamp' => (int) ((new \DateTime())->format('U') . '000') // Microtime
            ];
            $userXP->records = $records;
            $userXP->save();

            $projectOwner->xp -= 3;
            $projectOwner->save();
        }

        $message = null;
        $xpDiff = 0;

        // Apply XP change
        $coefficient = number_format(($work / ($this->model->estimatedHours * 60 * 60)), 5);
        switch ($coefficient) {
            case ($coefficient < 0.75):
                $xpDiff = $taskXp + 3;
                $message = 'Early task delivery: ' . $taskLink;
                break;
            case ($coefficient >= 0.75 && $coefficient <= 1):
                $xpDiff = $taskXp;
                $message = 'Task delivery: ' . $taskLink;
                break;
            case ($coefficient > 1 && $coefficient <= 1.1):
                $xpDiff = -1;
                $message = 'Late task delivery: ' . $taskLink;
                break;
            case ($coefficient > 1.1 && $coefficient <= 1.25):
                $xpDiff = -2;
                $message = 'Late task delivery: ' . $taskLink;
                break;
            case ($coefficient > 1.25 && $coefficient <= 1.4):
                $xpDiff = -3;
                $message = 'Late task delivery: ' . $taskLink;
                break;
        }

        if ($xpDiff !== 0) {
            if (!$userProfile->xp_id) {
                $userXP = new GenericModel(['records' => []]);
                $userXP->save();
                $userProfile->xp_id = $userXP->_id;
            } else {
                $userXP = GenericModel::find($userProfile->xp_id);
            }

            $records = $userXP->records;
            $records[] = [
                'xp' => $xpDiff,
                'details' => $message,
                'timestamp' => (int) ((new \DateTime())->format('U') . '000') // Microtime
            ];
            $userXP->records = $records;
            $userXP->save();

            $userProfile->xp += $xpDiff;
            $userProfile->save();
        }
    }
}
