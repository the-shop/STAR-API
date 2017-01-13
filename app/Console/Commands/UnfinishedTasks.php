<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\GenericModel;

class UnfinishedTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unfinished:tasks:auto-move';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cron that moves unfinished tasks from sprint to following sprint on sprint end date.';

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
        //get all projects
        GenericModel::setCollection('projects');
        $projects = GenericModel::all();

        //get all admin users
        GenericModel::setCollection('profiles');
        $admins = GenericModel::where('admin', '=', true)->get();

        $activeProjects = [];
        $sprints = [];

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

        $sprintEndedTasks = [];
        $endedSprints = [];
        $futureSprints = [];
        $futureSprintsStartDates = [];

        $date = new \DateTime();
        $unixNow = $date->format('U');
        $checkDay = date('Y-m-d', $unixNow);
        $unixDayAgo = $unixNow - 24 * 60 * 60;
        $checkDayAgo = date('Y-m-d', $unixDayAgo);

        GenericModel::setCollection('tasks');
        foreach ($sprints as $sprint) {
            $sprintStartDueDate = date('Y-m-d', $sprint->start);
            $sprintEndDueDate = date('Y-m-d', $sprint->end);
            if ($sprintEndDueDate === $checkDayAgo && !empty($sprint->tasks)) {
                foreach ($sprint->tasks as $taskId) {
                    $task = GenericModel::where('_id', '=', $taskId)->first();
                    if (empty($task->owner)) {
                        $sprintEndedTasks[$taskId] = $task;
                        $endedSprints[$sprint->project_id] = $sprint;
                    }
                }
            } elseif ($unixNow < $sprint->start || $checkDay === $sprintStartDueDate) {
                $futureSprints[] = $sprint;
                $futureSprintsStartDates[$sprint->project_id][] = $sprint->start;
            }
        }

        if (!empty($sprintEndedTasks)) {
            //ping on slack admins if there are no future sprints created so we can move unfinished tasks from sprint to
            //following sprint on sprint end date
            if (empty($futureSprints)) {
                foreach ($admins as $admin) {
                    if ($admin->slack) {
                        $recipient = '@' . $admin->slack;
                        $message = 'Hey! There are no future sprints created to move unfinished tasks from ended ' .
                            'sprints!';
                        \SlackChat::message($recipient, $message);
                    }
                }
            } else {
                foreach ($futureSprints as $sprint) {
                    if ($sprint->start === min($futureSprintsStartDates[$sprint->project_id])) {
                        foreach ($sprintEndedTasks as $task) {
                            if ($task->project_id === $sprint->project_id) {
                                $task->sprint_id = $sprint->_id;
                                $task->save();

                                $newSprintTasks = $sprint->tasks;
                                $newSprintTasks[] = $task->_id;
                                $sprint->tasks = $newSprintTasks;
                                $sprint->save();

                                $oldSprintTaskUpdate = array_values(array_diff($endedSprints[$sprint->project_id]->
                                tasks, [$task->_id]));
                                $oldSprint = $endedSprints[$sprint->project_id];
                                $oldSprint->tasks = $oldSprintTaskUpdate;
                                $oldSprint->save();
                                $this->info('Task' . $task->title . 'moved to sprint ' . $sprint->title);
                            }
                        }
                    }
                }
            }
        }
    }
}
