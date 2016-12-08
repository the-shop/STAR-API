<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

class ConfigurationController extends Controller
{
    public function getConfiguration()
    {
        $config = \Config::get('sharedSettings');
        dd($config);
        return $this->jsonSuccess($config);
    }
}
