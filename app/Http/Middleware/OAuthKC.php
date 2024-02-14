<?php

namespace App\Http\Middleware;

use App\Enums\RoleType;
use Auth;
use Closure;
use Concerns\InteractsWithInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OAuthDB
{

    public function handle($request, Closure $next)
    {
        try {
            $url = config('app.oauth.auth_url').'/api/auth-user';
            $token = 'Bearer '.$request->bearerToken();
            $options = [ 'verify' => false];
            if (config('app.oauth.auth_server') == 'local') {
                $options['curl'] = [
                    CURLOPT_RESOLVE => [config('app.oauth.auth_url') . ':443:127.0.0.1']
                ];
            }

            $response = Http::retry(3, 1000)
                ->withOptions($options)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => $token,
                ])
                ->get($url);

            if ($response->successful()) {
                $currentUser = $response->json();
                $request->merge(['current_user' => $currentUser]);
                Auth::loginUsingId($currentUser["id"]);
                return $next($request);
            }

        } catch (\Exception $e) {
            report($e);
        }
        throw new HttpException(401, 'unauthorized');
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
//    public function handle_old($request, Closure $next)
//    {
//        try {
//            $headers = apache_request_headers();
//            $token = '';
//            if (isset($headers['Authorization'])) {
//                $token = $headers['Authorization'];
//            }
//            $client = new \GuzzleHttp\Client();
//            $url = config('app.oauth.auth_url').'/api/validate-token';
//            $response = $client->request('GET', $url, [
//                'debug'       => false,
//                'verify'      => false,
//                'http_errors' => false,
//                'headers' => [
//                    'Content-Type' => 'application/json',
//                    'Accept' => 'application/json',
//                    'Authorization' => $token,
//                ],
//            ]);
//
//            // info([$response->getBody()]);
//            if ($response->getStatusCode() == 200) {
//                $userId = $response->getBody();
//                Auth::loginUsingId($userId);
//
//                return $next($request);
//            }
//        } catch (\Exception $e) {
//            report($e);
//        }
//        throw new HttpException(401, 'unauthorized');
//    }
}
