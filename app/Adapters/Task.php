<?php

namespace App\Adapters;

use App\GenericModel;

class Task implements AdaptersInterface
{
    public $model;

    public function __construct(GenericModel $model)
    {
        $this->model = $model;
    }

    public function process()
    {
        $model = $this->model;
    }
}
