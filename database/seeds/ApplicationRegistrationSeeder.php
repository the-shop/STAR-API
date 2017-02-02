<?php

namespace {

    use Illuminate\Database\Seeder;
    use Illuminate\Support\Facades\Config;
    use Illuminate\Support\Facades\Schema;

    class ApplicationRegistrationSeeder extends Seeder
    {
        /**
         * Run the database seeds.
         *
         * @return void
         */
        public function run()
        {
            $coreDatabaseName = Config::get('sharedSettings.internalConfiguration.coreDatabaseName');
            Config::set('database.connections.mongodb.database', $coreDatabaseName);
            $defaultDb = Config::get('database.default');
            DB::purge($defaultDb);
            DB::connection($defaultDb);
            Schema::create('applications');
        }
    }
}
