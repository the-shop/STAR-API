<?php

namespace App\Http\Middleware;

use App\GenericModel;
use Closure;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;


class Acl
{
    /**
     * Handle an incoming request. Check user route permissions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $routeUri = $request->route()->getUri();
        $routeMethod = $request->method();

        $user = Auth::user();
        $defaultRole = \Config::get('sharedSettings.internalConfiguration.default_role');

        if ($user->admin === true) {
            return $next($request);
        }

        GenericModel::setCollection('acl');

        if ($user->aclId) {
            $acl = GenericModel::where('_id', '=', $user->aclId)->first();
            if ($acl !== null) {
                $aclAttributes = $acl->getAttributes();
                if (key_exists($routeMethod, $aclAttributes['allows'])) {
                    if (!in_array($routeUri, $aclAttributes['allows'][$routeMethod])) {
                        return response(['Insufficient permissions.', 403]);
                    }
                }
            } else {
                throw new MethodNotAllowedException([]);
            }
        } else {
            $acl = GenericModel::where('name', '=', $defaultRole)->first();
            $aclAttributes = $acl->getAttributes();
            if (key_exists($routeMethod, $aclAttributes['allows'])) {
                if (!in_array($routeUri, $aclAttributes['allows'][$routeMethod])) {
                    return response(['Insufficient permissions.', 403]);
                }
            }

        }

        return $next($request);
    }
}
