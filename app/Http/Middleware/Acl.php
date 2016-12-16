<?php

namespace App\Http\Middleware;

use App\GenericModel;
use Closure;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use App\Helpers\AclValidator;

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

        AclValidator::validateRoute($user, $defaultRole, $routeUri, $routeMethod);

        return $next($request);
    }
}
