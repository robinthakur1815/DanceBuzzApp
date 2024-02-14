<?php

namespace App\Http\Controllers\Api;

use App\AppVersion;
use App\Enums\AppUpgradeType;
use App\Http\Controllers\Controller;
use App\Model\UserAppUpdateAction;
use Illuminate\Http\Request;
use Validator;

class ApiAppVersionController extends Controller
{
    public function appVersion(Request $request)
    {
        $userAction = UserAppUpdateAction::where('device_id', $request->device_id)
                                        ->where('app_type', $request->app_type)
                                        ->where('device_type', $request->device_type)
                                        ->latest()->first();

        $app = AppVersion::where('app_type', $request->app_type)->where('device_type', $request->device_type)->latest()->first();
        $app_version = $request->version;
        if (!$app) {
            return response(['status' => false, 'version' => "0", 'upgrade_type' => AppUpgradeType::Skip]);
        }

        if ($userAction) {
            if (version_compare($userAction->version,$app->version) >=0) {
                return response(['status' => false, 'version' => "0", 'upgrade_type' => AppUpgradeType::Skip]);
            }
        }
        // $app_version is the current app version user using 
        if (version_compare($app_version,$app->version)<0) {
            return response(['status' => true, 'version' => $app->version, 'upgrade_type' => $app->upgrade_type]);
        }

        return response(['status' => false, 'version' => "0", 'upgrade_type' => AppUpgradeType::Skip]);
    }

    public function appUpdateAction(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'version'       =>  'required',
                'device_type'   =>  'required|int',
                'device_id'     =>  'required',
                'app_type'      =>  'required|int',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
            }

            $user = auth()->user();

            $userAppUpdateAction = UserAppUpdateAction::where('app_type', $request->app_type)
                                                        ->where('device_id', $request->device_id)
                                                        ->where('device_type', $request->device_type)
                                                        ->first();
            $data = [
                'version'       => $request->version,
                'app_type'      => $request->app_type,
                'device_id'     => $request->device_id,
                'device_type'   => $request->device_type,
                'user_id'       => $user ? $user->id : null,
            ];

            if (! $userAppUpdateAction) {
                UserAppUpdateAction::create($data);
            }

            return response()->json(['message' => 'Successfully submitted'], 200);
        } catch (\Exception $e) {
            report($e);

            return response(['message' =>  'server error', 'status' => false], 500);
        }
    }
}
