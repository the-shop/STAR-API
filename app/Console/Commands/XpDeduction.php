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
            $profileHashMap[$profile->_id] = $profile;
        }

        //print_r($profiles);
        $daysChecked = 0;
        $profilesInactive = [];

        do {
            // Check for the following day
            $date = new \DateTime();
            $cronTime = $date->format('U');
            GenericModel::setCollection('logs');
            $unixNow = $date->format('U') - (24 * 60 * 60 * $daysChecked);
            $unixDayAgo = $unixNow - 24 * 60 * 60;
            $hexNow = dechex($unixNow);
            $hexDayAgo = dechex($unixDayAgo);
            $logs = GenericModel::where('_id', '<', new ObjectID($hexNow . '0000000000000000'))
                ->where('_id', '>=', new ObjectID($hexDayAgo . '0000000000000000'))
                ->get();

            $logHashMap = [];
            foreach ($logs as $log) {
                $logHashMap[$log->id] = $log;
            }

            foreach ($profileHashMap as $user) {
                foreach ($logs as $log) {
                    if ($user->_id === $log->id && $daysChecked < 3) {
                        $profileHashMap[$log->id]->lastTimeActivityCheck = $cronTime;
                        $profileHashMap[$log->id]->save();
                        unset($profileHashMap[$log->id]);
                    } elseif (key_exists($user->_id, $logHashMap)) {
                        $profile = $profileHashMap[$log->id];
                        if ($daysChecked === 3) {
                            if ($profile->xp - 1 === 0) {
                                //set banned flag and save to DB
                                $profile->xp = 0;
                                $profile->banned = true;
                                $profile->save();
                            } else {
                                // New XP record creation and save to DB
                                $profile->xp--;
                                $profile->lastTimeActivityCheck = $cronTime;
                                $profile->save();
                            }
                            if ($daysChecked === 4) {
                                if ($profile->xp -2 <= 0) {
                                    $profile->xp = 0;
                                    $profile->banned = true;
                                    $profile->save();
                                } else {
                                    $profile->xp -= 2;
                                    $profile->lastTimeActivityCheck = $cronTime;
                                    $profile->save();
                                }
                            }
                        }
                    }
                }
            }
            $daysChecked++;
            unset ($logHashMap);
        } while (count($profileHashMap) > 0 || $daysChecked === 4);
    }
}
