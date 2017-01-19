<?php

namespace App\Services;

use App\GenericModel;
use App\Helpers\InputHandler;
use App\Profile;
use Illuminate\Support\Facades\Auth;
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
            // Adjust values for profile we're looking at
            $mappedValues = self::getTaskValuesForProfile($profile, $task);
            foreach ($mappedValues as $key => $value) {
                $task->{$key} = $value;
            }

            // Check if tasks is in selected time range and delivered
            $estimatedHours += (float) $task->estimatedHours;
            $deliveredTask = false;
            $taskInTimeRange = false;
            foreach ($task->task_history as $historyItem) {
                if (array_key_exists('status', $historyItem)
                    && ($historyItem['status'] === 'assigned'
                        || $historyItem['status'] === 'claimed')
                    && InputHandler::getUnixTimestamp($historyItem['timestamp']) <= $unixEnd
                    && InputHandler::getUnixTimestamp($historyItem['timestamp']) > $unixStart
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

        $out = [
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

        $out = array_merge($out, $this->calculateSalary($out, $profile));

        return $out;
    }

    public function perTask(GenericModel $task)
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

        $taskComplexity = max((int) $task->complexity, 1);

        $estimatedHours = (float) $task->estimatedHours * 1000 / min($xp, 1000);

        // Award xp based on complexity
        $xpAward = $xp <= 200 ? $taskComplexity * $estimatedHours * 10 / $xp : 0;

        $out = [];
        $out['xp'] = $xpAward;
        $out['estimatedHours'] = $estimatedHours;

        return $out;
    }

    /**
     * Calculates salary based on performance in time range
     *
     * @param array $aggregated
     * @param Profile $profile
     * @return array
     */
    private function calculateSalary(array $aggregated, Profile $profile)
    {
        $employeeConfig = Config::get('sharedSettings.internalConfiguration.employees.roles');

        $role = $profile->employeeRole;

        if (!isset($employeeConfig[$role])) {
            return [
                'minimalGrossPayout' => 0,
                'realGrossPayout' => 0,
                'grossBonusPayout' => 0,
                'costXpBasedPayout' => 0,
                'employeeRole' => 'Not set',
                'amountReached' => $aggregated['realPayoutCombined'],
                'roleMinimumReached' => false,
                'roleMinimum' => 0,
            ];
        }

        $minimum = $employeeConfig[$role]['minimumEarnings'];
        $coefficient = $employeeConfig[$role]['coefficient'];
        $xpEntryPoint = $employeeConfig[$role]['xpEntryPoint'];

        $realPayout = $minimum;

        // Adjust payout based on XP
        $xpInRange = (float) $profile->xp - $xpEntryPoint;

        if ($xpInRange < 0) {
            $xpInRange = 0;
        } elseif ($xpInRange > 200) {
            $xpInRange = 200;
        }

        // 50% of everything over minimum (from external projects) goes to bonus
        if ($aggregated['realPayoutExternal'] > $minimum) {
            $realPayout = $minimum + ($aggregated['realPayoutExternal'] - $minimum) / 2;
        }

        $costReal = $this->calculateSalaryCostForAmount($realPayout, $coefficient);
        $xpBasedPayout = $costReal * $coefficient * $xpInRange / 2 / 100;
        if ($aggregated['realPayoutCombined'] > $minimum) {
            $costReal += $xpBasedPayout;
        }

        $grossReal = $this->calculateSalaryGrossForAmount($costReal);

        $costGrossMinimum = $this->calculateSalaryCostForAmount($realPayout, $coefficient);
        $grossMinimum = $this->calculateSalaryGrossForAmount($costGrossMinimum);

        $aggregated['costTotal'] = round($costReal, 4);
        $aggregated['minimalGrossPayout'] = round($grossMinimum, 4);
        $aggregated['realGrossPayout'] = round($grossReal, 4);
        $aggregated['grossBonusPayout'] = round($grossReal - $grossMinimum, 4);
        $aggregated['costXpBasedPayout'] = $xpBasedPayout;
        $aggregated['employeeRole'] = $role;
        $aggregated['amountReached'] = $aggregated['realPayoutCombined'];
        $aggregated['roleMinimumReached'] = $grossReal > $grossMinimum;
        $aggregated['roleMinimum'] = $minimum;

        return $aggregated;
    }

    /**
     * Helper to calculate salary based of earned amount
     *
     * @param $forAmount
     * @param $coefficient
     * @return float
     */
    private function calculateSalaryCostForAmount($forAmount, $coefficient)
    {
        $totalCost = $forAmount - $forAmount * $coefficient * 2;

        return $totalCost;
    }

    /**
     * Helper for salary cost conversion to gross payout
     *
     * @param $totalGross
     * @return float
     */
    private function calculateSalaryGrossForAmount($totalGross)
    {
        // 17.2% is fixed cost over gross salary in Croatia
        $gross = $totalGross / 1.172;
        return $gross;
    }
}
