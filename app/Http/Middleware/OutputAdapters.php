<?php

namespace App\Http\Middleware;

use App\GenericModel;
use Closure;

class OutputAdapters
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
        $response = $next($request);

        foreach ($response->getOriginalContent() as $model) {
                $resource = GenericModel::getCollection();
            if ($resource === 'tasks') {
                GenericModel::setCollection('adapter-rules');
                $adapter = GenericModel::where('resource', '=', $resource)->first();
                $response = call_user_func($adapter->resolver['class'], $adapter->resolver['method']);
            }
        }

        return $response;
    }
}
