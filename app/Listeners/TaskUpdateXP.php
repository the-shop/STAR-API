<?php

namespace App\Listeners;

use App\Events\ModelUpdate;
use App\GenericModel;
use App\Helpers\InputHandler;
use App\Profile;
use App\Services\ProfilePerformance;
use Illuminate\Support\Facades\Config;

/**
 * Class TaskUpdateXP
 * @package App\Listeners
 */
class TaskUpdateXP
{
    /**
     * @param ModelUpdate $event
     * @return bool
     */
    public function handle(ModelUpdate $event)
    {
        $task = $event->model;

        $profilePerformance = new ProfilePerformance();

        GenericModel::setCollection('tasks');

        $taskPerformance = $profilePerformance->perTask($task);

        // Get project owner id
        GenericModel::setCollection('projects');
        $project = GenericModel::find($task->project_id);
        $projectOwner = Profile::find($project->acceptedBy);

        foreach ($taskPerformance as $profileId => $taskDetails) {
            if ($taskDetails['taskCompleted'] === false) {
                return false;
            }

            $taskOwnerProfile = Profile::find($profileId);

            GenericModel::setCollection('tasks');
            $mappedValues = $profilePerformance->getTaskValuesForProfile($taskOwnerProfile, $task);
            GenericModel::setCollection('projects');
            foreach ($mappedValues as $key => $value) {
                $task->{$key} = $value;
            }

            $estimatedSeconds = max(InputHandler::getInteger($task->estimatedHours) * 60 * 60, 1);

            $secondsWorking = $taskDetails['workSeconds'];

            $coefficient = $secondsWorking / $estimatedSeconds;

            $webDomain = Config::get('sharedSettings.internalConfiguration.webDomain');
            $taskLink = '['
                . $task->title
                . ']('
                . $webDomain
                . 'projects/'
                . $task->project_id
                . '/sprints/'
                . $task->sprint_id
                . '/tasks/'
                . $task->_id
                . ')';

            if ($secondsWorking > 0 && $estimatedSeconds > 1) {
                $xpDiff = 0;
                $message = null;
                $taskXp = (float) $taskOwnerProfile->xp <= 200 ? (float) $task->xp : 0;
                if ($coefficient < 0.75) {
                    $xpDiff = $taskXp + 3 * $this->getDurationCoefficient($task, $taskOwnerProfile);
                    $message = 'Early task delivery: ' . $taskLink;
                } elseif ($coefficient >= 0.75 && $coefficient <= 1) {
                    $xpDiff = $taskXp;
                    $message = 'Task delivery: ' . $taskLink;
                } elseif ($coefficient > 1 && $coefficient <= 1.1) {
                    $xpDiff =  -1;
                    $message = 'Late task delivery: ' . $taskLink;
                } elseif ($coefficient > 1.1 && $coefficient <= 1.25) {
                    $xpDiff = -2;
                    $message = 'Late task delivery: ' . $taskLink;
                } elseif ($coefficient > 1.25) {
                    $xpDiff = -3;
                    $message = 'Late task delivery: ' . $taskLink;
                } else {
                    // TODO: handle properly
                }

                if ($xpDiff !== 0) {
                    $profileXpRecord = $this->getXpRecord($taskOwnerProfile);

                    $records = $profileXpRecord->records;
                    $records[] = [
                        'xp' => $xpDiff,
                        'details' => $message,
                        'timestamp' => (int) ((new \DateTime())->format('U') . '000') // Microtime
                    ];
                    $profileXpRecord->records = $records;
                    $profileXpRecord->save();

                    $taskOwnerProfile->xp += $xpDiff;
                    $taskOwnerProfile->save();
                }

                if ($taskDetails['qaSeconds'] > 24 * 60 * 60) {
                    $poXpDiff = -3;
                    $poMessage = 'Failed to review PR in time for ' . $taskLink;
                } else {
                    $poXpDiff = 0.25;
                    $poMessage = 'Review PR in time for ' . $taskLink;
                }

                if ($projectOwner) {
                    $projectOwnerXpRecord = $this->getXpRecord($projectOwner);
                    $records = $projectOwnerXpRecord->records;
                    $records[] = [
                        'xp' => $poXpDiff,
                        'details' => $poMessage,
                        'timestamp' => (int) ((new \DateTime())->format('U') . '000') // Microtime
                    ];
                    $projectOwnerXpRecord->records = $records;
                    $projectOwnerXpRecord->save();

                    $projectOwner->xp += $poXpDiff;
                    $projectOwner->save();
                }
            }
        }

        return true;
    }

    /**
     * @param Profile $profile
     * @return GenericModel
     */
    private function getXpRecord(Profile $profile)
    {
        $oldCollection = GenericModel::getCollection();
        GenericModel::setCollection('xp');
        if (!$profile->xp_id) {
            $profileXp = new GenericModel(['records' => []]);
            $profileXp->save();
            $profile->xp_id = $profileXp->_id;
        } else {
            $profileXp = GenericModel::find($profile->xp_id);
        }
        GenericModel::setCollection($oldCollection);

        return $profileXp;
    }

    /**
     * @param GenericModel $task
     * @param Profile $taskOwner
     * @return float|int
     */
    private function getDurationCoefficient(GenericModel $task, Profile $taskOwner)
    {
        $profileCoefficient = 1;
        if ((float) $taskOwner->xp > 200 && (float) $taskOwner->xp <= 400) {
            $profileCoefficient = 0.8;
        } elseif ((float) $taskOwner->xp > 400 && (float) $taskOwner->xp <= 600) {
            $profileCoefficient = 0.6;
        } elseif ((float) $taskOwner->xp > 600 && (float) $taskOwner->xp <= 800) {
            $profileCoefficient = 0.4;
        } elseif ((float) $taskOwner->xp > 800 && (float) $taskOwner->xp <= 1000) {
            $profileCoefficient = 0.2;
        } elseif ((float) $taskOwner->xp > 1000) {
            $profileCoefficient = 0.1;
        }

        if ((int) $task->estimatedHours < 9) {
            return ((int) $task->estimatedHours / 10) * $profileCoefficient;
        }

        return 1;
    }
}
