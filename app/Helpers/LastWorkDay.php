<?php

namespace App\Helpers;

use Carbon\Carbon;

/**
 * Return date of last day of month that is not weekend
 * Class LastWorkDay
 * @package App\Helpers
 */
class LastWorkDay
{
    public static function getLastWorkingDay()
    {
        $firstDayOfMonth  = Carbon::now()->firstOfMonth();
        $lastDayOfMonth = Carbon::now()->endOfMonth();

        $dates = [];

        for ($date = $firstDayOfMonth; $date->lte($lastDayOfMonth); $date->addDay()) {
            if ($date->isWeekday()) {
                $dates[] = $date->format('Y-m-d');
            }
        }

        return end($dates);
    }
}
