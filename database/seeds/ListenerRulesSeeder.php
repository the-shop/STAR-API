<?php

namespace {

    use Illuminate\Database\Seeder;

    class ListenerRulesSeeder extends Seeder
    {
        /**
         * Run the database seeds.
         *
         * @return void
         */
        public function run()
        {
            DB::collection('listener-rules')->delete();
            DB::collection('listener-rules')->insert(
                [
                    [
                        'resource' => 'tasks',
                        'event' => 'create',
                        'listeners' => []
                    ],
                    [
                        'resource' => 'tasks',
                        'event' => 'update',
                        'listeners' => [
                            'App\Events\TaskUpdateSlackNotify' => [
                                'App\Listeners\TaskUpdateSlackNotification',
                            ],
                            'App\Events\ModelUpdate' => [
                                'App\Listeners\TaskUpdateXP',
                            ]
                        ]
                    ],
                ]
            );
        }
    }
}