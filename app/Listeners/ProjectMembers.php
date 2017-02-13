<?php

namespace App\Listeners;

use App\GenericModel;
use App\Helpers\Slack;
use App\Profile;
use Illuminate\Support\Facades\Config;

class ProjectMembers
{
    const STATUS_ADDED = true;
    const STATUS_REMOVED = false;

    /**
     * Handle the event
     * @param \App\Events\ProjectMembers $event
     */
    public function handle(\App\Events\ProjectMembers $event)
    {
        $project = $event->model;

        if ($project->isDirty()) {
            $oldFields = $project->getOriginal();
            $updatedFields = $project->getDirty();
            if ($project['collection'] === 'projects' && key_exists('members', $updatedFields)
                && !empty($updatedFields['members'])
            ) {
                //if user is added to project send slack notification
                foreach ($updatedFields['members'] as $newMemberId) {
                    if (!in_array($newMemberId, $oldFields['members'])) {
                        $member = Profile::find($newMemberId);
                        if ($member->slack) {
                            $this->slackMessageUser($member, $project, self::STATUS_ADDED);
                        }
                    }
                }
                //if user is removed from project send slack notification
                foreach ($oldFields['members'] as $oldMemberId) {
                    if (!in_array($oldMemberId, $updatedFields['members'])) {
                        $member = Profile::find($oldMemberId);
                        if ($member->slack) {
                            $this->slackMessageUser($member, $project, self::STATUS_REMOVED);
                        }
                    }
                }
            }
        }
    }

    /**
     * Helper method to notify user on Slack if added or removed from project
     * @param Profile $profile
     * @param GenericModel $project
     * @param $status
     */
    private function slackMessageUser(Profile $profile, GenericModel $project, $status)
    {
        $webDomain = Config::get('sharedSettings.internalConfiguration.webDomain');
        $recipient = '@' . $profile->slack;
        $message = 'Hey, you\'ve just been'
            . ($status === self::STATUS_ADDED ? ' added to ' : ' removed from ')
            . 'project '
            . $project->name
            . ' ('
            . $webDomain
            . 'projects/'
            . $project->_id
            . ')';

        Slack::sendMessage($recipient, $message, Slack::HIGH_PRIORITY);
    }
}
