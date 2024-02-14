<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Collection as AppCollection;
use App\Enums\CollectionType;
use App\Enums\LiveClassStatus;
use App\Enums\PaymentStatus;
use App\Enums\PublishStatus;
use App\Enums\RoleType;
use App\Helpers\LiveClassHelper;
use App\Http\Controllers\Controller;
// use App\Enums\RoleType;
use App\Http\Resources\LiveClassSession as LiveClassSessionResource;
use App\Http\Resources\LiveClassSessionCollection;
use App\Http\Resources\MobileData;
use App\Http\Resources\MobileDataCollection;
// use App\Model\Partner\PartnerClass;
use App\Model\Partner\PartnerLiveClass;
use App\Model\Partner\PartnerLiveClassSchedule;
use App\Model\Partner\StudentRegistration;
use App\Order;
use Carbon\Carbon;
use DB;
// use Carbon\Carbon;
use Illuminate\Http\Request;

class ApiAuthUserController extends Controller
{

    public function myDashboard()
    {
        $collectionOnly = true;
        $collectionIds = $this->getClassesIds($collectionOnly);

        info([$collectionIds]);

        $from = now()->format('Y/m/d');
        $to = now()->format('Y/m/d');
        $todayCollections = $this->getCollectionDateWise($from, $to, $collectionIds);

        $from = now()->startOfWeek()->format('Y/m/d');
        $to = now()->format('Y/m/d');
        $weekCollections = $this->getCollectionDateWise($from, $to, $collectionIds);

        $from = now()->startOfMonth()->format('Y/m/d');
        $to = now()->format('Y/m/d');
        $monthCollections = $this->getCollectionDateWise($from, $to, $collectionIds);

        $data = [
            [
                'title' => 'Today',
                'data'  =>  new MobileDataCollection($todayCollections)
            ],

            [
                'title' => 'Week',
                'data'  =>  new MobileDataCollection($weekCollections)
            ],

            [
                'title' => 'Month',
                'data'  =>  new MobileDataCollection($monthCollections)
            ]
        ];


        return response(['data' => $data], 200);
    }

    public function dashboard(Request $request)
    {
        // $userIds = $this->getStudentsIds();
        // $collections = [CollectionType::classes,  CollectionType::liveClass, CollectionType::workshops, CollectionType::events];
        // $datas = AppCollection::whereNotNull('published_content')->whereHas('confirmOrders', function($q) use($userIds) {
        //     $q->whereIn('purchaser_id', $userIds);
        // })->whereIn('collection_type', $collections)
        //         ->get();
        //         ->keyBy('collection_type')
        //         ->unique();

        // return  new MobileDataCollection($datas);

        // $datas = [];
        // foreach ($collections as $collectionType) {
        //     $data = $this->getBoughtCollection($userIds, $collectionType);
        //     if ($data) {
        //         $datas[] = $data;
        //     }
        // }


        $classesIds = $this->getClassesIds();
        $users = [auth()->id()];
        $collectionsLiveClass = $this->getCollections([CollectionType::classes,  CollectionType::classDeck], [], $classesIds );
        $collectionsWorkshopsEvents = $this->getCollections([CollectionType::events,  CollectionType::workshops], $users, []);


        // return new MobileDataCollection($collectionsLiveClass);
        $liveClassCollections = $this->changeOrderOfCollection(collect(collect($collectionsLiveClass)->where('collection_type', CollectionType::classDeck)->all()));
        $classCollections = $this->changeOrderOfCollection(collect(collect($collectionsLiveClass)->where('collection_type', CollectionType::classes)->all()));
        $workshopCollections = $this->changeOrderOfCollection(collect(collect($collectionsWorkshopsEvents)->where('collection_type', CollectionType::workshops)->all()));
        $eventsCollections = $this->changeOrderOfCollection(collect(collect($collectionsWorkshopsEvents)->where('collection_type', CollectionType::events)->all()));

        $collections = collect([$liveClassCollections, $workshopCollections, $classCollections, $eventsCollections])->collapse()->all();
        $data = [];
        foreach($collections as $collection){
            if ($collection and isset($collection->id)) {
                $data [] = new MobileData($collection);
            }
        }

        return collect($data);
        return new MobileDataCollection(collect($collections));

    }

