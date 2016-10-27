<?php

namespace App\Http\Middleware;

use Closure;
use App\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use DateTime;

class UserLogs
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
        //authenticate user
        $credentials = $request->only('email', 'password');

        //if user authenticated get name and id, if not name and id are null
        if ($token = JWTAuth::attempt($credentials)) {
            $name = Auth::user()->name;
            $id = Auth::user()->id;
        } else {
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
