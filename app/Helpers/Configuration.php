<?php

namespace App\Helpers;

use App\Http\Controllers\Controller;

class Configuration extends Controller
{

    public function configuration($email = null)
    {
        $internalSettings = \Config::get('sharedSettings.internalConfiguration', []);
        $externalSettings = \Config::get('sharedSettings.externalConfiguration', []);
        $allsettings = [];
        $allsettings['internal'] = $internalSettings;

        if (empty($internalSettings) && empty($externalSettings)) {
            return $this->jsonError(['Empty settings list.'], 404);
        }

        foreach ($externalSettings as $name => $configs) {
            foreach ($configs as $config) {
                if (!key_exists('resolver', $config)) {
                    continue;
                }
                try {
                    $value = call_user_func([$config['resolver']['class'], $config['resolver']['method']]);
                } catch (\Exception $e) {
                    continue;
                }
                $allsettings[$name][$config['settingName']] = $value;
            }
        }

        // return slack company fields for email upon new user registration
        if ($email !== null) {
            $response = [];
            $response['teamName'] = $allsettings['slack']['teamInfo']->team->name;
            $response['teamDomain'] = $allsettings['slack']['teamInfo']->team->domain;
            $response['EmailDomain'] = $allsettings['slack']['teamInfo']->team->email_domain;

            return $response;
        }

        return $this->jsonSuccess($allsettings);
    }
}