    public function myOrders(Request $request)
    {
        $type = $request->type;
        $collections = [CollectionType::events, CollectionType::workshops, CollectionType::classes,  CollectionType::classDeck];
        if (! in_array($type, $collections)) {
            return collect([]);
        }

        $max_rows = $request->max_rows ?? 10;
        if ($type == CollectionType::classDeck || $type == CollectionType::classes) {
            $classesIds = $this->getClassesIds();
            $datas = AppCollection::whereIn('vendor_class_id', $classesIds)->where('collection_type', $type)
            ->whereNotNull('published_content')
            ->latest()->paginate($max_rows);
        } else {
            $userIds = $this->getStudentsIds($type);

            $datas = AppCollection::whereHas('confirmOrders', function ($q) use ($userIds) {
                $q->whereIn('purchaser_id', $userIds);
            })->where('collection_type', $type)->whereNotNull('published_content')->latest()->paginate($max_rows);
        }

        $datas->getCollection()->transform(function ($item) {
            $item->published_content = json_decode($item->published_content);
            $item['ischeck'] = true;

            return $item;
        });

        return new MobileDataCollection($datas);
    }

    private function getCollectionDateWise($from, $to, $collectionIds)
    {
        $datas = AppCollection::whereIn('id', $collectionIds)
                                        ->whereNotNull('published_content')
                                        ->where('saved_content->end_date', '>=', $from)
                                        // ->where('saved_content->end_date', '<=', $to)
                                        ->latest()->get();
        return $datas;
    }

    public function liveClassSessions(Request $request)
    {
        $classId = $request->collection_id;
        // $userIds = $this->getStudentsIds();
        $collections = [  CollectionType::classDeck];
        $upcoming = $request->upcoming;

        // $vendorClassesIds =  $this->getClassesIds();

        $data = AppCollection::where('id', $classId)
        // ->whereIn('vendor_class_id',  $vendorClassesIds)
        ->whereIn('collection_type', $collections)->latest()->first();

        if (!$data) {
            return response(['errors' => ['collection' => ['collection not found']], 'status' => false, 'message' => ''], 422);
        }

        $vendorClassId = $data->vendor_class_id;

        // $class = PartnerClass::where('id',  $vendorClassId)->first();

        $liveClass = PartnerLiveClass::where('vendor_class_id',  $vendorClassId)->first();

        if (!$liveClass) {
            return response(['errors' => ['collection' => ['collection not found']], 'status' => false, 'message' => ''], 422);
        }
        $max_rows = $request->max_rows ?? 10;
        $sessions = PartnerLiveClassSchedule::where('live_class_id',$liveClass->id);

        $startDateTime = now()->addMinutes($liveClass->duration)->toDateTimeString();

        if ($upcoming) {
            $activeStatus = [LiveClassStatus::Active, LiveClassStatus::ReActivate,  LiveClassStatus::Running, LiveClassStatus::ReSchedule];
            $sessions =  $sessions->where('start_date_time', '>=', $startDateTime)
                                    ->whereIn('status', $activeStatus);
        }else{
            $inActiveStatus = [LiveClassStatus::Suspended, LiveClassStatus::Completed,  LiveClassStatus::Expired];
            $sessions = $sessions->where(function($q) use($startDateTime, $inActiveStatus){
                $q->where('start_date_time', '<', $startDateTime)
                ->orWhereIn('status', $inActiveStatus );
            });
        }

        $sessions = $sessions->orderBy('start_date_time', 'ASC')->paginate($max_rows);
        return new LiveClassSessionCollection($sessions);
        return  response(['data' => $sessions]) ;
    }

    public function latestLiveClass(Request $request)
    {
        // $type = [CollectionType::liveClass];
        // $userIds = $this->getStudentsIds($type );
        // $vendorClassesIds = AppCollection::whereHas('confirmOrders', function($q) use($userIds) {
        //                                 $q->whereIn('purchaser_id', $userIds);
        //                             })->where('collection_type', $type)
        //                             ->latest()->pluck('vendor_class_id');
        $vendorClassesIds = $this->getClassesIds();

        $partnerClasses = PartnerLiveClass::whereIn('vendor_class_id', $vendorClassesIds)->pluck('id');

        $session = PartnerLiveClassSchedule::whereIn('live_class_id', $partnerClasses)
                                            ->with('vendorLiveClass.vendorClass')
                                            ->where('status', LiveClassStatus::Running)
                                            ->whereDate('start_date_time', '>=', now()->toDateString())
                                            ->orderBy('start_date_time', 'ASC')
                                            ->latest()->first();

        if ($session) {
            $collection = AppCollection::where('vendor_class_id', $session->class_id)->first();
            $collectionId = $collection->id;
            $session['is_details'] = true;
            $session['collection_id'] = (string) $collectionId;
            $session['published_content'] = $collection->published_content;

            return response(['data' => new LiveClassSessionResource($session)]);
        }

        return response(['errors' => ['session' => ['session not found']], 'status' => false, 'message' => ''], 422);
    }

