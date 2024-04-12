<?php

namespace App\Http\Middleware;
use App\Models\{Store,Currency,ProductExtraOption};
use Closure;
use Session;
class SetLocale
{
    public function handle($request, Closure $next)
    {
        return $next($request);
    }
}
