<?php

namespace App\Http\Controllers;

/**
 * Class DatabaseController
 * @package App\Http\Controllers
 */
class DatabaseController extends Controller
{
    /**
     * Return list of all Database collections
     * @return \Illuminate\Http\JsonResponse
     */
    public function listCollections()
    {
        $collectionList = \DB::listCollections();
        $result = [];

        foreach ($collectionList as $list) {
            if ($list->getName() === 'system.indexes') {
                continue;
            }
            $result[] = $list->getName();
        }

        return $this->jsonSuccess($result);
    }
}
