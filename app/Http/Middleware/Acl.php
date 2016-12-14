<?php

namespace App\Http\Middleware;

use App\GenericModel;
use Closure;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Acl
{
    /**
     * Handle an incoming request. Check user route permissions.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
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

        return $next($request);
    }
}
