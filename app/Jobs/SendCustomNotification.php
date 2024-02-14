<?php

namespace App\Jobs;

use App\DeviceToken;
use App\Enums\AppType;
use App\Enums\TokenStatus;
use App\Enums\VendorRoleType;
use App\Helpers\NotificationHelper;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class SendCustomNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $notificationData;

    private $userNotificationData;

    private $app_type;

    private $userIds;

    public function __construct($notificationData, $userNotificationData, $app_type = null, $userIds = [])
    {
        $this->onQueue('pn');
        $this->notificationData = $notificationData;
        $this->userNotificationData = $userNotificationData;
        $this->app_type = $app_type;
        $this->userIds = $userIds;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $app_type = $this->app_type;
        $notificationData = $this->notificationData;
        $userNotificationData = $this->userNotificationData;
        $action_id = '';
        $action_slug = '';
        $userIds = $this->userIds;
        $class_id = '';
        $collection_id = '';
        $collection_type = '';
        if ($userNotificationData->data) {
            $data = $userNotificationData->data;
            $action_id = $data['action_id'] ?? '';
            $action_slug = $data['action_slug'] ?? '';
            if (isset($data['class_id']) and $data['class_id']) {
                $class_id = (string) $data['class_id'];
            }

            if (isset($data['collection_id']) and $data['collection_id']) {
                $collection_id = (string) $data['collection_id'];
            }
            if (isset($data['collection_type']) and $data['collection_type']) {
                $collection_type = (string) $data['collection_type'];
            }
        }

        $data = [
            'id'              => $userNotificationData->id,
            'created_at'      => now()->format('d/m/Y'),
            'is_read'         => false,
            'is_custom'       => true,
            'description'     => $notificationData->description,
            'click_action'    => 'FLUTTER_NOTIFICATION_CLICK',
            'title'           => $notificationData->title,
            'class_id'        => $class_id,
            'action'          => $notificationData->type,
            'action_id'       => $action_id,
            'action_slug'     => $action_slug,
            'collection_id'   => $collection_id,
            'collection_type' => $collection_type,
        ];

        if ($notificationData->user_id) {
            $user = User::find($notificationData->user_id);
            if ($user) {
                $tokens = DeviceToken::where('user_id', $notificationData->user_id)
                ->where('status', TokenStatus::Active)
                ->pluck('device_token');

                if ($user->role_id == VendorRoleType::Vendor || $user->role_id == VendorRoleType::VendorStaff) {
                    $app_type = AppType::Partner;
                }
                NotificationHelper::send($tokens, $data, $app_type);
            }
        } elseif ($userIds and is_array($userIds) and count($userIds)) {
            DeviceToken::where('status', TokenStatus::Active)->where('user_id', $userIds)->where('app_type', AppType::Client)->chunk(100, function ($deviceTokens) use ($data) {
                $tokens = $deviceTokens->pluck('device_token');
                NotificationHelper::send($tokens, $data, AppType::Client);
            });
        } else {
            if (! $app_type or $app_type == AppType::Client) {
                DeviceToken::where('status', TokenStatus::Active)->where('app_type', AppType::Client)->chunk(100, function ($deviceTokens) use ($data) {
                    $tokens = $deviceTokens->pluck('device_token');
                    NotificationHelper::send($tokens, $data, AppType::Client);
                });

                return;
            }

            if ($app_type == AppType::Partner) {
                DeviceToken::where('status', TokenStatus::Active)->where('app_type', AppType::Partner)->chunk(100, function ($deviceTokens) use ($data) {
                    $tokens = $deviceTokens->pluck('device_token');
                    NotificationHelper::send($tokens, $data, AppType::Partner);
                });
            }
        }
    }
}
