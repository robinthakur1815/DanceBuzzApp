<?php

namespace App\Notifications;

use App\Channels\PushNotificationChannel;
use App\Enums\CollectionType;
use App\Enums\EmailType;
use App\Enums\NotificationType;
use App\Enums\RoleType;
use App\Helpers\CollectionHelper;
// use App\Enums\UserRole;
use App\Model\EmailSend;
use App\Model\Partner\CollectionOrder;
use App\Model\Partner\VendorClass;
use App\Model\PartnerCollection;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Queue\SerializesModels;

class NewBookingDone extends Notification
{
    // use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */

    private $payment, $collection, $is_partner, $isMail;
    public function __construct(CollectionOrder $payment, PartnerCollection $collection, $is_partner, $isMail = true)
    {
        // $this->onQueue('pn');
        $this->payment      = $payment;
        $this->collection   = $collection;
        $this->is_partner   = $is_partner;
        $this->isMail       = $isMail;

    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        if ($this->isMail) {
            return ['database', 'mail', PushNotificationChannel::class];
        }else{
            return ['database', PushNotificationChannel::class];
        }
        // return ['database', PushNotificationChannel::class];

    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        // return (new MailMessage)
        //             ->line('The introduction to the notification.')
        //             ->action('Notification Action', url('/'))
        //             ->line('Thank you for using our application!');
        $data =  $this->sendData($notifiable);
        $emailTemplates = 'mail.payment_done';
        $class = "";
        $subject = "New Booking Done";
        $payment = $this->payment;
        $collection = "";
        if ($this->collection->collection_type == CollectionType::classDeck and $notifiable->role_id ==  RoleType::Guardian) {
            info(["collection 1"]);
            // $payment = $this->payment;
            $sentData = EmailSend::where('user_id', $notifiable->id)->where('email_type', EmailType::LiveClasses)->first();
            if ($sentData) {
                $emailTemplates = "mail.liveclass.live_class_payment";
                $sentData->update(['email_count' => $sentData->email_count ++]);
            }else{
                $message = sprintf(config('message.sms_live_class'), $notifiable->name);
                $this->sms($message, $notifiable->mobile);
                $sentData = [
                    'user_id'      => $notifiable->id,
                    'email_type'   => EmailType::LiveClasses,
                    'email_count'  => 1
                ];

                EmailSend::create($sentData);
                $emailTemplates = "mail.liveclass.live_class_onboard";
                $subject = "Your first virtual class is here!";
            }
        }

        $collection = $this->collectionData($this->collection);
        if (in_array($this->collection->collection_type, [CollectionType::classDeck, CollectionType::classes])) {
            $class = VendorClass::where('id', $this->collection->vendor_class_id)->first();
            $data['purchaser'] = $notifiable->name;
        }

        if (isset($collection->start_date)) {
            $data['start_date'] = $collection->start_date;
        }

        if (isset($collection->start_time)) {
            $data['start_time'] = $collection->start_time;
        }



        if (isset($collection->end_date)) {
            $data['end_date'] = $collection->end_date;
        }

        if (isset($collection->end_time)) {
            $data['end_time'] = $collection->end_time;
        }

        if (in_array($this->collection->collection_type, [CollectionType::classDeck])) {
            $collection = "";
        }


        $url = CollectionHelper::addWebUrl($this->collection);

        // return (new MailMessage)
        return (new MailMessage) ->subject($subject)->view($emailTemplates, [
                        'data'       => $data,
                        'class'      => $class,
                        'payment'    => $payment,
                        'collection' => $collection,
                        'url'        => $url
                    ]);

    }


     /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toPushNotification($notifiable)
    {
        return $this->sendData($notifiable);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
       return $this->sendData($notifiable);
    }

