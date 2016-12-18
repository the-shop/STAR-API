<?php

namespace App\Http\Middleware;

use App\GenericModel;
use Closure;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use App\Helpers\AclHelper;

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

        if ($user->admin === true) {
            return $next($request);
        }

        $acl = AclHelper::getAcl($user);

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
