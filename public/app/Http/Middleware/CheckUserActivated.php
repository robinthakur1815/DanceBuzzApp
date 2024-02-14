<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckUserActivated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() and !$request->user()->is_active) {
            // if (!$request->expectsJson()) {
            //     Auth::logout();
            //     return redirect('/login')->with('status', "your account is deactivated");
            // }
            return response(['errors' => ['methods' => ['your account is deactivated']], 'status' => false, 'message' => ''], 401);
        }

        return $next($request);
    }
}
