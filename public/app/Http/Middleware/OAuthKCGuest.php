<?php

namespace App\Http\Middleware;

use Auth;
use Closure;
use Concerns\InteractsWithInput;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OAuthDBGuest
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
            $token = '';
            if (isset($headers['Authorization'])) {
                $token = $headers['Authorization'];
            }
            if (! $token) {
                return $next($request);
            }
            $client = new \GuzzleHttp\Client();
            $url = config('app.oauth.auth_url').'/api/validate-token';
            $response = $client->request('GET', $url, [
                'debug'       => false,
                'verify'      => false,
                'http_errors' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => $token,
                ],
            ]);

            if ($response->getStatusCode() == 200) {
                $userId = $response->getBody();
                Auth::loginUsingId($userId);

                return $next($request);
            }
        } catch (\Exception $e) {
            report($e);
        }

        return $next($request);
    }
}
