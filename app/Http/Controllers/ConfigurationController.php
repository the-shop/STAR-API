<?php

namespace App\Http\Controllers;

use App\Helpers\Configuration;

class ConfigurationController extends Controller
{
    /**
     * Get Configuration from sharedSettings
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConfiguration()
    {
        $allSettings = Configuration::getConfiguration();

        if ($allSettings === false ) {
            $this->jsonError(['Empty settings list.'], 404);
        }

        return $this->jsonSuccess($allSettings);
    }
}
