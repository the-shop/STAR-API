<?php

namespace App\Http\Middleware;

use Closure;

class MultipleAppSupport
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $requestDbName = strtolower($request->route('appName'));
        $dbName = \Config::get('database.connections.mongodb.database');

        if ($dbName !== $requestDbName) {
            \Config::set('database.connections.mongodb.database', $requestDbName);
        }

        return $next($request);
    }
}
