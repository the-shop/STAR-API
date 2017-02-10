<?php

namespace App\Console\Commands;

use App\Helpers\InputHandler;
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
        $members = [];
        $sprints = [];
        $tasks = [];

        $dateCheck = (new \DateTime())->format('Y-m-d');

        // Get all active projects, members of projects and sprints
        foreach ($projects as $project) {
            if (!empty($project->acceptedBy) && $project->isComplete !== true) {
                $activeProjects[$project->id] = $project;
                GenericModel::setCollection('sprints');
                $projectSprints = GenericModel::where('project_id', '=', $project->id)->get();
                foreach ($projectSprints as $sprint) {
                    $sprintStartDueDate = date('Y-m-d', $sprint->start);
                    $sprintEndDueDate = date('Y-m-d', $sprint->end);
                    if ($dateCheck >= $sprintStartDueDate && $dateCheck <= $sprintEndDueDate) {
                        $sprints[$sprint->id] = $sprint;
                    }
                }
                GenericModel::setCollection('profiles');
                foreach ($project->members as $memberId) {
                    $member = GenericModel::where('_id', '=', $memberId)->first();
                    $members[$memberId] = $member;
                }
            }
        }

        // Get all active tasks
        GenericModel::setCollection('tasks');
        foreach ($sprints as $sprint) {
            $sprintTasks = GenericModel::where('sprint_id', '=', $sprint->id)->get();
            foreach ($sprintTasks as $task) {
                if (empty($task->owner)) {
                    $tasks[$task->id] = $task;
                }
            }
        }

        // Ping on slack all users on active projects about unassigned tasks on active sprints
        $taskCount = [];

        foreach ($tasks as $task) {
            if (!key_exists($task->project_id, $taskCount)) {
                $taskCount[$task->project_id] = 1;
            } else {
                $taskCount[$task->project_id]++;
            }
        }

        if (!empty($taskCount)) {
            foreach ($activeProjects as $project) {
                if (!key_exists($project->_id, $taskCount)) {
                    continue;
                }
                foreach ($members as $member) {
                    if (in_array($member->_id, $project->members) && $member->slack) {
                        $recipient = '@' . $member->slack;
                        $projectName = $project->name;
                        $unassignedTasks = $taskCount[$project->_id];
                        $message = '*Reminder*:'
                            . 'There are * '
                            . $unassignedTasks
                            . '* unassigned tasks on active sprints'
                            . ', for project *'
                            . $projectName
                            . '*';
                        \SlackChat::message($recipient, $message);
                    }
                }
            }
        }
    }
}
