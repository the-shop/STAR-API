<?php

namespace App\Services;

use App\GenericModel;
use App\Helpers\InputHandler;
use App\Profile;

/**
 * Class ProfilePerformance
 * @package App\Services
 */
class ProfilePerformance
{
    /**
     * @param Profile $profile
     * @param $unixStart
     * @param $unixEnd
     * @return array
     */
    public function aggregateForTimeRange(Profile $profile, $unixStart, $unixEnd)
    {
        // Get all profile projects
        GenericModel::setCollection('tasks');
        $profileTasks = GenericModel::where('owner', '=', $profile->id)->get();

        $estimatedHours = 0;
        $hoursDelivered = 0;
        $totalPayoutInternal = 0;
        $realPayoutInternal = 0;
        $totalPayoutExternal = 0;
        $realPayoutExternal = 0;
        $xpDiff = 0;

        $loadedProjects = [];

        // Let's aggregate task data
        foreach ($profileTasks as $task) {
            // Check if tasks is in selected time range and delivered
            $estimatedHours += (int) $task->estimatedHours;
            $deliveredTask = false;
            $taskInTimeRange = false;
            foreach ($task->task_history as $historyItem) {
                if (array_key_exists('status', $historyItem)
                    && ($historyItem['status'] === 'assigned'
                        || $historyItem['status'] === 'claimed')
                    && (int) $historyItem['timestamp'] / 1000 <= $unixEnd
                    && (int) $historyItem['timestamp'] / 1000 > $unixStart
                ) {
                    $taskInTimeRange = true;
                } elseif (array_key_exists('status', $historyItem) && $historyItem['status'] === 'qa_success') {
                    $deliveredTask = true;
                    break;
                }
            }

            // Skip task if not in time range
            if (!$taskInTimeRange) {
                continue;
            }

            // Get the project if not loaded already
            if (!array_key_exists($task->project_id, $loadedProjects)) {
                GenericModel::setCollection('projects');
                $loadedProjects[$task->project_id] = GenericModel::find($task->project_id);
            }

            $project = $loadedProjects[$task->project_id];
            $isInternalProject = $project->isInternal;

            if ($isInternalProject) {
                $totalPayoutInternal += $task->payout;
            } else {
                $totalPayoutExternal += $task->payout;
            }

            if ($deliveredTask === true) {
                $hoursDelivered += (int) $task->estimatedHours;

                if ($isInternalProject) {
                    $realPayoutInternal += $task->payout;
                } else {
                    $realPayoutExternal += $task->payout;
                }
            }
        }

        // Let's see the XP diff
        if ($profile->xp_id) {
            GenericModel::setCollection('xp');
            $xpRecord = GenericModel::find($profile->xp_id);
            if ($xpRecord) {
                foreach ($xpRecord->records as $record) {
                    $xpDiff += $record['xp'];
                }
            }
        }

        // Sum up totals
        $totalPayoutCombined = $totalPayoutExternal + $totalPayoutInternal;
        $realPayoutCombined = $realPayoutExternal + $realPayoutInternal;

        return [
            'estimatedHours' => $estimatedHours,
            'hoursDelivered' => $hoursDelivered,
            'totalPayoutExternal' => $totalPayoutExternal,
            'realPayoutExternal' => $realPayoutExternal,
            'totalPayoutInternal' => $totalPayoutInternal,
            'realPayoutInternal' => $realPayoutInternal,
            'totalPayoutCombined' => $totalPayoutCombined,
            'realPayoutCombined' => $realPayoutCombined,
            'xpDiff' => $xpDiff,
            'xpTotal' => $profile->xp,
        ];
    }

    public function forTask(GenericModel $task)
    {
        $task->confirmResourceOf('tasks');

        $taskHistory = is_array($task->task_history) ? $task->task_history : [];

        $taskHistoryOriginal = array_reverse($taskHistory);

        // We'll respond with array of performance per task owner (if task got reassigned for example)
        $response = [];

        // Let's find last task owner
        $taskOwner = null;
        $startSecond = null;
        foreach ($taskHistoryOriginal as $historyItem) {
            // Check if valid record
            if (array_key_exists('status', $historyItem) === false) {
                continue;
            }
            // Check for assignment record
            if ($historyItem['status'] === 'assigned' || $historyItem['status'] === 'claimed') {
                $taskOwner = $historyItem['user'];
                $startSecond = InputHandler::getUnixTimestamp($historyItem['timestamp']);
                break;
            }
        }

        // If task was never assigned, there's no performance, respond with empty array
        if ($taskOwner === null) {
            return $response;
        }

        // Set defaults
        $userPerformance = [
            'taskCompleted' => false,
            'workSeconds' => 0,
            'qaSeconds' => 0,
            'pauseSeconds' => 0,
        ];

        $isWorking = true;
        $isQa = false;
        $isPaused = false;

        // Now let's start tracking time from time owner took over the task
        foreach ($taskHistory as $key => $historyItem) {
            // Check if valid record
            if (array_key_exists('status', $historyItem) === false) {
                continue;
            }

            $itemSecond = InputHandler::getUnixTimestamp($historyItem['timestamp']);

            // Let's skip records before last task owner for now including assignment time
            if ($itemSecond <= $startSecond) {
                continue;
            }
            
            // Check for assignment record
            if ($isWorking) {
                $userPerformance['workSeconds'] += $itemSecond - $startSecond;
            } elseif ($isPaused) {
                $userPerformance['pauseSeconds'] += $itemSecond - $startSecond;
            } elseif ($isQa) {
                $userPerformance['qaSeconds'] += $itemSecond - $startSecond;
            }

            $isWorking = $historyItem['status'] === 'resumed'
                || $historyItem['status'] === 'assigned'
                || $historyItem['status'] === 'claimed';

            $isQa = $historyItem['status'] === 'qa_success'
                || $historyItem['status'] === 'qa_ready'
                || $historyItem['status'] === 'qa_fail';

            $isPaused = $historyItem['status'] === 'paused';

            $startSecond = $itemSecond;
        }

        $userPerformance['taskCompleted'] = $task->passed_qa === true;

        $response[$taskOwner] = $userPerformance;

        return $response;
    }

    /**
     * Calculates payout, xp award and estimate for specific $profile <-> $task relation
     *
     * @param Profile $profile
     * @param GenericModel $task
     * @return array
     */
    public function getTaskValuesForProfile(Profile $profile, GenericModel $task)
    {
        $task->confirmResourceOf('tasks');

        $xp = (float) $profile->xp;

        $taskComplexity = (int) $task->complexity;

        $estimatedHours = (int) $task->estimatedHours * 1000 / $profile->xp;

        // Adjust payout based on profile XP
        $taskPayout = min($xp, 1000) / 1000 * (float) $task->payout;
        // Award xp based on complexity
        $xpAward = $profile->xp <= 200 ? $taskComplexity / 10 * $estimatedHours * $profile->xp / 10 : 0;

        $out = [];
        $out['payout'] = $taskPayout;
        $out['xp'] = $xpAward;
        $out['estimatedHours'] = $estimatedHours;

        return $out;
    }
}
