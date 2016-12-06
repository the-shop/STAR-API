<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\GenericModel;
use MongoDB\BSON\ObjectID;

class XpDeduction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'XpDeduction';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check user activity - deduct XP based on user inactivity';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        GenericModel::setCollection('profiles');
        $profiles = GenericModel::all();

        $profileHashMap = [];
        foreach ($profiles as $profile) {
            $profileHashMap[$profile->id] = $profile;
        }

        $daysChecked = 0;

        do {
            // Check for the following day
            $date = new \DateTime();
            GenericModel::setCollection('logs');
            $unixNow = $date->format('U') - (24 * 60 * 60 * $daysChecked);
            $unixDayAgo = $unixNow - 24 * 60 * 60;
            $hexNow = dechex($unixNow);
            $hexDayAgo = dechex($unixDayAgo);
            $logs = GenericModel::where('_id', '<', new ObjectID($hexNow . '0000000000000000'))
                ->where('_id', '>=', new ObjectID($hexDayAgo . '0000000000000000'))
                ->get();
            dd($logs);

            foreach ($logs as $log) {
                if (isset($profiles[$log->id]) && $profiles[$log->id]->lastTimeActivityCheck > $unixNow && isset($profileHashMap[$log->id])) {
                    unset($profiles[$log->id]);
                } else {
                    $profile = $profileHashMap[$log->id];
                    if ($daysChecked > 3) {
                        if ($profile->xp - 1 === 0) {
                            // Banned flag
                        } else {
                            $profile->xp--;
                            $profile->lastTimeActivityCheck = $unixNow;
                            $profile->save();
                            // New XP record creation and save to DB
                        }
                    }

                    if ($daysChecked > 10) {
                        if ($profile->xp - 2 < 0) {
                            // Set xp to 0
                            // Banned flag
                        } else {
                            $profile->xp -= 2;
                            $profile->lastTimeActivityCheck = $unixNow;
                            $profile->save();
                            // New XP record creation and save to DB
                        }
                    }
                }
            }

            $daysChecked++;
        } while (count($profiles) > 0 && count($logs) > 0);

    }
}
