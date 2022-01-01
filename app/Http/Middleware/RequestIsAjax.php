<?php

namespace App\Http\Middleware;

class RequestIsAjax extends Middleware
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
        if (!$request->ajax()) {
            return route('login');
        }

        return $next($request);
    }
}

