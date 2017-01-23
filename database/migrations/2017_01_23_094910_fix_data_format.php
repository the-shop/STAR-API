<?php

namespace {

    use App\GenericModel;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Database\Migrations\Migration;

    class FixDataFormat extends Migration
    {
        /**
         * Run the migrations.
         *
         * @return void
         */
        public function up()
        {
            GenericModel::setCollection('validations');
            $validations = GenericModel::all();

            $validationRules = [];
            foreach ($validations as $validation) {
                if (!empty($validation['fields'])) {
                    $validationRules[$validation['resource']] = $validation['fields'];
                }
            }

            foreach ($validationRules as $resource => $fields) {
                GenericModel::setCollection($resource);
                $collectionData = GenericModel::all();
            }
        }

        /**
         * Reverse the migrations.
         *
         * @return void
         */
        public function down()
        {
            //
        }
    }
}
