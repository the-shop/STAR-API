<?php

namespace App\Http\Middleware;

use App\GenericModel;
use Closure;

class GenericResource
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
        // Get the resource name from route parameter
        $resourceName = $request->route('resource');

        // Set the collection name to be the resource name
        GenericModel::setCollection($resourceName);

        return $next($request);
    }
}
