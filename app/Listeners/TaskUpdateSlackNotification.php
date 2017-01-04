<?php

namespace App\Listeners;

use App\Events\TaskUpdateSlackNotify;
use App\GenericModel;

class TaskUpdateSlackNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

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
        $project = GenericModel::where('_id', '=', $event->tasks->project_id)->first();

        GenericModel::setCollection('profiles');
        $projectOwner = GenericModel::where('_id', '=', $project->owner)->first();
        $taskOwner = GenericModel::where('_id', '=', $event->tasks->owner)->first();
        $recipients = [
            '@' . $projectOwner->slack,
            '@' . $taskOwner->slack
        ];
        $message = 'Task *'
            . $event->tasks->title
            . '* was just updated by *'
            . \Auth::user()->name
            . '*';

        foreach ($recipients as $recipient) {
            \SlackChat::message($recipient, $message);
        }
        GenericModel::setCollection($preSetCollection);
    }
}
