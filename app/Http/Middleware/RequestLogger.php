<?php

namespace App\Http\Middleware;

use Closure;
use App\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Class RequestLogger
 * @package App\Http\Middleware
 */
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
        try {
            $profile = JWTAuth::parseToken()->authenticate();
            $name = $profile->name;
            $id = $profile->id;
        } catch (\Exception $e) {
            $name = '';
            $id = '';
        }

        $date = new \DateTime();

        $logData = [
            'name' => $name,
            'id' => $id,
            'date' => $date->format('d-m-Y H:i:s'),
            'ip' => $request->ip(),
            'uri' => $request->path(),
            'method' => $request->method()
        ];

        $log = new Log($logData);

        $log->save();

        return $next($request);
    }
}
