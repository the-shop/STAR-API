<?php

namespace App\Services;

use App\GenericModel;
use App\Profile;
use Illuminate\Support\Facades\Config;

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
    public function forTimeRange(Profile $profile, $unixStart, $unixEnd)
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
}
