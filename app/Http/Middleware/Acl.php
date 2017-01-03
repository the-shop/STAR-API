<?php

namespace App\Http\Middleware;

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

        if ($user && $user->admin === true) {
            return $next($request);
        }

        $acl = AclHelper::getAcl($user);

        // If there's no ACL defined, presume no permissions
        if (!key_exists($routeMethod, $acl->allows) || !in_array($routeUri, $acl->allows[$routeMethod])) {
            throw new MethodNotAllowedHttpException([], 'Insufficient permissions.');
        }

        return $next($request);
    }
}
