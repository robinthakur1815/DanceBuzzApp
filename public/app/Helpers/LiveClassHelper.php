<?php

namespace  App\Helpers;

use App\Collection;
use App\Enums\CollectionType;
use App\Enums\LiveClassStatus;
use App\Enums\PaymentStatus;
use App\Model\Partner\PartnerLiveClass;
use App\Model\Partner\PartnerLiveClassSchedule;
use App\Model\Partner\StudentRegistration;
use App\Model\Student;
use App\Order;
use App\ProductPrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;

class LiveClassHelper extends Facade
{
    public function createLiveClass()
    {
        $random = Str::random(10);

        return $random;
    }

    public static function getURL($server, $request, $ops = [])
    {
        $liveurl = $server->api; // config('app.liveclass.api'); // new production
        $salt = $server->secret; //config('app.liveclass.secret'); // production new
        $string = http_build_query($ops);
        $url = $liveurl.$request.'?'.$string.'&checksum='.sha1($request.$string.$salt);

        return $url;

        // return 'http://live.acadox.net/bigbluebutton/api/' . $request . '?' . $string . '&checksum=' . sha1($request . $string . $salt); // production
    }

    public static function getData($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $authToken = curl_exec($ch);

        return $authToken;
    }

    public static function getRecordings($sessions, $server)
    {
        $joinIds = null;
        $sessionIds = [];
        foreach ($sessions as $session) {
            $sessionIds[] = $session;
        }
        if (count($sessionIds)) {
            $joinIds = implode(',', $sessionIds);
        }
        // info([$joinIds]);
        $datas = [];
        if ($joinIds) {
            $url = self::getURL($server, 'getRecordings', [
                'meetingID' => $joinIds,
            ]);

            $xml = self::getData($url);

            $obj = json_decode(json_encode((array) simplexml_load_string($xml)));
            if ($obj && $obj->returncode == 'SUCCESS') {
                $recordings = $obj->recordings;
                foreach ($recordings as $recording) {
                    if (isset($recording->playback) and isset($recording->playback->format)) {
                        $images = [];
                        if (isset($recording->playback->format->preview->images->image)) {
                            foreach ($recording->playback->format->preview->images->image as $img) {
                                // $images[] = $img->0;
                                if (is_string($img)) {
                                    $images[] = $img;
                                } elseif (is_object($img) and isset(get_object_vars($img)[0])) {
                                    $images[] = get_object_vars($img)[0];
                                }
                            }
                        }
                        $datas[] = [
                            'id'      => $recording->meetingID,
                            'url'     => $recording->playback->format->url,
                            'preview' => $images,
                        ];
                    }
                }
            }
        }

        // dd($datas);

        return collect($datas);
    }

    public static function addHttps($url)
    {
        if (! preg_match('~^(?:f|ht)tps?://~i', $url)) {
            $url = 'https://'.$url;
        }

        return $url.'/bigbluebutton/api/';
    }

    public static function getVendorData($vendorId)
    {
        $serverId = DB::connection('partner_mysql')->table('live_server_vendor')->where('vendor_id', $vendorId)->pluck('live_server_id');
        $server = DB::connection('partner_mysql')->table('live_servers')->whereIn('id', $serverId)->first();
        if ($server) {
            config(['app.liveclass.live_class_base_url' => $server->base_url]);
            // $server->base_url = self::addHttps($server->base_url);
            $server->api = self::addHttps($server->base_url);
            config(['app.liveclass.api'    => self::addHttps($server->base_url)]);
            config(['app.liveclass.secret' => $server->secret]);

            return $server;
        } else {
            $server = new \stdClass();
            $server->api = config('app.liveclass.api');
            $server->base_url = config('app.liveclass.live_class_base_url');
            $server->secret = config('app.liveclass.secret');

            return $server;
        }
    }

    public static function updateAmount($collection)
    {
        try {
            if ($collection->vendor_id) {
                $collection->load('product');
                $productPrice = null;
                if ($collection->product) {
                    $productPrice = ProductPrice::where('product_id', $collection->product->id)->first();
                    if (! $productPrice) {
                        return false;
                    }
                }

                $subscription_included = false;
                $amountData = TaxFeeHelper::getTaxCalculationData($collection->vendor_id, $productPrice->price, $collection->collection_type, null, $subscription_included);

                // $sa = new SubscriptionAdapter($collection->vendor_id);
                // $amountData = $sa->process($productPrice->price, $collection->collection_type, null, false);
                $collection->update(['published_price' => $amountData['amount']]);
            }
        } catch (\Exception $th) {
            //throw $th;
        }
    }

