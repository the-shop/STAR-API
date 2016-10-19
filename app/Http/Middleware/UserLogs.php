<?php

namespace App\Http\Middleware;

use Closure;
use App\Http\Requests\Request;

class UserInformation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        /* $profile = Profile::find($name, $id);
        if(!$profile){
            return $this->jsonError('User not found');
        } */

        $file = "UserInformation.txt";
        //$request->ip();


        $contents = array('asdadads', 'fgdfg', $request->ip());

        file_put_contents($file, $contents, FILE_APPEND );

        return $next($request);
    }
}
