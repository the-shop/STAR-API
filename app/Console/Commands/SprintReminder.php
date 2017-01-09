<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\GenericModel;

/**
 * Class SprintReminder
 * @package App\Console\Commands
 */
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
     * Execute the console command.
     */
    public function handle()
    {
        GenericModel::setCollection('projects');
        $projects = GenericModel::all();

        $activeProjects = [];
        $sprints = [];
        $tasks = [];

        // Get all active projects and sprints
        GenericModel::setCollection('sprints');
        foreach ($projects as $project) {
            if (!empty($project->acceptedBy) && $project->isComplete !== true && !empty($project->sprints)) {
                $activeProjects[$project->id] = $project;
                foreach ($project->sprints as $sprintId) {
                    $sprints[$sprintId] = GenericModel::where('_id', '=', $sprintId)->first();
                }
            }
        }

        // Get all active tasks
        GenericModel::setCollection('tasks');
        foreach ($sprints as $sprint) {
            if (!empty($sprint->tasks)) {
                foreach ($sprint->tasks as $taskId) {
                    $tasks[$taskId] = GenericModel::where('_id', '=', $taskId)->first();
                }
            }
        }

        // Check tasks due date and ping task owner 1 day before task end
        $date = new \DateTime();
        $unixCheckDate = $date->format('U') + 24 * 60 * 60;
        $checkDate = date('Y-m-d', $unixCheckDate);

        GenericModel::setCollection('profiles');
        foreach ($tasks as $task) {
            $taskDueDate = date('Y-m-d', $task->due_date);
            if ($taskDueDate === $checkDate) {
                $user = GenericModel::find($task->owner);
                if ($user->slack) {
                    $recipient = '@' . $user->slack;
                    $project = $activeProjects[$task->project_id]->name;
                    $message = '*Reminder*: task *'
                        . $task->title
                        . '* is due *tomorrow* (on '
                        . $taskDueDate
                        . ', for project *'
                        . $project
                        . '*)';
                    \SlackChat::message($recipient, $message);
                }
            }
        }
    }
}
