<?php
namespace App\Http\Controllers;

use App\Exceptions\DynamicValidationException;
use App\GenericModel;

class TestingMigration extends Controller
{
    public function test()
    {
        GenericModel::setCollection('validations');
        $validations = GenericModel::all();

        $validationRules = [];
        $singleRecordRules = [];

        foreach ($validations as $validation) {
            if (!empty($validation['fields'])) {
                $validationRules[$validation['resource']] = $validation['fields'];
            }
        }

        foreach ($validationRules as $resource => $fields) {
            GenericModel::setCollection($resource);
            $collectionRecords = GenericModel::all();
            foreach ($collectionRecords as $record) {
                $singleRecordRules = $validationRules[$resource];
                $checkRecordFields = array_intersect_key($record->getAttributes(), $fields);
                foreach ($checkRecordFields as $key => $value) {
                    if (is_array($value)) {
                        foreach ($value as $index => $indexedValue) {
                            $singleRecordRules[$key . '.' . $index] = $singleRecordRules[$key];
                        }
                        unset($singleRecordRules[$key]);
                    }
                }
                $validator = \Validator::make(
                    $checkRecordFields,
                    $singleRecordRules
                );

                if ($validator->fails()) {
                    throw new DynamicValidationException($validator->errors()->all(), 400);
                }
                unset($singleRecordRules);
            }
        }

        return response()->json('Test passed! Everything ok!', 200);
    }
}
