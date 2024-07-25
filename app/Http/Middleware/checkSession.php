<?php

namespace App\Http\Middleware;

use Closure;
use Session;


class checkSession
{
    public function handle($request, Closure $next)
    {
        if (!$request->session()->exists('LOGIN')) {
            // user value cannot be found in session
            return redirect('/');
        }

        return $next($request);
    }
}
