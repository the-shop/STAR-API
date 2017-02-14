<?php

namespace App\Listeners;

use App\GenericModel;

class ProjectDelete
{
    /**
     * Handle the event.
     * @param \App\Events\ProjectDelete $event
     */
    public function handle(\App\Events\ProjectDelete $event)
    {
        $project = $event->model;

        $preSetCollection = GenericModel::getCollection();
        //delete all project sprints
        GenericModel::setCollection('sprints');
            $projectSprints = GenericModel::where('project_id', '=', $project->id)->get();
        foreach ($projectSprints as $sprint) {
            $deletedSprint = $sprint->replicate();
            $deletedSprint['collection'] = 'sprints_deleted';
            if ($deletedSprint->save()) {
                $sprint->delete();
            }
        }
        //delete all project tasks
        GenericModel::setCollection('tasks');
        $projectTasks = GenericModel::where('project_id', '=', $project->id)->get();
        foreach ($projectTasks as $task) {
            $deletedTask = $task->replicate();
            $deletedTask['collection'] = 'tasks_deleted';
            if ($deletedTask->save()) {
                $task->delete();
            }
        }
        GenericModel::setCollection($preSetCollection);
    }
}
