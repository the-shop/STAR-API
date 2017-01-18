<?php
namespace {

    use Illuminate\Database\Seeder;

    class AdapterRulesSeeder extends Seeder
    {
        /**
         * Run the database seeds.
         *
         * @return void
         */
        public function run()
        {
            DB::collection('adapter-rules')->delete();
            DB::collection('adapter-rules')->insert(
                [
                    [
                        'resource' => 'tasks',
                        'resolver' => [
                            'class' => \App\Adapters\Task::class,
                            'method' => 'process'
                        ]
                    ],
                ]
            );
        }
    }
}
