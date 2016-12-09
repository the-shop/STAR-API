<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

class ConfigurationController extends Controller
{
    public function getConfiguration()
    {
        $internalSettings = \Config::get('sharedSettings.internal', []);
        $externalSettings = \Config::get('sharedSettings.externalConfiguration', []);
        $allsettings = [];
        $allsettings['internal'] = $internalSettings;

        foreach ($externalSettings as $name => $configs) {
            if (!key_exists($name, $allsettings)) {
                $allsettings[$name] = [];
            }

            foreach ($configs as $config) {
                if (!key_exists('resolver', $config)) {
                    continue;
                }
                $value = call_user_func([$config['resolver']['class'], $config['resolver']['method']]);
                $allsettings[$name][$config['settingName']] = $value;
            }
        }

        return $this->jsonSuccess($allsettings);
    }
}
