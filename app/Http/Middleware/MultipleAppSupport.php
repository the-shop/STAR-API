<?php

namespace App\Http\Middleware;

use App\Helpers\AuthHelper;
use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Middleware\BaseMiddleware;

class MultipleAppSupport extends BaseMiddleware
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
        $coreDbName = Config::get('sharedSettings.internalConfiguration.coreDatabaseName');
        if ($requestDbName === $coreDbName) {
            return $next($request);
        }

        // Set database connection to "accounts"
        AuthHelper::setDatabaseConnection();

        $token = $this->auth->setRequest($request)->getToken();
        if ($token) {
            $user = $this->auth->authenticate($token);

            $this->events->fire('tymon.jwt.valid', $user);

            // Set authenticated user to app instance so we can call it from AuthHelper
            $app = \App::getFacadeRoot();
            $app->instance('authenticatedUser', $user);
        }

        $dbName = Config::get('database.connections.mongodb.database');

        // Get list of all databases
        $listExistingDatabases = DB::connection('mongodbAdmin')->command(['listDatabases' => true]);

        $databaseNames = [];
        foreach ($listExistingDatabases as $dbResult) {
            $databasesBsonList = $dbResult->databases->getArrayCopy();
            foreach ($databasesBsonList as $dbInfo) {
                $databaseNames[] = $dbInfo->name;
            }
        }

        // If database exists set database name else throw error
        if ($dbName !== $requestDbName && in_array($requestDbName, $databaseNames)) {
            Config::set('database.connections.mongodb.database', $requestDbName);
        } elseif ($dbName !== $requestDbName && !in_array($requestDbName, $databaseNames)) {
            return response()->json(['errors' => ['Permission denied. Application not found.']], 401);
        }

        return $next($request);
    }
}
