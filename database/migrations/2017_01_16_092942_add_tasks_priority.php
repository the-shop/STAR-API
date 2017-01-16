<?php

namespace {

    use Illuminate\Database\Migrations\Migration;
    use App\GenericModel;

    class AddTasksPriority extends Migration
    {
        /**
         * Run the migrations.
         *
         * @return void
         */
        public function up()
        {
            GenericModel::setCollection('tasks');
            $tasks = GenericModel::all();
            foreach ($tasks as $task) {
                $task->update([
                    'priority' => 'Medium'
                ]);
            }
        }

        /**
         * Reverse the migrations.
         *
         * @return void
         */
        public function down()
        {
            GenericModel::setCollection('tasks');
            $tasks = GenericModel::all();
            foreach ($tasks as $task) {
                $task->update([
                    'priority' => ''
                ]);
            }
        }
    }
}