    public function liveClassHistorySessions(Request $request)
    {
        $live_class_id = $request->live_class_id;
        // $userIds = $this->getStudentsIds();
        // $type = [CollectionType::liveClass];

        // $vendorClassesIds = AppCollection::whereHas('confirmOrders', function($q) use($userIds) {
        //                                 $q->whereIn('purchaser_id', $userIds);
        //                             })->whereIn('collection_type', $type)
        //                             ->latest()->pluck('vendor_class_id');
        $vendorClassesIds = $this->getClassesIds();

        $partnerClasses = PartnerLiveClass::whereIn('vendor_class_id', $vendorClassesIds)->where('id', $live_class_id)->pluck('id');

        $max_rows = $request->max_rows ?? 10;
        $sessions = PartnerLiveClassSchedule::with('vendorLiveClass.vendorClass')
            ->whereIn('live_class_id', $partnerClasses);

        $sessions = $sessions->latest()->paginate($max_rows);

        return new LiveClassSessionCollection($sessions);
    }

    public function classRecordings(Request $request)
    {
        // $userIds = $this->getStudentsIds();
        $live_class_id = $request->live_class_id;
        // $type = [CollectionType::liveClass];
        // $vendorClassesIds = AppCollection::whereHas('confirmOrders', function($q) use($userIds) {
        //                                 $q->whereIn('purchaser_id', $userIds);
        //                             })->whereIn('collection_type', $type)
        //                             ->latest()->pluck('vendor_class_id');

        $vendorClassesIds = $this->getClassesIds();

        $partnerClasses = PartnerLiveClass::whereIn('vendor_class_id', $vendorClassesIds)->where('id', $live_class_id)->pluck('id');

        $sessions = PartnerLiveClassSchedule::where('is_recorded', true)
            ->whereIn('live_class_id', $partnerClasses)
            ->where('live_class_id', $live_class_id);
        $session = $sessions->first();
        $sessions = $sessions->pluck('meeting_id');

        $server = LiveClassHelper::getVendorData($session->vendor_id ?? 'random');

        $sessionsRecordings = LiveClassHelper::getRecordings($server, $sessions);

        $recordingIds = [];
        foreach ($sessionsRecordings as $sessionsRecording) {
            $recordingIds[] = $sessionsRecording['id'];
        }

        $sessions = PartnerLiveClassSchedule::with('vendorLiveClass.vendorClass')->whereIn('meeting_id', $recordingIds)->orderBy('start_date_time', 'ASC')->paginate(count($recordingIds));

        $sessions->getCollection()->transform(function ($item) use ($sessionsRecordings) {
            $recording = $sessionsRecordings->where('id', $item->meeting_id)->first();
            $item['url'] = '';
            $item['images'] = [];
            if ($recording) {
                $item['url'] = $recording['url'];
                $item['images'] = $recording['preview'];
            }
            $item['is_recording'] = true;

            return $item;
        });

        return new LiveClassSessionCollection($sessions);
    }

    private function getCollectionIds()
    {

    }

    private function getCollections($types, $users = [], $classesIds = [])
    {
        if (count($users) and count($classesIds)) {
            return collect([]);
        }
        $endDate = now()->format('Y/m/d');
        $allCollections = AppCollection::with(['product.prices.discounts', 'product.productReviews'])
                        ->where('status',  PublishStatus::Published)
                        ->whereNotNull('published_content')
                        ->whereIn('collection_type', $types )
                        ->where('published_content->end_date', '>=', $endDate)
                        ->latest();

        if (count($classesIds)) {
            $allCollections = $allCollections->whereIn('vendor_class_id', $classesIds);
        }

        if (count($users)) {
            $allCollections = $allCollections->whereHas('confirmOrders', function($q) use($users){
                $q->whereIn('purchaser_id', $users);
            });
        }

        $allCollections = $allCollections->get();

        $allCollections->map(function ($item) {
            $content = json_decode($item->published_content);
            $item->published_content = $content;
            $startDate = "";
            if ($content and isset($content->start_date) and $content->start_date) {
                $startDate = Carbon::createFromFormat('Y/m/d', $content->start_date)->toDayDateTimeString();
            }else{
                $startDate = now()->toDayDateTimeString();
            }
            $item['start_date_sort'] = $startDate;
            $item['ischeck'] = true;
            return $item;
        })->all();

        return collect($allCollections);
    }