    private function sendData($notifiable)
    {
        $currency = "₹";
        $amount = $this->payment->amount;
        $date = $this->payment->created_at->format('M d, Y');
        $created_at = $date ." at ". $this->payment->created_at->format('h:i A');
        $collection_name = $this->collection->title;
        $orderId = $this->payment->id;
        $slug = $this->collection->slug;
        $collection_id = $this->collection->id;
        $type = $this->collection->collection_type;
        $collectionType = CollectionType::getKey($type);


        $this->payment->load('createdBy');
        $purchaser = $this->payment->createdBy ? $this->payment->createdBy->name : '';
        $studentName = "";
        if (in_array($type, [CollectionType::classDeck, CollectionType::classes])) {
            $studentName  = $this->payment->purchaser ? $this->payment->purchaser->name : "";
        }

        // "payment done amount $currency $amount at $created_at for $collection_name" :
        //     "successfully payment done amount $currency $amount for $collection_name at $created_at";

        $notificationType = "";
        $collectionTitle = "";

        if ($type == CollectionType::classDeck) {
            $notificationType =  NotificationType::LivesClassCollection;
            $collectionTitle = "ClassDeck";
        }

        if ($type == CollectionType::classes) {
            $notificationType =  NotificationType::ClassCollection;
            $collectionTitle = "Class";
        }

        if ($type == CollectionType::events) {
            $notificationType =  NotificationType::EventCollection;
            $collectionTitle = "Event";

        }

        if ($type == CollectionType::workshops) {
            $notificationType =  NotificationType::WorkShopCollection;
            $collectionTitle = "Workshop";
        }

        $amountData = $currency . number_format($amount, 2);

        $title = $this->is_partner ? sprintf(config('message.booking.partner_title'), $collectionTitle) : sprintf(config('message.booking.client_title'), $collectionTitle);
        $description = sprintf(config('message.booking.client_description'), $amountData, $collectionTitle, $collection_name, $created_at,  $this->payment->code);
        if ($this->is_partner ) {
            $description = sprintf(config('message.booking.partner_description'), $amountData, $collectionTitle, $collection_name, $created_at,  $this->payment->code);
        }

        if (in_array($notifiable->role_id, [RoleType::Guardian,RoleType::Vendor, RoleType::VendorStaff ])) {
            $this->sms($description, $notifiable->phone);
        }

        $data =  [
            'action'          => $this->is_partner ? NotificationType::CollectionBooked : NotificationType::CollectionBookByClient,
            'action_id'       => $orderId,
            'action_slug'     => $slug,
            'collection_id'   => $collection_id,
            'collection_type' => $collectionType,
            'title'           => $title,
            'description'     => $description,
            'class_id'        => $this->collection->vendor_class_id,
            'is_partner'      => $this->is_partner,
            'studentName'     => $studentName,
            'purchaser'       => $purchaser,
            'created_at'      => $created_at,
            'type'            => $notificationType,
            'collectionTitle' => $collectionTitle
        ];

        return $data;
    }


    private function sms($message, $mobile)
    {

        $client = new \GuzzleHttp\Client();
        $url = config('app.backend_api.base_url') . "/api/send/sms/notifiction";
        $data = [
            'message' => $message,
            'mobile'  => $mobile
        ];
        $client->request('POST', $url, [
            'debug'       => false,
            'verify'      => false,
            'http_errors' => false,
            'headers' => [
                // 'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'form_params' => $data
        ]);

        info('sms send');
    }

    public function collectionData($collectionData)
    {
        $published_content = json_decode($collectionData->published_content);
        $startDate= "";
        if (isset($published_content->start_date) and $published_content->start_date) {
            $startDate = Carbon::createFromFormat('Y/m/d', $published_content->start_date);
        }

        $startTime = "";
        $endDate         = "";
        $endTime         = "";

        if (isset($published_content->start_time)) {
            $startDateTime = Carbon::parse($published_content->start_time);
            $startTime = $startDateTime->format('h:i A');
        }

        if (isset($published_content->end_date) and $published_content->end_date) {
            $endStampDate = Carbon::createFromFormat('Y/m/d', $published_content->end_date);
            $endDate = $endStampDate->format('d M, Y');
        }

        if (isset($published_content->end_time)) {
            $endDateTime = Carbon::parse($published_content->end_time);
            $endTime = $endDateTime->format('h:i A');
        }

        $collection = new \stdClass();
        $collection->start_date = $startDate;
        $collection->start_time = $startTime;
        $collection->end_date   = $endDate;
        $collection->end_time   = $endTime;

        $collection->title = isset($collectionData->title) ? $collectionData->title : "";
        return $collection;
    }


}
