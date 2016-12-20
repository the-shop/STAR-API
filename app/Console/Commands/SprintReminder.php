<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\GenericModel;

class SprintReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sprint:remind';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check sprints due dates and ping project users 2 days and 1 day before sprint end';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        GenericModel::setCollection('projects');
        $projects = GenericModel::all();

        $activeProjects = [];
        $sprints = [];
        $tasks = [];

        GenericModel::setCollection('sprints');
        foreach ($projects as $project) {
            if (!empty($project->acceptedBy) && $project->isComplete !== true) {
                $activeProjects[$project->id] = $project;
                if (!empty($project->sprints)) {
                    foreach ($project->sprints as $sprintId) {
                        $sprints[$sprintId] = GenericModel::where('_id', '=', $sprintId)->first();
                    }
                }
            }
        }

        GenericModel::setCollection('tasks');
        foreach ($sprints as $sprint) {
            if (!empty($sprint->tasks)) {
                foreach ($sprint->tasks as $taskId) {
                    $tasks[$taskId] = GenericModel::where('_id', '=', $taskId)->first();
                }
            }
        }

        //check due dates and ping user 2 days before sprint end
        $date = new \DateTime();
        $unixNow = $date->format('U');
        $unix2DaysAhead = $unixNow + 48 * 60 * 60;
        $unix1DayAhead = $unixNow + 24 * 60 * 60;

        $howMany = [];
        foreach ($sprints as $sprint) {
            if ($sprint->end <= $unix2DaysAhead) {
                $howMany[] = date('m-d-Y', $sprint->end);
            }
        }

        print_r($howMany);
    }
}
