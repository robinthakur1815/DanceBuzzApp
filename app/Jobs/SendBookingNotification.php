<?php

namespace App\Jobs;

use App\Enums\AppType;
use App\Enums\CollectionType;
use App\Enums\RoleType;
use App\Model\Partner\CollectionOrder;
use App\Notifications\NewBookingDone;
use App\User;
use App\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class SendBookingNotification
{
    use Dispatchable, Queueable, InteractsWithQueue;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $order;

    public function __construct(CollectionOrder $order)
    {
        // $this->onQueue('pn');
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(CollectionOrder $order)
    {
        try{

            $order = $this->order;
            $order->load('collection', 'purchaser', 'createdBy');
    
            $collection = $order->collection;
            $partnerUser = null;
            $createdByUser = $order->purchaser;
            
            $createdBy = $order->createdBy;
            
            if ($createdBy->role_id and in_array($createdBy->role_id, [RoleType::Student, RoleType::Vendor, RoleType::VendorStaff])) {
               
                if ($createdByUser->email) {
                    $guardianEmail = explode('-', $createdByUser->email, 2);
                    if (count($guardianEmail) > 0) {
                        $emailId = $guardianEmail[1];
                        $createdBy = User::where('email', $emailId)->where('app_type', AppType::Partner)->first();
                    }
                }elseif ($createdByUser->phone){
                    $guardianPhone = explode('-', $createdByUser->phone, 2);
                    if (count($guardianPhone) > 0 ) {
                        $phoneId = $guardianPhone[1];
                        $createdBy = User::where('phone', $phoneId)->where('app_type', AppType::Partner)->first();
                    }
                }else{
                    $createdBy = null;
                }
    
                // if ($student) {
                //     $guardianIdData = DB::connection('partner_mysql')->table('student_guardian')
                //     ->where('student_id', $student->id)
                //     ->first();
                //     if($guardianIdData) {
                //         $guardian = DB::connection('partner_mysql')->table('guardians')
                //         ->where('id', $guardianIdData->guardian_id)
                //         ->first();
                //         if ($guardian) {
                //             $createdBy = User::where('id', $guardian->user_id)->first();
                //         }
                //     }
                // }
            }
    
            if ($collection and $collection->vendor_id) {
                $vendor = Vendor::find($collection->vendor_id);
                if ($vendor) {
                    $partnerUser = User::where('id', $vendor->created_by)->first();
                }
            }
    
            if ($createdByUser) {
                $createdByUser->notify(new NewBookingDone($order, $collection, false, false));
            }
    
            if ($partnerUser) {
                $partnerUser->notify(new NewBookingDone($order, $collection, true, true));
            }
    
            if ($createdBy) {
                $createdBy->notify(new NewBookingDone($order, $collection, false, true));
            }
        }catch(\Exception $e){
            info([$e]);
        }
    }
}