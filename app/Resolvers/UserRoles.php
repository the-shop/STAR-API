<?php

namespace App\Resolvers;

use App\GenericModel;

class UserRoles
{
    public static function getRoles()
    {
        $preSetCollection = GenericModel::getCollection();
        GenericModel::setCollection('user-roles');
        $userRoles = GenericModel::first();

        $resolvedRoles = array_map('strtolower', $userRoles->userRoles);

        GenericModel::setCollection($preSetCollection);

        return $resolvedRoles;
    }
}