    public static function checkCollectionIsBought($isPaginate, $collections)
    {
        $datas = [];
        $userIds = UserHelper::getStudentsIds();
        $classesIds = [];
        foreach ($collections as $collection) {
            $classesIds[] = $collection->vendor_class_id;
        }

        if (! count($userIds)) {
            return $collections;
        }

        $studentIds = Student::whereIn('user_id', $userIds)->pluck('id');

        if (! count($studentIds)) {
            return $collections;
        }

        $collectionIds = Order::whereIn('purchaser_id', $userIds)->whereHas('withoutTrashCollection', function ($q) {
            $q->whereIn('collection_type', [CollectionType::classDeck, CollectionType::classes]);
        })->where('payment_status', PaymentStatus::Received)->pluck('collection_id');

        if (! count($collectionIds)) {
            return $collections;
        }

        $boughtCollections = Collection::whereIn('id', $collectionIds)->whereIn('vendor_class_id', $classesIds)->get('vendor_class_id');
        $registeredKidClasses = StudentRegistration::whereIn('student_id', $studentIds)->whereIn('vendorclass_id', $classesIds)->get('vendorclass_id');
        $vendorClassIds = [];
        foreach ($classesIds as $classId) {
            $data = [
                'class_id'   => $classId,
                'is_bought'  => false,
                'allow_other'=> false,
            ];

            $registeredKid = $registeredKidClasses->where('vendorclass_id', $classId)->first();
            $registeredKidCount = $registeredKidClasses->where('vendorclass_id', $classId)->count();
            $boughtCollection = $boughtCollections->where('vendorclass_id', $classId)->first();
            if ($registeredKid or $boughtCollection) {
                $vendorClassIds[] = $classId;
                $data['is_bought'] = true;
                if (count($studentIds) > $registeredKidCount) {
                    $data['allow_other'] = true;
                }
            }
            $datas[] = $data;
        }

        $sessionDatas = [];
        if (count($vendorClassIds)) {
            $sessionDatas = self::addLiveClassDetails($vendorClassIds);
        }

        if ($isPaginate) {
            $collections->getCollection()->transform(function ($data) use ($datas,  $sessionDatas) {
                $dat = collect($datas)->where('class_id', $data->vendor_class_id)->first();
                $data['bought_data'] = $dat;
                if (count($sessionDatas) and $dat and $dat['is_bought']) {
                    $session = collect($sessionDatas)->where('class_id', $data->vendor_class_id)->first();
                    if ($session) {
                        $data['session_data'] = $session;
                    }
                }

                return $data;
            });
        } else {
            $collections->map(function ($data) use ($datas, $sessionDatas) {
                $dat = collect($datas)->where('class_id', $data->vendor_class_id)->first();

                $data['bought_data'] = $dat;

                if (count($sessionDatas) and $dat and $dat['is_bought']) {
                    $session = collect($sessionDatas)->where('class_id', $data->vendor_class_id)->first();
                    if ($session) {
                        $data['session_data'] = $session;
                    }
                }

                return $data;
            });
        }

        return $collections;
    }

    private static function addLiveClassDetails($classesIds)
    {
        $liveClasses = PartnerLiveClass::whereIn('vendor_class_id', $classesIds)->get();
        $sessionLatests = PartnerLiveClassSchedule::whereIn('class_id', $classesIds)
            ->whereDate('start_date_time', '>=', now()->toDateString())
            ->whereDate('start_date_time', now()->format('Y-m-d'))
            ->orderBy('start_date_time', 'ASC')
            ->get();
        $datas = [];

        if (count($sessionLatests)) {
            foreach ($sessionLatests as  $sessionLatest) {
                $liveClass = $liveClasses->where('id', $sessionLatest->live_class_id)->first();
                $data = [
                    'meeting_id'  => '',
                    'session_id'  => 0,
                    'internal_id' => '',
                    'class_id'    => $sessionLatest->class_id,
                ];

                $isNotExpired = false;
                if ($liveClass) {
                    $endTime = $sessionLatest->start_date_time->addMinutes($liveClass->duration);
                    if ($endTime->gt(now()) and $endTime->isToday()) {
                        $isNotExpired = true;
                    }

                    if ($sessionLatest->status == LiveClassStatus::Running and $isNotExpired) {
                        $data['meeting_id'] = $sessionLatest->meeting_id;
                        $data['session_id'] = $sessionLatest->id;
                        $data['internal_id'] = $sessionLatest->internal_id;
                    }
                }

                $datas[] = $data;
            }
        }

        return collect($datas);
    }
}
