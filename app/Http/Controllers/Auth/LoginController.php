<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AppType;
use App\Enums\RoleType;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        //return $request->only($this->username(), 'password');
        return ['email' => $request->{$this->username()}, 'password' => $request->password, 'is_active' => 1];
    }

    /**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateLogin(Request $request)
    {
        $this->validate($request, [
            $this->username() => 'required|exists:users,'.$this->username().',is_active,1',
            'password' => 'required',
        ], [
            $this->username().'.exists' => 'User is invalid or the account has been disabled.',
        ]);
    }

    public function oAuthLogin(Request $request)
    {
        try {
            $data = [
                'grant_type'    => 'password',
                'client_id'     => config('app.oauth.client_id'),
                'client_secret' => config('app.oauth.client_secret'),
                'username'      => $request->username,
                'password'      => $request->password,
                'site_type'     => $request->site_type,
            ];

            //return ($data);die();
            $client = new \GuzzleHttp\Client();
            $url = config('app.oauth.auth_url').'/oauth/token';
            //return $url;die();
            $response = $client->request('POST', $url, [
                'debug'       => false,
                'verify'      => false,
                'http_errors' => false,
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'form_params' => $data,
            ]);

            // if ($response->getStatusCode() == 200) {
            //     $data = json_decode($response->getBody());
            //     $isSuperAdmin = $this->isSuperAdmin($data);
            //     if (!$isSuperAdmin) {
            //         return response([
            //             'error'             =>  'invalid_credentials',
            //             'error_description' => 'Only super admin are allowed',
            //             'message'           => 'Only super admin are allowed',
            //         ], 422);
            //     }
            //     return collect($data);
            // }


            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody());
                return collect($data);
            }
            
        } catch (\Exception $e) {
            report($e);
        }

        return response([
            'error'             =>  'invalid_credentials',
            'error_description' => 'The user credentials were incorrect.',
            'message'           => 'The user credentials were incorrect.',
        ], 422);
    }

    private function isSuperAdmin($data)
    {
        try {
            info("Data", array_wrap($data));
            $url = config('app.oauth.auth_url').'/api/auth-user';
            $token = 'Bearer '.$data->access_token;
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
                 return ($currentUser["role_id"] == RoleType::SuperAdmin);
            }

        } catch (\Exception $e) {
            report($e);
        }
        return false;
    }
}
