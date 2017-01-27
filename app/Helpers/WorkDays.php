<?php

namespace App\Helpers;

use Carbon\Carbon;

/**
 * Return list of work days (week days)
 * Class WorkDays
 * @package App\Helpers
 */
class WorkDays
{
    public static function getWorkDays()
    {
        $firstDayOfMonth = Carbon::now()->firstOfMonth();
        $lastDayOfMonth = Carbon::now()->endOfMonth();

        $dates = [];

        for ($date = $firstDayOfMonth; $date->lte($lastDayOfMonth); $date->addDay()) {
            if ($date->isWeekday()) {
                $dates[] = $date->format('Y-m-d');
            }
        }

        return $dates;
    }
}
