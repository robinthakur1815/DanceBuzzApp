<?php

namespace App\Jobs;

use App\Enums\AppType;
use App\Enums\RoleType;
use App\Helpers\CommonHelper;
use App\Model\Partner\VendorClass;
use App\Model\PartnerCollection;
use App\Notifications\NewAttachedDone;
use App\User;
use App\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendAttachedNotification
{
    use Dispatchable, Queueable, InteractsWithQueue;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $classId, $studentId, $vendorId;

    public function __construct($classId, $studentId, $vendorId)
    {
        // $this->onQueue('pn');
        $this->classId   = $classId;
        $this->studentId = $studentId;
        $this->vendorId  = $vendorId;

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try{

            $class = VendorClass::find( $this->classId);
            $vendor = Vendor::find($this->vendorId);
            $collectionData = PartnerCollection::where('vendor_class_id', $this->classId)->first();
            $sendData = CommonHelper::collectionData($collectionData);
            $studentUser = User::find($this->studentId);
            
            $studentName = $studentUser ? $studentUser->name : '';
            $class_name = $class ? $class->name : '';
            $vendorName = $vendor ? $vendor->name : '';
            $collection_name = $sendData->collectionTitle;
            $start_date = $sendData->start_date;
            $start_time = $sendData->start_time;
            $end_date = $sendData->end_date;
            $end_time = $sendData->end_time;
            $createdBy = $studentUser;

            $data = new \stdClass;
            $data->studentName = $studentName;
            $data->class_name = $class_name;
            $data->collection_name = $collection_name;
            $data->start_date = $start_date;
            $data->start_time = $start_time;
            $data->end_date = $end_date;
            $data->end_time = $end_time;

            $description =  sprintf(config('message.accthed_student'), $vendorName, $class_name );

            $url = $sendData->url;

            if ($studentUser->role_id == RoleType::Student) {
                if ($studentUser->email) {
                    $guardianEmail = explode('-', $studentUser->email, 2);
                    if (count($guardianEmail) > 0) {
                        $emailId = $guardianEmail[1];
                        $createdBy = User::where('email', $emailId)->where('app_type', AppType::Partner)->first();
                    }
                }elseif ($studentUser->phone){
                    $guardianPhone = explode('-', $studentUser->phone, 2);
                    if (count($guardianPhone) > 0 ) {
                        $phoneId = $guardianPhone[1];
                        $createdBy = User::where('phone', $phoneId)->where('app_type', AppType::Partner)->first();
                    }
                }else{
                    $createdBy = $studentUser;
                }
            }
    
            if ($createdBy) {
                $createdBy->notify(new NewAttachedDone($description, $data, $url));
            }
        }catch(\Exception $e){
            info([$e]);
        }
    }
}