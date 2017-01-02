<?php

namespace App\Helpers;

use App\GenericModel;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class AclHelper
{
    /**
     * Get user ACL role
     * @param $user
     * @return mixed
     */
    public static function getAcl($user)
    {
        GenericModel::setCollection('acl');

        //check if user has aclId field set, otherwise use default role
        if ($user->aclId) {
            $acl = GenericModel::where('_id', '=', $user->aclId)->first();
        } else {
            $defaultRole = \Config::get('sharedSettings.internalConfiguration.default_role');
            $acl = GenericModel::where('name', '=', $defaultRole)->first();
        }

        //check if user has role
        if (!$acl instanceof GenericModel) {
            throw new MethodNotAllowedHttpException([], 'Insufficient permissions.');
        }

        return $acl;
    }
}