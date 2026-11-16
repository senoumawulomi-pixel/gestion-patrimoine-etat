<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SessionAuth
{
    public function handle(Request $request, Closure $next)
    {
        return session('token') ? $next($request) : redirect()->route('login');
    }
}
