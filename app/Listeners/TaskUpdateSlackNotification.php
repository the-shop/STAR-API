<?php

namespace App\Listeners;

use App\Events\TaskUpdateSlackNotify;
use App\GenericModel;

class TaskUpdateSlackNotification
{
    /**
     * Handle the event.
     *
     * @param  TaskUpdateSlackNotify $event
     * @return void
     */
    public function handle(TaskUpdateSlackNotify $event)
    {
        $preSetCollection = GenericModel::getCollection();
        GenericModel::setCollection('projects');
        $project = GenericModel::find($event->model->project_id);

        GenericModel::setCollection('profiles');
        $projectOwner = GenericModel::find($project->acceptedBy);
        $taskOwner = GenericModel::find($event->model->owner);

        // Let's build a list of recipients
        $recipients = [];

        if ($projectOwner && $projectOwner->slack) {
            $recipients[] = '@' . $projectOwner->slack;
        }

        if ($taskOwner->slack) {
            $recipients[] = '@' . $taskOwner->slack;
        }

        // Make sure that we don't double send notifications if task owner is project owner
        $recipients = array_unique($recipients);

        $message = 'Task *'
            . $event->model->title
            . '* was just updated by *'
            . \Auth::user()->name
            . '*';

        foreach ($recipients as $recipient) {
            \SlackChat::message($recipient, $message);
        }

        GenericModel::setCollection($preSetCollection);
    }
}
