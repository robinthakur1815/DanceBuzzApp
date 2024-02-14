<?php

namespace App\Http\Controllers;

use App\Http\Resources\User as UserResources;
use App\Jobs\ResetPasswordMail;
use App\PasswordReset;
use App\Role;
use App\User;
use Auth;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Log;
use Validator;

class AuthController extends Controller
{
    public function logoutUser(Request $request)
    {   
        $user = auth()->user();
    
        /* $user->AauthAcessToken()->update(['revoked'=> true]); */

        return response(['status' => true, 'message' => 'Logout successfully'], 200);
    }

    public function currentUser()
    {
        $user = Auth::user();
        $user->load('avatarMediable');
        $url = null;
        if (! $user->avatarMediable || ! $user->avatarMediable->media) {
            $url = null;
        } else {
            $url = Storage::disk('s3')->url($user->avatarMediable->media->url);
        }
        unset($user->avatarMediable);
        $user['profile_url'] = $url;
        $user['authCollections'] = $this->getAllowedCollections($user->role_id);

        $role = Role::find($user->role_id);
        if ($role) {
            $user['role_name'] = $role->name;
        }

        return $user;
    }

    public function changePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'old_password' => 'required|string|min:6',
                'new_password' => 'required|string|min:6|confirmed',
            ]);

            if ($validator->fails()) {
                return response(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
            }

            $user = auth()->user();

            if (! Hash::check($request->old_password, $user->password)) {
                return response(['errors' => ['password' => ['old password does not match to password']], 'status' => false, 'message' => ''], 422);
            }

            if (Hash::check($request->new_password, $user->password)) {
                return response(['errors' => ['password' => ['your password can not be same as your current password, try different password']], 'status' => false, 'message' => ''], 422);
            }

            $user->update(['password' => bcrypt($request->new_password)]);

            $user['userOnly'] = true;

            return new UserResources($user);
        } catch (\Exception $e) {
            report($e);

            return response(['message' =>  'server error', 'status' => false], 500);
        }
    }

    public function sendResetPasswordMail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query'        => 'required|string',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
        }
        $user = User::where('email', request('query'))->first();
        if (! $user) {
            return response(['errors' => ['user' => ['Requested email is not registered with us.']], 'status' => false, 'message' => ''], 422);
        }
        // $currentToken = PasswordReset::whereDate('created_at', Carbon::today())->whereDate('expire_at', '>', Carbon::today())->first();
        $currentToken = PasswordReset::whereEmail(request('query'))->whereDate('expire_at', '>', now())->latest()->first();
        // if ($currentToken) {
        //     $user->notify((new ResetPasswordNotification($currentToken->token)));

        //     return response(['status' => true, 'message' => "Mail has been resend"], 201);
        // }
        if ($currentToken) {
            // info('dsadsagd');
            // $currentToken->update(['expire_at' => now()->addDays(1)]);
            // $currentToken->save();
            $token = $currentToken->token;
            $email = $user->email;

            ResetPasswordMail::dispatch($token, $email, $user);

            return response(['status' => true, 'message' => 'Mail has been resend'], 201);
        }

        $token = Str::random(64);
        $data = [
            'email'       => $user->email,
            'token'       => $token,
            'expire_at'   => now()->addDays(1),
            'created_at'  => now(),
        ];

        PasswordReset::create($data);

        $email = $user->email;
        // $user->notify((new ResetPasswordNotification($token)));
        // info($token,$email,$user);
        ResetPasswordMail::dispatch($token, $email, $user);

        return response(['status' => true, 'message' => 'Mail has been send'], 201);
    }

    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'token'        => 'required|string|min:64|max:64',
                'password'     => 'required|string|min:6|confirmed',
            ]);
            if ($validator->fails()) {
                return response(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
            }

            $token = PasswordReset::where('token', $request->token)->first();

            if (! $token) {
                return response(['errors' => ['token' => ['Your password reset link has expired']], 'status' => false, 'message' => ''], 422);
            }
            if (now() > $token->expire_at) {
                return response(['errors' => ['token' => ['token is expired']], 'status' => false, 'message' => ''], 422);
            }
            $user = User::where('email', $token->email)->first();
            $user->update(['password' => bcrypt($request->password)]);
            PasswordReset::where('token', $request->token)->delete();

            return response(['status' => true, 'message' => 'Password successfully changed'], 200);
        } catch (\Exception $e) {
            report($e);

            return response(['message' =>  'server error', 'status' => false], 500);
        }
    }

    public function validateToken(Request $request)
    {
        $token = PasswordReset::where('token', $request->token)->first();
        if (! $token) {
            return response(['errors' => ['token' => ['Token is not valid. Please request a new one']], 'status' => false, 'message' => ''], 422);
        }
        if (now() > $token->expire_at) {
            return response(['errors' => ['token' => ['Token is expired. Please request a new one']], 'status' => false, 'message' => ''], 422);
        }

        return response(['status' => true, 'message' => 'varified Token'], 200);
    }

    private function getAllowedCollections($id)
    {
        $authCollections = [];
        $role = Role::find($id);
        // info($role);
        // info(config('roles.products'));
        if ($role) {
            $roleSlug = 'roles.'.$role->slug;
            // info($roleSlug);
            $authCollections = config($roleSlug);
            // info($authCollections);
            return $authCollections;
        }

        return $authCollections;
    }

    public function loginBySuperAdmin(Request $request)
    {
        $user = User::find($request->id);
       //$user = $request;
        $user->load('avatarMediable');
        $url = null;
        if (! $user->avatarMediable || ! $user->avatarMediable->media) {
            $url = null;
        } else {
            $url = Storage::disk('s3')->url($user->avatarMediable->media->url);
        }
        unset($user->avatarMediable);
        $user['profile_url'] = $url;
        $user['authCollections'] = $this->getAllowedCollections($user->role_id);

        $role = Role::find($user->role_id);
        if ($role) {
            $user['role_name'] = $role->name;
        }

        return $user;
    }
}