    private function changeOrderOfCollection($collections)
    {
        $collections = $collections->sortByDesc(function($collection){
                        return $collection->start_date_sort;
                    })->all();

        return collect($collections);
    }

    private function getBoughtCollection($userIds, $collectionType)
    {
        if ($collectionType == CollectionType::classDeck || $collectionType == CollectionType::classes) {
            $classesIds = $this->getClassesIds();
            if (count($classesIds)) {
                $datas = AppCollection::whereNotNull('published_content')
                ->whereIn('vendor_class_id', $classesIds)
                ->where('collection_type', $collectionType)
                ->first();
                if (! $datas) {
                    return null;
                }

                return  new MobileData($datas);
            }

            return null;
        }

        $order = Order::whereIn('purchaser_id', $userIds)->whereHas('withoutTrashCollection', function ($q) use ($collectionType) {
            $q->where('collection_type', $collectionType);
        })->where('payment_status', PaymentStatus::Received)->latest()->first();

        if (! $order) {
            return null;
        }

        $datas = AppCollection::whereNotNull('published_content')->where('id', $order->collection_id)->where('collection_type', $collectionType)->first();

        if (! $datas) {
            return null;
        }

        return  new MobileData($datas);
    }

    private function getStudentsIds($type = null)
    {
        $user = auth()->user();
        $authId = auth()->id();
        $singleUsers = [RoleType::Student, RoleType::Vendor, RoleType::VendorStaff];
        if (in_array($user->role_id, $singleUsers)) {
            return [$authId];
        }

        if ($type and in_array($type, [CollectionType::events, CollectionType::workshops])) {
            return [$authId];
        }

        $guardian = DB::connection('partner_mysql')->table('guardians')->where('user_id', auth()->id())->first();
        $students = DB::connection('partner_mysql')->table('student_guardian')->where('guardian_id', $guardian ? $guardian->id : '')->pluck('student_id');

        // Studetn
        $studentsIds = DB::connection('partner_mysql')->table('students')->whereIn('id', $students)->pluck('user_id');

        return $studentsIds->push($authId)->all();
    }

    private function getClassesIds($collectionOnly = null)
    {
        $user = auth()->user();
        $authId = auth()->id();
        $singleUsers = [RoleType::Student];
        $studentsIds = [];
        $vendorClassIds = [];
        $orderCollectionIds = [];
        $collectionIds = [];
        if (in_array($user->role_id, $singleUsers)) {
            $studentsIds = DB::connection('partner_mysql')->table('students')->where('user_id', $authId)->pluck('id');
        } else {
            $guardian = DB::connection('partner_mysql')->table('guardians')->where('user_id', auth()->id())->first();
            if ($guardian) {
                $studentsIds = DB::connection('partner_mysql')->table('student_guardian')->where('guardian_id', $guardian ? $guardian->id : '')->pluck('student_id');
            }
        }

        if (count($studentsIds)) {
            $vendorClassIds = StudentRegistration::whereIn('student_id', $studentsIds)->latest()->pluck('vendorclass_id');
        }

        if ($collectionOnly) {

            if (count($vendorClassIds)) {
                $collectionIds = AppCollection::whereIn('vendor_class_id', $vendorClassIds)->pluck('id');
            }

            $orderCollectionIds  = Order::where('purchaser_id', $user->id)
                            ->where('payment_status', PaymentStatus::Received)
                            ->latest()->pluck('collection_id');

            if (count($orderCollectionIds) and count($collectionIds)) {
                $collectionIds =  collect($collectionIds)->merge(collect($orderCollectionIds))->all();
            }

            return $collectionIds;
        }

        return $vendorClassIds;
    }
}
