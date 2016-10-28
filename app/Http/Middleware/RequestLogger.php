<?php

namespace App\Http\Middleware;

use Closure;
use App\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use DateTime;

class RequestLogger
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
        // Check authorization
        try {
            $profile = JWTAuth::parseToken()->authenticate();
            $name = $profile->name;
            $id = $profile->id;
        } catch (\Exception $e) {
            $name = '';
            $id = '';
        }

        $date = new DateTime();

        //data to be written
        $logData = [
            'name' => $name,
            'id' => $id,
            'date' => $date->format('d-m-Y H:i:s'),
            'ip' => $request->ip(),
            'uri' => $request->path(),
            'method' => $request->method()
        ];

        //Create new Log model
        $log = new Log($logData);

        //Save log
        $log->save();

        return $next($request);
    }
}
