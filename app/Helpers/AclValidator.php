<?php

namespace App\Helpers;

use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use App\GenericModel;

class AclValidator
{
    /**
     * Acl validator
     * @param $user
     * @param $defaultRole
     * @param $routeMethod
     * @param $routeUri
     * @return bool
     */
    public static function validateRoute($user , $defaultRole, $routeMethod, $routeUri)
    {
        if ($user->admin === true) {
            return true;
        }

        GenericModel::setCollection('acl');

        //check if user has aclId field set, otherwise use default role
        if ($user->aclId) {
            $acl = GenericModel::where('_id', '=', $user->aclId)->first();
        } else {
            $acl = GenericModel::where('name', '=', $defaultRole)->first();
        }

        //validate permissions
        if (!$acl instanceof GenericModel) {
            throw new MethodNotAllowedHttpException([], 'Insufficient permissions.');
        }

        if (!key_exists($routeMethod, $acl->allows)) {
            throw new MethodNotAllowedHttpException([], 'Insufficient permissions.');
        }

        if (!in_array($routeUri, $acl->allows[$routeMethod])) {
            throw new MethodNotAllowedHttpException([], 'Insufficient permissions.');
        }

        return true;
    }
}