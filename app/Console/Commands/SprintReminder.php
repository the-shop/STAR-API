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
    protected $description = 'Check sprint tasks due dates and ping task owner 1 day before task end_due_date';

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

        //get all active projects and sprints
        GenericModel::setCollection('sprints');
        foreach ($projects as $project) {
            if (!empty($project->acceptedBy) && $project->isComplete !== true && !empty($project->sprints)) {
                $activeProjects[$project->id] = $project;
                foreach ($project->sprints as $sprintId) {
                    $sprints[$sprintId] = GenericModel::where('_id', '=', $sprintId)->first();
                }
            }
        }

        //get all active tasks
        GenericModel::setCollection('tasks');
        foreach ($sprints as $sprint) {
            if (!empty($sprint->tasks)) {
                foreach ($sprint->tasks as $taskId) {
                    $tasks[$taskId] = GenericModel::where('_id', '=', $taskId)->first();
                }
            }
        }

        //check tasks due date and ping task owner 1 day before task end
        $date = new \DateTime();
        $unixCheckDate = $date->format('U') + 24 * 60 * 60;
        $checkDate = date('d-m-Y', $unixCheckDate);

        GenericModel::setCollection('profiles');
        foreach ($tasks as $task) {
            $taskDueDate = date('d-m-Y', $task->due_date);
            if ($taskDueDate === $checkDate) {
                $user = GenericModel::where('_id', '=', $task->owner)->first();
                $recipient = '@' . $user['slack'];
                $project = $activeProjects[$task->project_id]->name;
                $message = '::REMINDER:: Project ' . $project . ' due date on task ' . $task->title .
                    ' is ::TOMORROW:: ' . $taskDueDate;
                \SlackChat::message($recipient, $message);
            }
        }
    }
}
