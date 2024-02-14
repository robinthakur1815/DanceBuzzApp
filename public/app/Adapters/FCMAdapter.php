<?php

namespace App\Adapters;

use App\DeviceToken;
use App\Enums\AppType;
use App\Model\NotificationGroup;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;

final class FCMAdapter extends Facade
{
   
    public static function createGroup($group_id, $group_type, $groupName,  $type,  $userId)
    {
        $createData = [
            'operation'             => 'create',
            'notification_key_name' => $groupName
        ];

        $requestResponse = self::sendRequest($createData, $type);
        if($requestResponse->status) {
            $data = [
                'group_id'        => $group_id,
                'group_type'      => $group_type,
                'notification_id' => $requestResponse->id,
                'group_name'      => $groupName,
                'type'            => $type,
                'created_by'      => $userId
            ];
            NotificationGroup::create($data);
        }
    }

    public static  function addIdsInGroup($group_id, $group_type,  $userId)
    {
        $tokens = DeviceToken::where('user_id', $userId)->pluck('device_token');
        $type = AppType::Client;
        $groupData = NotificationGroup::where('group_id', $group_id)->where('group_type', $group_type)->first();
        if($groupData and count($tokens)){
            $createData = [
                'operation'             => 'add',
                'notification_key_name' => $groupData->group_id,
                'notification_key'      => $groupData->notification_id,
                'registration_ids'      => $tokens
            ];
    
           self::sendRequest($createData, $type);
        }
        
    }

    public static  function removeIdsInGroup($group_id, $group_type, $userId)
    {
        $tokens = DeviceToken::where('user_id', $userId)->pluck('device_token');
        $type = AppType::Client;
        $groupData = NotificationGroup::where('group_id', $group_id)->where('group_type', $group_type)->first();
        if($groupData and count($tokens)){
            $createData = [
                'operation'             => 'remove',
                'notification_key_name' => $groupData->group_id,
                'notification_key'      => $groupData->notification_id,
                'registration_ids'      => $tokens
            ];
    
           self::sendRequest($createData, $type);
        }
    }

    public static  function sendGroupNotification($group_id, $group_type, $data)
    {
        $groupData = NotificationGroup::where('group_id', $group_id)->where('group_type', $group_type)->first();
        if($groupData){
            
            $type = $groupData->type;
            $baseUrl = 'https://fcm.googleapis.com/fcm/send';
            $notification = [
                'body'  => $data['description'],
                'title' => $data['title'],
                'sound' => true,
            ];
            $extraNotificationData = $data;
            $payLoad = [
                'notification'     => $notification,
                'data'             => $extraNotificationData,
                'to'               => $groupData->notification_id,
            ];

            $firebase_key = $type == AppType::Partner ? config('services.firebase.partner_key') : config('services.firebase.key');

            $headers = [
                'Authorization: key='.$firebase_key,
                'Content-Type: application/json',
            ];
            $response = Http::withHeaders($headers)->post($baseUrl, $payLoad);
            $status = $response->ok();
            if($status){
                
            }
        }
    }

    private static function sendRequest($payLoad, $type)
    {
        $baseUrl = "https://fcm.googleapis.com/fcm/notification";

        $key = config('services.firebase.key');
        
        if($type == AppType::Partner) {
            $key = config('services.firebase.partner_key');
        }
       
        $headers = [
            'Authorization: key='.$key,
            'Content-Type: application/json',
        ];

        $response = Http::withHeaders($headers)->post($baseUrl, $payLoad);
        
        $responseData = $response->body();
        $data = new \stdClass;

        $data->id = null;
        $data->status = $response->ok();

        if($response->ok() and isset($responseData['notification_key'])) {
            $data->id = $responseData['notification_key'];
        }
        
        return $data;
    }
}
