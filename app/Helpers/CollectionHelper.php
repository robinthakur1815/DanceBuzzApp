<?php

namespace App\Helpers;

use App\Collection;
use App\Coupon;
use App\Discount as AppDiscount;
use App\Enums\ClassPublishStatus;
use App\Enums\CollectionType;
use App\Enums\DiscountType;
use App\Enums\RoleType;
use App\Model\Partner\Discount;
use App\Model\Partner\Fee;
use App\Model\Partner\Location;
use App\Model\Partner\PartnerClass;
use App\Product;
use App\ProductPrice;
use App\User;
use App\Vendor;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\DB as FacadesDB;

final class CollectionHelper
{
    public static function checkPublishedStatus($id)
    {
        try {
            $status = false;
            $activities = DB::connection('partner_mysql')->table('activity_log')->where('subject_id', $id)->where('subject_type', config('app.class_model_type'))->get();
            foreach ($activities as $activity) {
                $statusChanged = json_decode($activity->properties);

                if (isset($statusChanged->attributes) and isset($statusChanged->attributes->publish_status) and $statusChanged->attributes->publish_status) {
                    if ($statusChanged->attributes->publish_status == ClassPublishStatus::Published) {
                        $status = true;
                    }
                }
            }

            return $status;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public static function VendorData($user = null)
    {
        $data = [
            'vendor_id'   => 0,
            'user_id'     => 0,
            'location_id' => 0,
            'service_id'  => 0,
        ];

        if (! $user) {
            $user = User::where('role_id', RoleType::User)->withTrashed()->first();
        }

        if ($user) {
            $vendor = DB::connection('partner_mysql')->table('vendors')->where('created_by', $user->id)->first();
            $serviceData = DB::connection('partner_mysql')->table('vendor_service')->where('vendor_id', $vendor ? $vendor->id : '')->first();
            $location = Location::where('vendor_id', $vendor ? $vendor->id : '')->first();
            $data = [
                'vendor_id'   => $vendor ? $vendor->id : 0,
                'user_id'     => $user ? $user->id : 0,
                'location_id' => $location ? $location->id : 0,
                'service_id'  => $serviceData ? $serviceData->service_id : 0,
            ];
        }

        return $data;
    }

    public static function createClass()
    {
        $userData = self::VendorData();
        $collections = Collection::whereIn('collection_type', [CollectionType::classes, CollectionType::classDeck])
                                        ->with('product.prices')
                                        ->whereNull('vendor_class_id')->get();

        foreach ($collections as  $collection) {
            $is_free = true;
            if ($collection->product and $collection->product->prices and is_array($collection->product->prices) and count($collection->product->prices)) {
                $is_free = false;
            }
            $startDate = now()->toDateTimeString();
            if ($collection->saved_content) {
                $saved_content = json_decode($collection->saved_content);
                if ($saved_content and $saved_content->start_date) {
                    $startDate = Carbon::createFromFormat('Y/m/d', $saved_content->start_date);
                }
            }
            $data = [
                'name'                 => $collection->title,
                'start_date'           => $startDate,
                'vendor_id'            => $userData['vendor_id'],
                'owner_id'             => $userData['user_id'],
                'location_id'          => $userData['location_id'],
                'service_id'           => $userData['service_id'],
                'created_by'           => $userData['user_id'],
                'is_live'              => false,
                'is_publish'           => true,
                'is_free'              => $is_free,
                'publish_status'       => $collection->published_content ? ClassPublishStatus::Published : ClassPublishStatus::Draft,
            ];

            $class = PartnerClass::create($data);
            $collectionData = [
                'vendor_id' => $userData['vendor_id'],
                'vendor_class_id' => $class->id,
            ];

            $collection->update($collectionData);
        }
    }

    public static function createUpdatePackage($request, $user, $productPrice = null)
    {
        $ownerId = null;
        $vendorId = null;
        if ($request->vendor) {
            $vendorId = $request->vendor->id;
            $vendor = Vendor::find($request->vendor->id);
            $vendorId = $vendor->id;
            $ownerId = $vendor->created_by;
        } else {
            $VendorData = self::VendorData();
            $vendorId = $VendorData['vendor_id'];
            $ownerId = $VendorData['user_id'];
        }
        $package = null;
        if ($productPrice) {
            $package = Fee::where('id', $productPrice->vendor_package_id)->first();
        }
        $data = [
            'name'                 => $request->name,
            'description'          => $request->desc,
            'amount'               => $request->price,
            'validity'             => $request->validity,
            'split_no'             => $package ? $package->split_no : 1,
            'updated_by'           => $user->id,
            'created_by'           => $package ? $package->created_by : $user->id,
            'vendor_id'            => $package ? $package->vendor_id : $vendorId,
            'owner_id'             => $package ? $package->owner_id : $ownerId,
            'is_publish'           => true,
        ];

        if ($package) {
            $package->update($data);
        } else {
            $package = Fee::create($data);
        }

        return $package;
    }

    public static function createUpdateDiscount($request, $user, $cmsDiscount = null)
    {
        $ownerId = null;
        $vendorId = null;

        if ($request->vendor) {
            $vendor = Vendor::find($request->vendor->id);
            $vendorId = $vendor->id;
            $ownerId = $vendor->created_by;
        } else {
            $VendorData = self::VendorData();
            $vendorId = $VendorData['vendor_id'];
            $ownerId = $VendorData['user_id'];
        }

        $discount = null;
        if ($cmsDiscount) {
            $discount = Discount::where('id', $cmsDiscount->vendor_discount_id)->first();
        }

        $startDate = $discount ? $discount->start_date : null;
        $endDate = $discount ? $discount->end_date : null;
        if ($request->start_date) {
            $startDate = Carbon::parse($request->start_date);
        }
        if ($request->end_date) {
            $endDate = Carbon::parse($request->end_date);
        }
        $data = [
            'name'                 => $request->name,
            'description'          => $request->description ? $request->description : '',
            'code'                 => $request->code,
            'value'                => $request->amount,
            'vendor_id'            => $discount ? $discount->vendor_id : $vendorId,
            'owner_id'             => $discount ? $discount->owner_id : $ownerId,
            'isPercentage'         => $request->is_percentage ? $request->is_percentage : false,
            'type'                 => DiscountType::Offer,
            'updated_by'           => $user->id,
            'created_by'           => $discount ? $discount->created_by : $user->id,
            'start_date'           => $startDate,
            'end_date'             => $endDate,
            'is_publish'           => true,
        ];

        if ($discount) {
            $discount->update($data);
        } else {
            $discount = Discount::create($data);
        }

        return $discount;
    }

    public static function createUpdateCoupon($request, $user, $cmsCoupon = null)
    {
        $ownerId = null;
        $vendorId = null;
        if ($request->vendor) {
            $vendor = Vendor::find($request->vendor->id);
            $vendorId = $vendor->id;
            $ownerId = $vendor->created_by;
        } else {
            $VendorData = self::VendorData();
            $vendorId = $VendorData['vendor_id'];
            $ownerId = $VendorData['user_id'];
        }
        $coupon = null;
        if ($cmsCoupon) {
            $coupon = Discount::where('id', $cmsCoupon->vendor_coupon_id)->first();
        }

        $startDate = $coupon ? $coupon->start_date : null;
        $endDate = $coupon ? $coupon->end_date : null;
        if ($request->start_date) {
            $startDate = Carbon::parse($request->start_date);
        }
        if ($request->end_date) {
            $endDate = Carbon::parse($request->end_date);
        }
        $data = [
            'name'                 => $request->name,
            'description'          => $request->description ? $request->description : '',
            'code'                 => $request->code,
            'value'                => $request->amount,
            'vendor_id'            => $coupon ? $coupon->vendor_id : $vendorId,
            'owner_id'             => $coupon ? $coupon->owner_id : $ownerId,
            'isPercentage'         => $request->is_percentage ? $request->is_percentage : false,
            'type'                 => DiscountType::Discount,
            'updated_by'           => $user->id,
            'created_by'           => $coupon ? $coupon->created_by : $user->id,
            'start_date'           => $startDate,
            'end_date'             => $endDate,
            'is_publish'           => true,
        ];

        if ($coupon) {
            $coupon->update($data);
        } else {
            $coupon = Discount::create($data);
        }

        return $coupon;
    }

    public static function createActivityLog($vendor_class_id, $actionType, $authId)
    {
        try {
            $properties = [
                'attributes' =>['is_publish' => 1, 'publish_status' => 1],
            ];

            $datas = [
                'log_name' => 'manual',
                'description' => $actionType,
                'subject_id' => $vendor_class_id,
                'subject_type' => config('app.class_model_type'),
                'causer_id' => $authId,
                'causer_type' => config('app.user_model_type'),
                'properties' => json_encode($properties),
                'created_at' => now(),
                'updated_at' => now(),

            ];

            DB::connection('partner_mysql')
                ->table('activity_log')->insert($datas);
        } catch (\Exception $th) {
            report($th);
        }
    }

    public static function updatePackageVendor()
    {
        $products = Product::whereNotNull('vendor_package_id')->get();
        foreach ($products as $product) {
            $collection = Collection::where('id', $product->collection_id)->first();
            if ($collection) {
                $product->update(['vendor_id' => $collection->vendor_id]);
            }
        }
    }

    public static function updatePackagePriceVendor()
    {
        $products = ProductPrice::whereNotNull('vendor_package_id')->get();
        foreach ($products as $product) {
            $collection = Fee::where('id', $product->vendor_package_id)->first();
            if ($collection) {
                $product->update(['vendor_id' => $collection->vendor_id]);
            }
        }
    }

    public static function updateDiscountVendor()
    {
        $discounts = AppDiscount::whereNotNull('vendor_discount_id')->get();
        foreach ($discounts as $discount) {
            $collection = Fee::where('id', $discount->vendor_discount_id)->first();
            if ($collection) {
                $discount->update(['vendor_id' => $collection->vendor_id]);
            }
        }
    }

    public static function updateCouponVendor()
    {
        $coupons = Coupon::whereNotNull('vendor_coupon_id')->get();
        foreach ($coupons as $coupon) {
            $collection = Fee::where('id', $coupon->vendor_coupon_id)->first();
            if ($collection) {
                $coupon->update(['vendor_id' => $collection->vendor_id]);
            }
        }
    }

    public static function updatePackagePriceVendorPivot()
    {
        $productPrices = ProductPrice::whereNotNull('vendor_package_id')->whereNotNull('vendor_id')->get();
        foreach ($productPrices as $price) {
            $data = [
                'product_id'       => $price->product_id,
                'product_price_id' => $price->id,
                'is_active'        => true,
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
            FacadesDB::table('product_prices_product')->insert($data);
        }
    }

    public static function updateVendorPackage()
    {
        $productPrices = ProductPrice::whereNotNull('vendor_package_id')->whereNotNull('vendor_id')->get();
        foreach ($productPrices as $price) {
            $collection = Fee::where('id', $price->vendor_package_id)->first();
            if ($collection) {
                $collection->update(['is_publish' => 1]);
            }
        }
    }

    public static function createProductOfCollection()
    {
        $collections = Collection::doesntHave('product')->get();

        foreach ($collections as $collection) {
            $productData = [
                'name'              => $collection->title,
                'slug'              => $collection->slug,
                'description'       => $collection->description,
                'stock'             => 1,
                'status'            => $collection->status,
                'created_by'        => $collection->created_by,
                'updated_by'        => $collection->updated_by,
                'vendor_id'         => $collection->vendor_id,
                'collection_id'     => $collection->id,
            ];

            Product::create($productData);
        }
    }

    public static function attachPackageApiCall($packageId, $classId, $type)
    {
        $url = config('app.backend_api.base_url')."/api/vendor/package/class/$classId/$type";
        $data = [
            'packages' => [$packageId],
            'is_cms'   => true,
        ];

        return self::attachVendorApiCall($url, $data);
    }

    public static function attachDiscountApiCall($packageId, $discountId, $type)
    {
        $url = config('app.backend_api.base_url')."/api/vendor/offer/class/package/$packageId/$type";
        $data = [
            'offer' => $discountId,
            'is_cms'   => true,
        ];

        return self::attachVendorApiCall($url, $data);
    }

    public static function updateClassApiCall($classId, $request)
    {
        $url = config('app.backend_api.base_url')."/api/vendor/update/class/$classId";
        $data = $request->all();
        $startDate = Carbon::createFromFormat('Y/m/d', $request->start_date);
        $startDate = $startDate->format('d/m/Y');
        $endDate = '';
        $startTime = '';
        $endTime = '';
        if ($request->end_date) {
            $endDate = Carbon::createFromFormat('Y/m/d', $request->end_date);
            $endDate = $endDate->format('d/m/Y');
        }

        if ($request->start_time) {
            $startTime = Carbon::createFromFormat('h:i:s', $request->start_time);
            $startTime = $startTime->format('h:i A');
        }

        if ($request->end_time) {
            $endTime = Carbon::createFromFormat('h:i:s', $request->end_time);
            $endTime = $endTime->format('h:i A');
        }

        $data = [
            'name'                  => $request->title,
            'start_date'            => $startDate,
            'end_date'              => $endDate,
            'startTime'             => $startTime,
            'endTime'               => $endTime,
            'frequencey_per_month'  => $request->frequencey_per_month,
            'vendor_id'             => $request->vendor_id,
            'location_id'           => $request->location_id,
            'service_id'            => $request->service_id,
            'is_free'               => $request->is_free,
            'description'           => $request->description,
        ];

        return self::attachVendorApiCall($url, $data);
    }

    public static function attachVendorApiCall($url, $data)
    {
        try {
            $headers = apache_request_headers();
            $token = '';
            if (isset($headers['Authorization'])) {
                $token = $headers['Authorization'];
            }

            $client = new \GuzzleHttp\Client();
            $response = $client->request('POST', $url, [
                'debug' => false,
                'verify' => false,
                'http_errors' => false,
                'headers' => [
                    // 'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => $token,
                ],
                'form_params' => $data,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            if ($response->getStatusCode() == 200) {
                return ['status' => true, 'response' => $responseData,  'code' => 200];
            } else {
                return ['status' => false, 'response' =>  $responseData, 'code' => 422];

                return false;
            }
        } catch (\Exception $e) {
            report($e);

            return ['status' => false, 'response' =>  'server error', 'code' => 500];

            return false;
            report($e);
        }
    }

    public static function getActivePackage($collection)
    {
        $productPrice = null;
        $collection->load('product.activePackages.discounts');
        if ($collection->product and $collection->product->activePackages and count($collection->product->activePackages)) {
            $productPrice = $collection->product->activePackages->first();
        }

        return $productPrice;
    }

    public static function attachDetachPrice($product, $productPrice, $type)
    {
        $attachedPrice = FacadesDB::table('product_prices_product')
                                    ->where('product_id', $product->id)
                                    ->where('product_price_id', $productPrice->id)
                                    ->first();

        if (! $attachedPrice) {
            $data = [
                'product_id'       => $product->id,
                'product_price_id' => $productPrice->id,
                'is_active'        => true,
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
            if ($type != 'attach') {
                $data['is_active'] = false;
            }
            FacadesDB::table('product_prices_product')->insert($data);
        } else {
            $data = [
                'is_active' => false,
                'updated_at' => now(),
            ];

            if ($type == 'attach') {
                $data['is_active'] = true;
            }

            FacadesDB::table('product_prices_product')
                                    ->where('product_price_id', $productPrice->id)
                                    ->where('product_id', $product->id)
                                    ->update($data);
        }
    }


    public static function addWebUrl($collection)
    {
        $url = "";
        $slug = $collection->slug;
        $collection_type = $collection->collection_type;

        if ($collection_type == CollectionType::classes) {
            $url = '/class/'. $slug ;
        }

        if ($collection_type == CollectionType::classDeck) {
            $url = '/live-class/'. $slug ;
        }

        if ($collection_type == CollectionType::events) {
            $url = '/event/'. $slug ;
        }

        if ($collection_type == CollectionType::workshops) {
            $url = '/workshop/'. $slug ;
        }
        $baseUrl = config('app.client_url');
        return  $baseUrl.$url;
    }
}
