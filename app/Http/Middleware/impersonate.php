<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
class impersonate
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
        if(session()->has('impersonate')){
            Auth::onceUsingId(session('impersonate'));
        }
        return $next($request);
    }
}
