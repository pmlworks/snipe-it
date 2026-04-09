<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LogAuthedUserHeader
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        $response = $next($request);

        if ((config('app.authorized_user_header') === true) && ($request->bearerToken() != '')) {
            $response->headers->set('X-Authorized-User-ID', auth()?->id());
        } 

        return $response;
    }
}
