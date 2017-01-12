<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\SprintReminder::class,
        Commands\XpDeduction::class,
        Commands\UnfinishedTasks::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
         $schedule->command('sprint:remind')
                  ->twiceDaily(8, 14);
         $schedule->command('xp:activity:auto-deduct')
                  ->dailyAt('13:00');
         $schedule->command('unfinished:tasks:auto-move')
                  ->dailyAt('00:01');
    }
}
