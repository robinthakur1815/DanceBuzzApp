<?php

namespace App\Http\Resources;

use App\Enums\CollectionType;
use App\Model\Guardian;
use App\Model\Student;
use App\Student as AppStudent;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use DB;

class Transaction extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {   
        $user = User::find($this->created_by);
        $guardian = Guardian::where('user_id',$this->created_by)->first();
        if(!$guardian){
            $guardian = Student::where('user_id',$this->created_by)->first();

        }
        $orderData = json_decode($this->pg_request_data);
        $billing_name = $orderData ? $orderData->customerName : $user->name;
        $billing_email = $orderData ? $orderData->customerEmail: $user->email ;
        $orderDate =   $this->created_at;
        $billingAddress = $guardian->address;
        $collection = DB::connection('partner_mysql')->table('collections')
                          ->find($this->collection_id);
        $product_name  = $collection->title; 
        
        if($guardian->country_id){
            $country = DB::connection('partner_mysql')->table('countries')
            ->find($guardian->country_id);
        }
      
        return  [
           'order_id'            =>     $this->code,
           'order_note'          =>     $this->order_note,
           'billing_name'        =>     $billing_name,
           'billing_email'       =>     $billing_email,
           'order_date'          =>     $orderDate,
           'billing_address'     =>     $billingAddress,
           'billing_city'        =>     $guardian->city,
           'billing_zipcode'     =>     $guardian->zipcode,
           'billing_country'     =>     $country ? $country->name : null,
           'product_name'        =>     $product_name,
           'amount'              =>     $this->amount,
           'currency'            =>     $this->currency,
           'product_type'        =>     $collection->collection_type,
           
       ];


        return parent::toArray($request);
    }
    private function dateFormat($date)
    {
        return Carbon::createFromFormat('d/m/Y', $date)->format('Y/m/d');
    }
}
