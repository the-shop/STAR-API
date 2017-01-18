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

        return $this->task;
    }
}
