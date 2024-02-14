<?php

namespace  App\Helpers;

use App\Enums\AppType;
use App\Enums\CollectionType;
use App\Enums\NotificationType;
use App\Jobs\SendCustomNotification;
use App\Model\NotificationModel;
use App\Model\UserNotification;
use App\Feed;
use App\Student;
use App\CustomFeed;
use Carbon\Carbon;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

class NotificationHelper extends Facade
{
    public static function send($tokens, $data, $type = null)
    {
        try {
            if (! count($tokens)) {
                return ['status' => false];
            }
            $url = 'https://fcm.googleapis.com/fcm/send';
            $notification = [
                'body'  => $data['description'],
                'title' => $data['title'],
                'sound' => true,
            ];
            $extraNotificationData = $data;
            $fcmNotification = [
                'registration_ids' => $tokens,
                'notification'     => $notification,
                'data'             => $extraNotificationData,
            ];

            $firebase_key = $type == AppType::Partner ? config('services.firebase.partner_key') : config('services.firebase.key');

            $fields = json_encode($fcmNotification);
            $headers = [
                'Authorization: key='.$firebase_key,
                'Content-Type: application/json',
            ];

            $error_msg = null;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

            $result = curl_exec($ch);

            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
            }
            if ($error_msg) {
                info($error_msg);
            }
            curl_close($ch);

            return ['status' => true];
        } catch (\Exception $e) {
            report($e);

            return ['status' => false];
        }
    }

    public static function collection($collection, $isFeed = false, $users = [])
    {
        $data = null;
        if ($collection->collection_type == CollectionType::events) {
            $data = [
                'title'      => config('message.event.title'),
                'description' => config('message.event.description'),
                'type'       => NotificationType::EventCollection,
                'created_by' => auth()->id(),
            ];
        }

        if ($collection->collection_type == CollectionType::classes) {
            $data = [
                'title'      => config('message.class.title'),
                'description' => config('message.class.description'),
                'type'       => NotificationType::ClassCollection,
                'created_by' => auth()->id(),
            ];
        }

        if ($collection->collection_type == CollectionType::classDeck) {
            $data = [
                'title'      => config('message.liveClass.title'),
                'description' => config('message.liveClass.description'),
                'type'       => NotificationType::LivesClassCollection,
                'created_by' => auth()->id(),
            ];
        }

        if ($collection->collection_type == CollectionType::workshops) {
            $data = [
                'title'      => config('message.workshop.title'),
                'description' => config('message.workshop.description'),
                'type'       => NotificationType::WorkShopCollection,
                'created_by' => auth()->id(),
            ];
        }

        if ($collection->collection_type == CollectionType::campaigns) {
            $data = [
                'title'      => config('message.campaign.title'),
                'description'=> config('message.campaign.description'),
                'type'       => NotificationType::Campaign,
                'created_by' => auth()->id(),
            ];
        }
        $data['app_type'] = AppType::Client;

        if ($isFeed) {
            $title = Str::title($collection->title);
            $feeds = Feed::with('feedable.medias');
            $feed = $feeds->whereHasMorph(
                'feedable',
                [CustomFeed::class],
                function (Builder $query) use($collection){
                    $query->where('feedable_id', '=', $collection->id);
                })->first();


            $data = [
                'title'      => config('message.feed.title'),
                'description'=> sprintf(config('message.feed.description'), $title),
                'type'       => NotificationType::NewFeed,
                'created_by' => auth()->id(),
            ];
            $data['app_type'] = AppType::Client;
            if($feed != null and $feed->is_partner == true){
            $data['app_type'] = AppType::Partner;
            }

        }



        if ($data) {
            $userNotification = UserNotification::create($data);
            $notificationData = [
                'type'       => NotificationType::Custom,
                'app_type'   => $data['app_type'],
                'created_by' => auth()->id(),
                'data'       => json_encode([
                    'action'         => $userNotification->type,
                    'action_id'      => $collection->id,
                    'action_slug'    => $collection->slug,
                    'title'          => $userNotification->title,
                    'description'    => $userNotification->description,
                    'class_id'       => $collection->vendor_class_id,
                    'description'     => $userNotification->description,
                    'collection_id'   => $collection->id,
                    'collection_type' => $collection->collection_type,
                ]),
            ];
            $notificationModel = NotificationModel::create($notificationData);
            if($data['app_type'] == AppType::Client){
            SendCustomNotification::dispatchNow($userNotification, $notificationModel, AppType::Client);
            }
            SendCustomNotification::dispatchNow($userNotification, $notificationModel, AppType::Partner);
        }
    }

    public static function reminderNotification($collection, $users, $dateTimeData = null)
    {
        info('process started');
        $data = null;
        if ($dateTimeData) {
            $dateTime = $dateTimeData;
        } else {
            $dateTime = self::startDateTime($collection);
        }

        $title = Str::title($collection->title);
        if ($collection->collection_type == CollectionType::classes) {
            $data = [
                'title'       => config('message.class.reminder_title'),
                'description' => sprintf(config('message.class.reminder_description'), $title, $dateTime),
                'type'        => NotificationType::ClassCollection,
                'created_by'  => auth()->id(),
            ];
        }

        if ($collection->collection_type == CollectionType::classDeck) {
            $data = [
                'title'      => config('message.liveClass.reminder_title'),
                'description' => sprintf(config('message.liveClass.reminder_description'), $title, $dateTime),
                'type'       => NotificationType::LivesClassCollection,
                'created_by' => auth()->id(),
            ];
        }

        if ($collection->collection_type == CollectionType::events) {
            $data = [
                'title'       => config('message.event.reminder_title'),
                'description' => sprintf(config('message.event.reminder_description'), $title, $dateTime),
                'type'        => NotificationType::EventCollection,
                'created_by'  => auth()->id(),
            ];
        }

        if ($collection->collection_type == CollectionType::workshops) {
            $data = [
                'title'       => config('message.workshop.reminder_title'),
                'description' => sprintf(config('message.workshop.reminder_description'), $title, $dateTime),
                'type'        => NotificationType::WorkShopCollection,
                'created_by'  => auth()->id(),
            ];
        }

        $data['app_type'] = AppType::Client;

        if ($data) {
            $userNotification = UserNotification::create($data);
            $notificationData = [
                'type'       => NotificationType::Custom,
                'app_type'   => AppType::Client,
                'created_by' => auth()->id(),
                'data'       => json_encode([
                    'action'        => $userNotification->type,
                    'action_id'     => $collection->id,
                    'class_id'      => $collection->vendor_class_id,
                    'action_slug'   => $collection->slug,
                    'title'         => $userNotification->title,
                    'description'   => $userNotification->description,
                    'collection_id' => $collection->id,
                    'collection_type' => $collection->collection_type,
                ]),
            ];
            $notificationModel = NotificationModel::create($notificationData);
            SendCustomNotification::dispatchNow($userNotification, $notificationModel, AppType::Client, $users);
        }
    }
    public static function rejectionStoryNotification($story, $userId){
        
        $campaign = $story->campaign()->first();
        $campaign = $campaign->title;
        $studentId = $story->student_user_id;
        $student = Student::where('user_id', $studentId)->first();
        $student = $student->name;
        $reason  = $story->reason;
        $data = [
            'title'      => config('message.story.title'),
            'description'=> sprintf(config('message.story.description'), $campaign, $student, $reason),
            'type'       => NotificationType::StoryRejection,
            'created_by' => auth()->id(),
        ];
        $data['app_type'] = AppType::Client;
        if($data){
            $userNotification = UserNotification::create($data);
            $notificationData = [
                'type'       =>    NotificationType::StoryRejection,
                'app_type'   => AppType::Client,
                'created_by' => auth()->id(),
                'data'       => json_encode([
                
                    'action'         => $userNotification->type,
                    'action_id'      => $story->id,
                    'action_slug'    => "",
                    'title'          => $userNotification->title,
                    'class_id'       => "",
                    'description'     => $userNotification->description,
                    'collection_id'   => $story->id,
                    'collection_type' => $story->status,

                           
                ]),
            ];
            $notificationModel = NotificationModel::create($notificationData);
            SendCustomNotification::dispatchNow($userNotification, $notificationModel, AppType::Client, $userId);
        
                                                                                                                 

        }
    }

    public static function startDateTime($collection)
    {
        $published_content = json_decode($collection->saved_content);
        $start_date = (isset($published_content->start_date) && $published_content->start_date) ? $published_content->start_date : null;
        $start_time = (isset($published_content->start_time) && $published_content->start_time) ? $published_content->start_time : null;

        $start_date = self::dateTimeFormat($start_date, 'Y/m/d');
        $start_time = self::dateTimeFormat($start_time, 'h:i A');

        $dateTime = '';
        if ($start_date) {
            $dateTime = $start_date->format('M d, Y');
        }

        if ($start_time) {
            $dateTime = $dateTime.' '.$start_time->format('h:i A');
        }

        return $dateTime;
    }

    public static function dateTimeFormat($date, $format)
    {
        try {
            return Carbon::createFromFormat($format, $date);
        } catch (\Throwable $th) {
            try {
                return Carbon::parse($date);
            } catch (\Throwable $th) {
            }
        }

        return '';
    }
}
