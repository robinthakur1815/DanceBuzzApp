<?php

namespace App\Channels;

use App\DeviceToken;
use App\Enums\AppType;
use App\Enums\TokenStatus;
use App\Helpers\NotificationHelper;
use App\Model\NotificationModel;
use Illuminate\Notifications\Notification;

class PushNotificationChannel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        try {
            $message = $notification->toPushNotification($notifiable);
            $collection_id = '';
            $collectionType = '';
            $classId = '';

            if (isset($message['collection_type'])) {
                $collectionType = $message['collection_type'];
            }
            if (isset($message['collection_id'])) {
                $collection_id = $message['collection_id'];
            }

            if (isset($message['class_id'])) {
                $classId = $message['class_id'];
            }

            $notificationSendData = [
                'id'               => $notification->id,
                'created_at'       => now()->format('d/m/Y'),
                'is_read'          => false,
                'is_custom'        => false,
                'description'      => $message['description'],
                'click_action'     => 'FLUTTER_NOTIFICATION_CLICK',
                'title'            => $message['title'],
                'action'           => $message['type'],
                'action_id'        => $message['action_id'],
                'action_slug'      => $message['action_slug'],
                'collection_id'    => $collection_id,
                'collection_type'  => $collectionType,
                'class_id'         => $classId,
            ];

            $newNotification = NotificationModel::where('id', $notification->id)->first();

            $is_partner = $message['is_partner'];
            $app_type = $is_partner ? AppType::Partner : AppType::Client;
            if ($newNotification) {
                $newNotification->update(['app_type' => $app_type]);
            }

            $tokens = DeviceToken::where('user_id', $notifiable->id)
                        ->where('status', TokenStatus::Active)
                        ->where('app_type', $app_type)
                        ->pluck('device_token');
            NotificationHelper::send($tokens, $notificationSendData, $app_type);

            return response(['status' => true]);
        } catch (\Exception $e) {
            report($e);

            return response(['message' =>  'server error', 'status' => false], 500);
        }
    }
}
