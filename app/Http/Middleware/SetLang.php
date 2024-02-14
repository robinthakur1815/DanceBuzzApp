<?php

namespace App\Http\Middleware;

use Auth;
use Closure;
use Concerns\InteractsWithInput;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SetLang
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
        try {
            $headers = apache_request_headers();
            $lang = 'en';
            if (isset($headers['Language'])) {
                $lang = $headers['Language'];
            }
            \App::setLocale($lang);
        } catch (\Exception $e) {
            report($e);
        }

        return $next($request);
    }
}
