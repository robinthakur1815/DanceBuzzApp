<?php

namespace App\Http\Controllers\Api;

use App\Collection;
use App\Enums\AppType;
use App\Enums\NotificationType;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserNotificationCollection;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\NotificationResourceCollection;
use App\Jobs\SendCustomNotification;
use App\Model\NotificationModel;
use App\Model\Partner\StudentRegistration;
use App\Model\Student;
use App\Model\UserNotification;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator as FacadesValidator;

class ApiSendPushNotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = UserNotification::with('user', 'createdBy')->latest();

        if ($request->search) {
            $notifications = $notifications->where('title', 'like', "%{$request->search}%");
        }

        if ($request->max_rows) {
            $notifications = $notifications->paginate($request->max_rows);
        } else {
            $notifications = $notifications->get();
        }

        return new UserNotificationCollection($notifications);
        // return paginate($request->max_rows ?? 10);
    }

    public function send(Request $request)
    {
        try {
            $validator = FacadesValidator::make($request->all(), [
                'title'         =>  'required',
                'description'   =>  'required',
                'app_type'      =>  'required|int',
                'email'         =>  'required_if:app_type,==, 3',
            ]);

            if ($validator->fails()) {
                return response(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
            }

            $data = [
                'title'       => $request->title,
                'description' => $request->description,
                'type'        => NotificationType::Custom,
                'created_by'  => auth()->id(),
            ];

            $notificationData = [
                'type'       => NotificationType::Custom,
                'created_by' => auth()->id(),
                'data'       => json_encode([
                    'action'      => NotificationType::Custom,
                    'action_id'   => null,
                    'action_slug' => null,
                    'title'       => $request->title,
                    'description' => $request->description,
                ]),
            ];

            $appType = null;

            if ($request->email and $request->app_type == 3) {
                $user = User::where('email', $request->email)->where('app_type', AppType::Partner)->first();
                if ($user) {
                    $data['user_id'] = $user->id;
                    $notificationData['notifiable_type'] = config('app.user_model');
                    $notificationData['notifiable_id'] = $user->id;
                }
            }

            if ($request->app_type == AppType::Partner || $request->app_type == AppType::Client) {
                $appType = $request->app_type;
            } else {
                $appType = AppType::Client;
            }

            $data['app_type'] = $appType;
            $notificationData['app_type'] = $appType;

            $userNotification = UserNotification::create($data);
            $notificationModel = NotificationModel::create($notificationData);

            SendCustomNotification::dispatchNow($userNotification, $notificationModel, $appType);

            return response(['status' => true, 'message' => 'success', 'user' => $userNotification], 201);
        } catch (\Exception $e) {
            report($e);

            return response(['message' =>  'server error', 'status' => false], 500);
        }
    }

    public function vendorSend(Request $request)
    {
        try {
            $validator = FacadesValidator::make($request->all(), [
                'title'         =>  'required',
                'description'   =>  'required',
                'class_id'      =>  'nullable|int',
                'emails'        =>  'nullable|array',
            ]);

            if ($validator->fails()) {
                return response(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
            }

            if (! $request->class_id and ! $request->emails) {
                return response(['errors' => ['email' => ['select class or email address']], 'status' => false, 'message' => ''], 422);
            }

            if (! $request->class_id) {
                if (! is_array($request->emails)) {
                    return response(['errors' => ['email' => ['email not found']], 'status' => false, 'message' => ''], 422);
                }
            }

            $data = [
                'title'       => $request->title,
                'description' => $request->description,
                'type'        => NotificationType::Custom,
                'created_by'  => auth()->id(),
            ];

            $notificationData = [
                'type'       => NotificationType::Custom,
                'created_by' => auth()->id(),
                'data'       => json_encode([
                    'action'      => NotificationType::Custom,
                    'action_id'   => null,
                    'action_slug' => null,
                    'title'       => $request->title,
                    'description' => $request->description,
                    'class_id'    => $request->class_id
                ]),
            ];

            $appType = AppType::Client;
            $users = [];
            if ($request->class_id) {
                $classId = $request->class_id;
                $studentsIds = StudentRegistration::where('vendorclass_id', $classId)->pluck('student_id');
                if (! count($studentsIds)) {
                    return response(['errors' => ['student' => [__('message.no_student_found')]], 'status' => false, 'message' => ''], 422);
                }

                $usersIds = Student::whereIn('id', $studentsIds)->pluck('user_id');
                $users = User::where('app_type', AppType::Partner)
                                ->whereIn('id', $usersIds)
                                ->get();
            } else {
                $users = User::where('app_type', AppType::Partner)
                ->whereIn('email', $request->emails)
                ->get();
            }
            $userNotification = null;
            if (count($users)) {
                foreach ($users as $user) {
                    $data['user_id'] = $user->id;
                    $notificationData['notifiable_type'] = config('app.user_model');
                    $notificationData['notifiable_id'] = $user->id;

                    $data['app_type'] = $appType;
                    $notificationData['app_type'] = $appType;

                    $userNotification = UserNotification::create($data);
                    $notificationModel = NotificationModel::create($notificationData);

                    SendCustomNotification::dispatchNow($userNotification, $notificationModel, $appType);
                }
            }

            return response(['status' => true, 'message' => 'success', 'user' => $userNotification], 201);
        } catch (\Exception $e) {
            report($e);

            return response(['message' =>  'server error', 'status' => false], 500);
        }
    }

    public function partnerNotification($collectionId)
    {
       $collection = Collection::where('id', $collectionId)->first();
    //    info([$collection]);
       if ($collection) {
            NotificationHelper::collection($collection, false);
        }
    }

    public function getNotification(Request $request)
    {
    
             $notifications = NotificationModel::where([
                                                    'notifiable_id' => $request->collection_id,
                                                    'type'          => $request->collection_type
                                                ])->get();

            return new NotificationResourceCollection($notifications);

    
    }
}
