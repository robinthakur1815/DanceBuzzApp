<?php

namespace App\Http\Resources;

use DB;
use App\User;
use App\Story;
use Carbon\Carbon;
use App\Enums\Gender;
use App\Enums\RoleType;
use App\Enums\PlatformType;
use App\Helpers\UserHelper;
use Illuminate\Support\Str;
use App\Enums\PaymentStatus;
use App\Enums\CollectionType;
use App\Model\Partner\Discount;
use App\Model\Partner\CollectionOrder;
use App\Http\Resources\GuardianCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class Student extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $user = $this->user;
        $registration = $this->registrations;

        // Number of remaining live class
        $currDate_time = now()->toDateTimeString();
        $end_date_results = $registration->where('end_date', '>=', $currDate_time);

        $num_live_class = $end_date_results->count();

        // Total Number of classes in which student registred

        // Get vendorclass_id
        $total_liveclass_results = $registration->where('student_id', '=', $this->id);

        $num_total_classes = $total_liveclass_results->count();

        //return $arr_total_classes;

        //Get vendor_class_id for collection table

        $vendorclass_id = array();

        foreach ($total_liveclass_results as $rows) {
            $vendorclass_id[] = $rows->vendorclass_id;
        }

        //Get total number of live class

        $live_results = DB::connection('partner_mysql')->table('collections')
            ->whereIn('vendor_class_id', $vendorclass_id)
            ->where('collection_type', CollectionType::classDeck)
            ->get();

        $total_live_class = count($live_results);

        //Get total number of workshop

        $workshop_results = DB::connection('partner_mysql')->table('collections')
            ->whereIn('vendor_class_id', $vendorclass_id)
            ->where('collection_type', CollectionType::workshops)
            ->get();

        $total_workshop = count($workshop_results);

        //Get total number of campaign

        $Campaigns_results = Story::where('student_user_id', $this->user_id)->get();

        $total_Campaigns = count($Campaigns_results);

        //Get total number of classes

        $Classes_results = DB::connection('partner_mysql')->table('collections')
            ->whereIn('vendor_class_id', $vendorclass_id)
            ->where('collection_type', CollectionType::classes)
            ->get();

        $total_Classes = count($Classes_results);

        //$form = DB::table('multiforms')->count();
        //Get total number of events

        $studentInfo = isset($this->meta) ? json_decode($this->meta, true) : '';
        $grade = $studentInfo && $studentInfo['grades'] ? $studentInfo['grades'] : "Not Available";
        $section = $studentInfo && $studentInfo['section'] ? $studentInfo['section'] : "Not Available";
        $admission_number = $studentInfo && $studentInfo['admission_number'] ? $studentInfo['admission_number'] : "Not Available";

        $events_results = DB::connection('partner_mysql')->table('collections')
            ->whereIn('vendor_class_id', $vendorclass_id)
            ->where('collection_type', CollectionType::events)
            ->get();

        $total_events = count($events_results);

        $Devicetype = DB::connection('mysql2')->table('device_tokens')
            ->where('user_id', $this->user_id)
            ->latest()
            ->get();

        $device = isset($Devicetype->platform_type) ? $Devicetype->platform_type : "N/A";
        // $device =  isset($Devicetype) ? json_decode($Devicetype ,true) :'';
        // info($device);
        // $platformtype = $device && $device['platform_type'] ?  $device['platform_type']  : "Not Available" ;

        $user->load('avatar');
        $user->load('profile');
        $this->load('school');
        $profile = $user->avatar;

        $userAvatar = null;
        if ($this->user && $this->user->partnerAvatar) {
            $userAvatar = $this->user->partnerAvatar;
        }
        $register_type = "";
        $enthu_point = 0;
        if (!$profile) {
            UserHelper::createUserProfile($user->id);
        }

        $user->refresh();

        if ($user->profile and $user->profile->enthu_points) {
            $enthu_point = (int) $user->profile->enthu_points;
        }

        if ($user->profile and $user->profile->register_type) {
            $register_type = (int) $user->profile->register_type;
        }

        // $is_school_student = $grade = $studentInfo && $studentInfo['grades'] ? $studentInfo['grades'] : '';
        // if ($is_school_student){
        // $transactions = CollectionOrder::with('collection', 'purchaser.student', 'purchaser.guardian')
        // ->where('purchaser_id',$user->id)
        // ->whereHas('collection',function($q){
        // $q->whereIn('collection_type',);
        // })->where('meta->collection_type',[CollectionType::events,CollectionType::workshops] )->whereNull('deleted_at')->latest()->take(10)->get();
        // }
        // else{
        $transactions = CollectionOrder::with('collection', 'purchaser.student', 'purchaser.guardian')->where('purchaser_id',$user->id)->whereIn('meta->collection_type',[CollectionType::events,CollectionType::workshops] )->whereNull('deleted_at')->latest()->take(10)->get();
        
       $transactionData = [];
       foreach ($transactions as $orders) {

           // Calculate Discount start ///

           $discount_value = "";
           if ($orders) {

               $discount = Discount::find($orders->discount_id);
               $discount_value = $discount ? $discount->value : null;
               $discount_percentage = $discount ? $discount->isPercentage : null;
           }

           $stateId = '';
           if ($orders->purchaser) {
               $userRoleData = $orders->purchaser;

               $stateId = $userRoleData->role_id == RoleType::Guardian ? $userRoleData->guardian->state_id : '';

               if (!$stateId) {

                   $stateId = $userRoleData->role_id == RoleType::Student ? $userRoleData->student->state_id : '';

               }
           }

           if ($stateId == config('app.tax_state')) {
               $igst = round(trim($orders->amount) * 18 / 100, 2);
               $cgst = number_format((float) 0, 2);
               $sgst = number_format((float) 0, 2);
               $rate1 = round(trim($orders->amount) - $igst, 2); // 2 is fo place value in decimals 800.00
               $tot_gst = trim($cgst) + trim($sgst);
           } else {
               $igst = number_format((float) 0, 2);
               $cgst_check = $orders ? $orders->amount : 0;
               $cgst = number_format((float) $cgst_check * 9 / 100, 2);
               $sgst_check = $orders ? $orders->amount : 0;
               $sgst = number_format((float) $sgst_check * 9 / 100, 2);
               $tot_gst = round($cgst) + round($sgst);
               $rate1 = round(trim($orders ? $orders->amount : 0) - $tot_gst, 2); // 2 is fo place value in decimals 800.00
           }

           if ($discount_value) {

               if ($discount_percentage == "1") {
                   $discount_price = round($rate1 * trim($discount_value) / 100, 2);
                   $total = round($rate1 - $discount_price, 2);
                   $discount = $discount_price;
                   $payable_amount = $total + $igst + $tot_gst;
               } else {
                   $total = round($rate1 - $discount_value, 2);
                   $discount = $discount_value;
                   $payable_amount = $total + $igst + $tot_gst;
               }
           } else {

               $total = $rate1;
               $discount = number_format('0', 2);
               $payable_amount = $total + $igst + $tot_gst;
           }

           // Calculate Discount end ///

           if ($orders) {
               $collection_type = isset($orders->collection) ? $orders->collection->collection_type : '';
               $description = isset($orders->collection) ? $orders->collection->title : '';
               $userId = $orders->purchaser_id;
               //$userName = User::on('mysql2')->where('id', $userId)->first();
               $user_name = $orders->purchaser ? $orders->purchaser->name : '';
           } else {
               $collection_type = null;
               $description = "";

           }
           switch ($collection_type) {
               case 11:
                   $collection_name = "Events";
                   break;
               case 25:
                   $collection_name = "Classes";
                   break;
               case 26:
                   $collection_name = "Workshops";
                   break;
               case 37:
                   $collection_name = "ClassDeck";
                   break;
               case null:
                   $collection_name = "";
                   break;
           }

           $mode_payment = $orders->payment_mode ? str_replace('_', ' ', $orders->payment_mode) : "N/A";
           $payment_mode = ucwords(strtolower($mode_payment));
           $transactionData[] = [
               'order_id' => $orders->code,
               'item' => $description,
               'course' => $collection_name,
               'billing_date' => Carbon::createFromFormat('Y-m-d H:i:s', $orders->created_at)->format('d.m.Y'),
               'total' => number_format($payable_amount, 2),
               'pament_status' => PaymentStatus::getKey($orders->payment_status),
               'payment_mode' => $payment_mode,
               'billing_name' => isset($user_name) ? $user_name : '',

           ];
       }

        if ($request->getAll) {
            $registrations = $registration->all();
        } else {
            $registrations = $registration->take(5);
        }
        $recent_activity = [];
        foreach ($registrations as $registration) {

            $collection = DB::connection('partner_mysql')->table('collections')
                ->where('vendor_class_id', $registration->vendorclass_id)
                ->first();

            $collectionName = $collection->title;
            $collectionType = $collection->collection_type;

            $recent_activity[] = [
                'date' => $registration->created_at->format('Y.m.d'),
                'collection_name' => $collectionName,
                'collection_type' => $collectionType,
            ];
        }

        $data = [
            'id' => $this->id,
            'name' => Str::title($user->name),
            'last_name' => Str::title($user->last_name),
            'first_last' => '',
            'email' => $user->email,
            'phone' => $user->phone,
            'username' => $user->username,
            'is_active' => $user->is_active ? true : false,
            'user_id' => $this->user_id,
            'platformtype' => $register_type ? PlatformType::getKey($register_type) : '',
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'guardians' => $this->guardians,
            'dob' => $this->dob,
            'gender' => $this->gender ? Gender::getKey($this->gender) : '',
            'enthu_point' => $enthu_point,
            'avatar' => $userAvatar,
            'school' => $this->school,
            'dob' => $this->dob,
            'gender' => $this->gender,
            'live_class' => $total_live_class,
            'live_results' => $live_results,
            'events_results' => $events_results,
            'Classes_results' => $Classes_results,
            'Campaigns_results' => $Campaigns_results,
            'workshop_results' => $workshop_results,
            'workshop' => $total_workshop,
            'campaigns' => $total_Campaigns,
            'classes' => $total_Classes,
            'events' => $total_events,
            'grade' => $grade,
            'section' => $section,
            'admission_number' => $admission_number,

            //

        ];

        if (isset($user->name[0])) {
            $data['firsLast'] = Str::title($user->name[0]);
        }

        if (isset($user->last_name[0])) {
            $data['firsLast'] = $data['firsLast'] . '' . Str::title($user->last_name[0]);
        }

        if ($this->is_detail || $this->only_user) {
            $data['gender'] = (!$this->only_user) ? Gender::getKey($this->gender) : $this->gender;
            $data['dob'] = $this->dob ? $this->dob : '';
            $data['school_id'] = $this->school_id;
            $data['school_name'] = $this->school ? $this->school->name : '';
            $data['school_director'] = $this->school ? $this->school->school_director : '';
            $data['school_email'] = $this->school ? $this->school->school_email : '';
            $data['school_phone'] = $this->school ? $this->school->school_phone : '';
            $data['registration_note'] = $this->registration_note;
            $data['role'] = 'Student';
            $data['enthu_points'] = $user && $user->profile ? $user->profile->enthu_points : '';
            $data['register_type'] = $user && $user->profile ? $user->profile->register_type : '';
            $data['gender'] = $user && $user->profile && $user->profile->gender ? Gender::getKey($this->gender) : '';
            $data['registration_code'] = $this->registrations && count($this->registrations) > 0 ? $this->registrations[0]->registration_code : null;
            $data['registration_data'] = $this->registrations;
            $data['latest_transaction'] = $transactionData;
            $data['recent_activity'] = $recent_activity;
        }

        if ($this->is_detail) {
            $address = $this->address;
            if ($this->city) {
                $address = $address . ', ' . $this->city;
            }
            // if ($this->state_id)
            //     $address = $address . ', ' . $this->state->name;
            if ($this->zipcode) {
                $address = $address . '- ' . $this->zipcode;
            }
            $data['address'] = $address;

            $data['school_address'] = $this->school ?
            $this->school->address . ', '
            . $this->school->city . ', '
            . ($this->school->state ? $this->school->state->name : '') . '- '
            . $this->school->zipcode : '';

            $data['guardians'] = new GuardianCollection($this->guardians);
        } elseif ($this->only_user) {
            $data['address'] = $this->address ? $this->address : '';
            $data['state_id'] = $this->school ? $this->state_id : '';
            $data['city'] = $this->school ? $this->city : '';
            $data['zipcode'] = $this->school ? $this->zipcode : '';

            $data['school_address'] = $this->school ? $this->school->address : '';
            $data['school_state_id'] = $this->school ? $this->school->state_id : '';
            $data['school_city'] = $this->school ? $this->school->city : '';
            $data['school_zipcode'] = $this->school ? $this->school->zipcode : '';
        }

        return $data;
    }
}
