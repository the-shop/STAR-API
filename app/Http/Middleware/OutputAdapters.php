<?php

namespace App\Http\Middleware;

use App\GenericModel;
use Closure;

class OutputAdapters
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
        $response = $next($request);
        $models = $response->getOriginalContent();

        $preSetCollection = GenericModel::getCollection();
        GenericModel::setCollection('adapter-rules');
        $adapterRules = GenericModel::all();

        $adapterResponse = [];

        foreach ($adapterRules as $adapterRule) {
            if ($models instanceof GenericModel && $adapterRule->resource ===
                $models['collection']
            ) {
                $adapterModel = new $adapterRule->resolver['class']($models);
                $adapterResponse[] = call_user_func([$adapterModel, $adapterRule->resolver['method']]);
            } else {
                foreach ($models as $model) {
                    if ($adapterRule->resource === $model['collection']) {
                        $adapterModel = new $adapterRule->resolver['class']($model);
                        $adapterResponse[] = call_user_func([$adapterModel, $adapterRule->resolver['method']]);
                    }
                }
            }
        }


        GenericModel::setCollection($preSetCollection);

        $headers = [];
        $token = \JWTAuth::getToken();
        if ($token) {
            $headers = array_merge(
                $headers,
                [
                    'Authorization' => 'bearer ' . $token
                ]
            );
        }

        if (!empty($adapterResponse) && count($adapterResponse) > 1) {
            return response()->json($adapterResponse, 200, $headers);
        } elseif (!empty($adapterResponse) === 1) {
            return response()->json($adapterResponse[0], 200, $headers);
        }

        return response()->json($models, 200, $headers);
    }
}
