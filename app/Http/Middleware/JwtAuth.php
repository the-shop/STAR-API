<?php

namespace App\Http\Middleware;

use App\GenericModel;
use Tymon\JWTAuth\Middleware\BaseMiddleware;

class JwtAuth extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        if (!$token = $this->auth->setRequest($request)->getToken()) {
            return $this->respond('tymon.jwt.absent', 'token_not_provided', 400);
        }

        $user = $this->auth->authenticate($token);

        if ($user === false) {
            return $this->respond('tymon.jwt.absent', 'User does not exist in database.', 404);
        };

        $this->events->fire('tymon.jwt.valid', $user);

        return $next($request);
    }
}
