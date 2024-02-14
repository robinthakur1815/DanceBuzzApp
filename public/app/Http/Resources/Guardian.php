<?php

namespace App\Http\Resources;

use App\Enums\CollectionType;
use App\Enums\Gender;
use App\Enums\PaymentStatus;
use App\Enums\RoleType;
use App\Model\Partner\CollectionOrder;
use App\Model\Partner\Discount;
use App\Story;
use App\User;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class Guardian extends JsonResource
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
        $studentIds = array();
        if ($this->students) {
            $students = $this->students;
            $studentIds = [];
            $studentUserIds = [];
            foreach ($students as $student) {
                $studentIds[] = $student->id;
                $studentUserIds[] = $student->user_id;
            }

        }
        $total_students = count($students);
        $registration = $this->registrations;

        // info($registration);
        // Number of remaining live class
        $currDate_time = now()->toDateTimeString();
        $end_date_results = $registration->where('end_date', '>=', $currDate_time);
        $num_live_class = $end_date_results->count();
        info($studentIds);
        // Total Number of classes in which student registred
        // Get vendorclass_id
        $total_liveclass_results = DB::connection('partner_mysql')->table('student_registration')
            ->whereIn('student_id', $studentIds)->latest()->get();
        $num_total_classes = $total_liveclass_results->count();
        //  info($num_total_classes);
        //return $total_liveclass_results;
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

        $Campaigns_results = Story::whereIn('student_user_id', $studentIds)->get();

        $total_Campaigns = count($Campaigns_results);

        //Get total number of classes

        $Classes_results = DB::connection('partner_mysql')->table('collections')
            ->whereIn('vendor_class_id', $vendorclass_id)
            ->where('collection_type', CollectionType::classes)
            ->get();

        $total_Classes = count($Classes_results);

        //$form = DB::table('multiforms')->count();
        //Get total number of events

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
        $students = "";
        $user->load('avatar');
        $user->load('profile');
        //  $this->load('school');
        //   $profile = $user->avatar;
        $enthu_point = 0;

        $userAvatar = null;
        if ($this->user && $this->user->partnerAvatar) {
            $userAvatar = $this->user->partnerAvatar;
        }
        if ($user->profile and $user->profile->enthu_points) {
            $enthu_point = (int) $user->profile->enthu_points;
        }
        /*  $transactions = DB::connection('partner_mysql')->table('payments')
        ->whereIn('student_id', $studentIds)->latest()->get(); */

        //     return $transactions;
        // return $transactions;die();

        $totalUserIds = array_merge($studentUserIds, [$user->id]);

        $transactions = CollectionOrder::with('collection', 'purchaser.student', 'purchaser.guardian')->whereIn('purchaser_id', $totalUserIds)->whereNull('deleted_at')->latest()->take(10)->get();

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
        if ($this->country_id) {
            $country = DB::connection('partner_mysql')->table('countries')->where('id', $this->country_id)->first();
            $country_code = $country->phonecode;
        }

        return [
            'name' => $this->user->name,
            'firsLast' => Str::title($this->user->name[0]),
            'avatar' => $userAvatar,
            'enthu_points' => $enthu_point,
            'email' => $this->user->email,
            'phone' => $this->user->phone,
            'country_code' => $country_code,
            'username' => $this->user->username,
            'id' => $this->id,
            'address' => $this->address,
            'gender' => Gender::getKey($this->gender),
            'created_at' => $this->user->created_at,
            'updated_at' => $this->user->updated_at,
            'is_active' => $this->user->is_active ? true : false,
            'platformtype' => $device,
            'live_class' => $total_live_class,
            'workshop' => $total_workshop,
            'campaigns' => $total_Campaigns,
            'classes' => $total_Classes,
            'events' => $total_events,
            'total_students' => $total_students,
            'students' => $this->students ? $this->getStudents($this->students) : null,
            'role' => 'Guardian',
            'latest_transaction' => $transactionData,

            //'live_results'              => $live_results,
            //'events_results'            => $events_results,
            // 'Classes_results'           => $Classes_results,
            //'Campaigns_results'         => $Campaigns_results,
            // 'workshop_results'          => $workshop_results,

        ];

        return parent::toArray($request);
    }

    private function getStudents($students)
    {
        $updatedStudents = collect();
        foreach ($students as $key => $student) {
            $updatedStudents->push([
                'name' => $student->user->name,
                'email' => $student->user->email,
                'username' => $student->user->username,
                'phone' => $student->user->phone,
                'created_at' => $student->user->created_at,
                'schoolName' => $student->school ? $student->school->name : '',
                'student_id' => $student->id,
                //'studentdata' => new StudentCollection($this->student)

            ]
            );
        }

        return $updatedStudents;
    }
}
