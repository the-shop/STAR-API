<?php

namespace App\Adapters;

use App\GenericModel;
use App\Services\ProfilePerformance;
use Illuminate\Support\Facades\Auth;

class Task implements AdaptersInterface
{
    public $task;

    public function __construct(GenericModel $model)
    {
        $this->task = $model;
    }

    public function process()
    {
        $profilePerformance = new ProfilePerformance();

        $mappedValues = $profilePerformance->getTaskValuesForProfile(Auth::user(), $this->task);

        foreach ($mappedValues as $key => $value) {
            $this->task->{$key} = $value;
        }

        $this->task->estimatedHours = sprintf('%.2f', $this->task->estimatedHours);
        $this->task->xp = sprintf('%.2f', $this->task->xp);

        $taskStatus = $profilePerformance->perTask($this->task);

        if (!empty($taskStatus)) {
            $taskEstimatedSeconds = $mappedValues['estimatedHours'] * 60 * 60;
            $taskDeliveredOnTime = $taskStatus[$this->task->owner]['workSeconds']
            <= $taskEstimatedSeconds;

            // deadline in last 25% of the time of task
            $lastQuarterOfTask = 0.25 * $taskEstimatedSeconds;

            //generate task color status
            if ($this->task->passed_qa === true && $taskDeliveredOnTime === true) {
                $this->task->colorIndicator = 'green';
                return $this->task;
            }

            if ($this->task->submitted_for_qa === true) {
                $this->task->colorIndicator = 'blue';
                return $this->task;
            }

            if ($this->task->paused === true) {
                $this->task->colorIndicator = 'yellow';
                return $this->task;
            }

            if (($taskEstimatedSeconds - $taskStatus[$this->task->owner]['workSeconds']) <= $lastQuarterOfTask) {
                $this->task->colorIndicator = 'orange';
            }

            if ($this->task->passed_qa === false && $taskDeliveredOnTime === false) {
                $this->task->colorIndicator = 'red';
                return $this->task;
            }
        }

        return $this->task;
    }
}
