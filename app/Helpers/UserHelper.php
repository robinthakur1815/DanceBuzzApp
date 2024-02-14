<?php

namespace  App\Helpers;

use App\Category;
use App\Collection as AppCollection;
use App\Enums\CollectionType;
use App\Enums\PaymentStatus;
use App\Enums\RoleType;
use App\Model\Partner\PartnerMedia;
use App\Model\Partner\PartnerUser;
use App\Order;
use App\ProductPrice;
use App\Tag;
use App\User;
use App\Vendor;
use Illuminate\Container\Container;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

use Illuminate\Support\Facades\DB;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Facade;


class UserHelper extends Facade
{
    public static function validateUser($userId, $authToken)
    {
        $httpClient = new Client(['base_uri' => config('app.backend_api.base_url'),  'verify' => false]);

        try {
            $client = new \GuzzleHttp\Client();
            $url = config('app.backend_api.base_url').'/api/user';
            $response = $client->request('GET', $url, [
                'debug' => false,
                'verify' => false,
                'http_errors' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer '.$authToken,
                ],
            ]);

            if ($response->getStatusCode() == 200) {
                $response = $response->getBody()->getContents();

                return json_decode($response, true);
            } else {
                return false;
            }
        } catch (Exception $e) {
            Log::error($e);
        }
    }

    public static function createUserProfile($userId)
    {
        $data = [
            'user_id'        => $userId,
            'reference_code' => \Str::random(9).''.$userId,
        ];
        $profile = UserProfile::create($data);

        return $profile;
    }

    public static function updateEnthuPoints($purchaserId, $attendeeNo, $authToken)
    {
        $httpClient = new Client(['base_uri' => config('app.backend_api.base_url'),  'verify' => false]);
        try {
            $client = new \GuzzleHttp\Client();
            $url = config('app.backend_api.base_url').'/api/update/enthu/points';

            $response = $client->request('POST', $url, [
                'debug' => false,
                'verify' => false,
                'http_errors' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer '.$authToken,
                ],
                'query' => [
                    'userId' => $purchaserId,
                    'attendeeNo' => $attendeeNo,
                ],
            ]);

            if ($response->getStatusCode() == 200) {
                $response = $response->getBody()->getContents();
            }

            return $response;
            // else{
            //     return false;
            // }
        } catch (Exception $e) {
            report($e);
            return false;
        }
    }

    public function checkPaymentStatus($orderId)
    {
        // return $orderId;
        $curl = curl_init();
        $appId = config('app.cash_free.app_id');
        $secretKey = config('app.cash_free.secret_key');
        curl_setopt_array($curl, [
            CURLOPT_URL => config('app.cash_free.CURLOPT_URL'),
            CURLOPT_RETURNTRANSFER => config('app.cash_free.CURLOPT_RETURNTRANSFER'),
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => config('app.cash_free.CURLOPT_MAXREDIRS'),
            CURLOPT_TIMEOUT => config('app.cash_free.CURLOPT_TIMEOUT'),
            CURLOPT_HTTP_VERSION => config('app.cash_free.CURLOPT_HTTP_VERSION'),
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => sprintf(config('app.cash_free.CURLOPT_POSTFIELDS'), $appId, $secretKey, $orderId),
            CURLOPT_HTTPHEADER => [
                'cache-control: no-cache',
                'content-type: application/x-www-form-urlencoded',
            ],
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $jsonResponse = ['status' => false, 'message' => 'Server error', 'data' => 'cURL Error #:'.$err];
        if ($err) {
            \Log::error('cURL Error #:'.$err);
        } else {
            $order = Order::find($orderId);
            $result = json_decode($response, true);
            if ($order) {
                if ($result['status'] == 'ERROR') {
                    $jsonResponse['message'] = $result['reason'];
                } else {
                    $result = json_decode($response, true);
                    if ($order->meta) {
                        $atendeeNo = count(json_decode($order->meta)->attendees);
                    } else {
                        $atendeeNo = 1;
                    }
                    if ($order->payment_status == PaymentStatus::Pending) {
                        $this->checkPaymentAndUpdateStatus($order->purchaser_id, $atendeeNo);
                    }
                    $status = PaymentStatus::Received;

                    if (! isset($result['txStatus'])) {
                        $status = PaymentStatus::Cancel;
                    }

                    if (isset($result['txStatus']) and $result['txStatus'] == 'FAILED') {
                        $status = PaymentStatus::Failed;
                    }
                    $order->transaction_id = isset($result['referenceId']) ? $result['referenceId'] : null;
                    $order->payment_mode = isset($result['paymentMode']) ? $result['paymentMode'] : null;
                    $order->transaction_date = isset($result['txTime']) ? $result['txTime'] : null;
                    $order->transaction_data = $response;
                    $order->payment_status = $status;

                    $order->save();
                    $jsonOrderMeta = json_decode($order->meta, true);
                    $result['attendees'] = $jsonOrderMeta['attendees'];
                    $jsonResponse['message'] = 'Successfully found result';
                    $jsonResponse['status'] = true;
                }
                $jsonResponse['status'] = true;
            }
            $jsonResponse['data'] = $result;
        }
    }

    public function checkPaymentAndUpdateStatus($purchaserId, $attendeeNo)
    {
        $httpClient = new Client(['base_uri' => config('app.backend_api.base_url'),  'verify' => false]);
        try {
            $client = new \GuzzleHttp\Client();
            $url = config('app.backend_api.base_url').'/api/schedule/update/enthu/points';

            $response = $client->request('POST', $url, [
                'debug' => false,
                'verify' => false,
                'http_errors' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'query' => [
                    'userId' => $purchaserId,
                    'attendeeNo' => $attendeeNo,
                ],
            ]);

            if ($response->getStatusCode() == 200) {
                $response = $response->getBody()->getContents();
            }

            // else{
            //     return false;
            // }
        } catch (Exception $e) {
            Log::error($e);
        }
    }

    public function collectionCategories()
    {
        $categories = Category::all();
        $collections = AppCollection::select('id', 'collection_type', 'categories')->get();
        foreach ($collections as $col) {
            $cats = json_decode($col->categories);
            if ($cats) {
                foreach ($cats as $cat) {
                    $exist = $categories->where('id', $cat)->first();
                    if ($exist) {
                        $col->categoryPivot()->create(['category_id' => $cat, 'collection_type' => $col->collection_type]);
                    }
                }
            }
        }
    }

    public function collectionTagsPivot()
    {
        $allTags = Tag::all();
        $collections = AppCollection::select('id', 'collection_type', 'tags')->get();
        foreach ($collections as $col) {
            $tags = json_decode($col->tags);
            if ($tags) {
                foreach ($tags as $tag) {
                    $exist = $allTags->where('id', $tag)->first();
                    if ($exist) {
                        $col->tagPivot()->create(['tag_id' => $tag, 'collection_type' => $col->collection_type]);
                    }
                }
            }
        }
    }

    //finding users from partner project
    public static function users($userIds)
    {
        $users = User::whereIn('id', $userIds)->get();
        $avatars = PartnerMedia::whereIn('model_id', $userIds)->where('model_type', config('app.user_model_type'))->get();
        $users = $users->map(function ($user) use ($avatars) {
            $avatar = $avatars->where('model_id', $user->id)->first();
            $url = null;
            if ($avatar) {
                $url = config('app.s3url').'/'.$avatar->url;
            }
            $user['avatar'] = $url;

            return $user;
        });

        return collect($users);
    }

    public static function migrateUser()
    {
        $users = DB::table('users')->whereIn('id', [
            12,
        13,
          ])->get();
        $ids = [];
        foreach ($users as $user) {
            $name = $user->name;

            $data = [
                'name'              => $name,
                // "username"          => $user->username,
                'email'             => $user->email ?? null,
                'phone'             => $user->phone ?? null,
                'password'          => $user->password,
                'email_verified_at' => $user->email_verified_at,
                'role_id'           => $user->role_id,
                'is_active'         => $user->is_active,
                'created_at'        => $user->created_at,
                'updated_at'        => $user->updated_at,
                'app_type'          => 1, // cms app id
            ];

            try {
                DB::transaction(function () use ($data, $user) {
                    $newuser = User::create($data);
                    DB::table('users')->where('id', $user->id)
                                        ->update([
                                            'new_user_id' => $newuser->id,
                                        ]);
                });
            } catch (\Exception $th) {
                $ids[] = $user->id;
                info($th);
            }
        }

        return $ids;
    }

    public static function migrateDatas()
    {

         // 1

        // \App\CollectionVersion::withTrashed()->chunk(50, function ($versions) {
        //     foreach($versions as $version) {
        //         if ($version->created_by) {
        //             $user =  DB::table('users')->where('id', $version->created_by)->first();
        //             $version->update(["created_by" => $user->new_user_id]);
        //         }

        //         if ($version->updated_by) {
        //             $user =  DB::table('users')->where('id', $version->updated_by)->first();
        //             $version->update(["updated_by" => $user->new_user_id]);
        //         }
        //     }
        // });

        // 2
        // AppCollection::withTrashed()->chunk(50, function ($collections) {
        //     foreach($collections as $collection) {
        //         if ($collection->created_by) {
        //             $user =  DB::table('users')->where('id', $collection->created_by)->first();
        //             $collection->update(["created_by" => $user->new_user_id]);
        //         }

        //         if ($collection->updated_by) {
        //             $user =  DB::table('users')->where('id', $collection->updated_by)->first();
        //             $collection->update(["updated_by" => $user->new_user_id]);
        //         }

        //        if ($collection->published_by) {
        //             $user =  DB::table('users')->where('id', $collection->published_by)->first();
        //             $collection->update(["published_by" => $user->new_user_id]);
        //         }
        //     }
        // });

        // // 3
        // \App\Media::chunk(50, function ($mediadatas) {
        //     foreach($mediadatas as $media) {
        //         if ($media->created_by) {
        //             $user =  DB::table('users')->where('id', $media->created_by)->first();
        //             $media->update(["created_by" => $user->new_user_id]);
        //         }

        //         if ($media->updated_by) {
        //             $user =  DB::table('users')->where('id', $media->updated_by)->first();
        //             $media->update(["updated_by" => $user->new_user_id]);
        //         }
        //     }
        // });

        // // 4
        \App\Mediables::chunk(50, function ($Mediables) {
            foreach ($Mediables as $Mediable) {
                if ($Mediable->created_by) {
                    $user = DB::table('users')->where('id', $Mediable->created_by)->first();
                    if ($user) {
                        $Mediable->update(['created_by' => $user->new_user_id]);
                    }
                }

                if ($Mediable->updated_by) {
                    $user = DB::table('users')->where('id', $Mediable->updated_by)->first();
                    if ($user) {
                        $Mediable->update(['updated_by' => $user->new_user_id]);
                    }
                }
            }
        });

        $categories = Category::withTrashed()->get();
        foreach ($categories as $category) {
            if ($category->created_by) {
                $user = DB::table('users')->where('id', $category->created_by)->first();
                $category->update(['created_by' => $user->new_user_id]);
            }

            if ($category->updated_by) {
                $user = DB::table('users')->where('id', $category->updated_by)->first();
                $category->update(['updated_by' => $user->new_user_id]);
            }
        }

        $tags = Tag::get();
        foreach ($tags as $tag) {
            if ($tag->created_by) {
                $user = DB::table('users')->where('id', $tag->created_by)->first();
                $tag->update(['created_by' => $user->new_user_id]);
            }

            if ($tag->updated_by) {
                $user = DB::table('users')->where('id', $tag->updated_by)->first();
                $tag->update(['updated_by' => $user->new_user_id]);
            }
        }

        \App\Seo::withTrashed()->chunk(50, function ($seos) {
            foreach ($seos as $seo) {
                if ($seo->created_by) {
                    $user = DB::table('users')->where('id', $seo->created_by)->first();
                    $seo->update(['created_by' => $user->new_user_id]);
                }

                if ($seo->updated_by) {
                    $user = DB::table('users')->where('id', $seo->updated_by)->first();
                    $seo->update(['updated_by' => $user->new_user_id]);
                }
            }
        });

        $files = \App\File::get();
        foreach ($files as $file) {
            if ($file->created_by) {
                $user = DB::table('users')->where('id', $file->created_by)->first();
                $file->update(['created_by' => $user->new_user_id]);
            }

            if ($file->updated_by) {
                $user = DB::table('users')->where('id', $file->updated_by)->first();
                $file->update(['updated_by' => $user->new_user_id]);
            }
        }

        $Fileables = \App\Fileable::get();
        foreach ($Fileables as $Fileable) {
            if ($Fileable->created_by) {
                $user = DB::table('users')->where('id', $Fileable->created_by)->first();
                $Fileable->update(['created_by' => $user->new_user_id]);
            }

            if ($Fileable->updated_by) {
                $user = DB::table('users')->where('id', $Fileable->updated_by)->first();
                $Fileable->update(['updated_by' => $user->new_user_id]);
            }
        }

        $WebPages = \App\WebPage::withTrashed()->get();
        foreach ($WebPages as $WebPage) {
            if ($WebPage->created_by) {
                $user = DB::table('users')->where('id', $WebPage->created_by)->first();
                $WebPage->update(['created_by' => $user->new_user_id]);
            }

            if ($WebPage->updated_by) {
                $user = DB::table('users')->where('id', $WebPage->updated_by)->first();
                $WebPage->update(['updated_by' => $user->new_user_id]);
            }
        }

        $WebSections = \App\WebSection::withTrashed()->get();
        foreach ($WebSections as $WebSection) {
            if ($WebSection->created_by) {
                $user = DB::table('users')->where('id', $WebSection->created_by)->first();
                $WebSection->update(['created_by' => $user->new_user_id]);
            }

            if ($WebSection->updated_by) {
                $user = DB::table('users')->where('id', $WebSection->updated_by)->first();
                $WebSection->update(['updated_by' => $user->new_user_id]);
            }
        }

        $tag_groups = \App\TagGroup::get();
        foreach ($tag_groups as $taggroup) {
            if ($taggroup->created_by) {
                $user = DB::table('users')->where('id', $taggroup->created_by)->first();
                $taggroup->update(['created_by' => $user->new_user_id]);
            }

            if ($taggroup->updated_by) {
                $user = DB::table('users')->where('id', $taggroup->updated_by)->first();
                $taggroup->update(['updated_by' => $user->new_user_id]);
            }
        }

        $products = \App\Product::withTrashed()->get();
        foreach ($products as $product) {
            if ($product->created_by) {
                $user = DB::table('users')->where('id', $product->created_by)->first();
                $product->update(['created_by' => $user->new_user_id]);
            }

            if ($product->updated_by) {
                $user = DB::table('users')->where('id', $product->updated_by)->first();
                $product->update(['updated_by' => $user->new_user_id]);
            }
        }

        $discounts = \App\Discount::withTrashed()->get();
        foreach ($discounts as $discount) {
            if ($discount->created_by) {
                $user = DB::table('users')->where('id', $discount->created_by)->first();
                $discount->update(['created_by' => $user->new_user_id]);
            }

            if ($discount->updated_by) {
                $user = DB::table('users')->where('id', $discount->updated_by)->first();
                $discount->update(['updated_by' => $user->new_user_id]);
            }
        }

        $product_prices = \App\ProductPrice::withTrashed()->get();
        foreach ($product_prices as $product_price) {
            if ($product_price->created_by) {
                $user = DB::table('users')->where('id', $product_price->created_by)->first();
                $product_price->update(['created_by' => $user->new_user_id]);
            }

            if ($product_price->updated_by) {
                $user = DB::table('users')->where('id', $product_price->updated_by)->first();
                $product_price->update(['updated_by' => $user->new_user_id]);
            }
        }

        $category_groups = \App\CategoryGroup::get();
        foreach ($category_groups as $category_group) {
            if ($category_group->created_by) {
                $user = DB::table('users')->where('id', $category_group->created_by)->first();
                if ($user) {
                    $category_group->update(['created_by' => $user->new_user_id]);
                }
            }

            if ($category_group->updated_by) {
                $user = DB::table('users')->where('id', $category_group->updated_by)->first();
                if ($user) {
                    $category_group->update(['updated_by' => $user->new_user_id]);
                }
            }
        }

        $coupons = \App\Coupon::withTrashed()->get();
        foreach ($coupons as $coupon) {
            if ($coupon->created_by) {
                $user = DB::table('users')->where('id', $coupon->created_by)->first();
                if ($user) {
                    $coupon->update(['created_by' => $user->new_user_id]);
                }
            }

            if ($coupon->updated_by) {
                $user = DB::table('users')->where('id', $coupon->updated_by)->first();
                if ($user) {
                    $coupon->update(['updated_by' => $user->new_user_id]);
                }
            }
        }

        $product_reviews = \App\ProductReviews::withTrashed()->get();
        foreach ($product_reviews as $product_review) {
            if ($product_review->approved_by) {
                $user = DB::table('users')->where('id', $product_review->approved_by)->first();
                if ($user) {
                    $product_review->update(['approved_by' => $user->new_user_id]);
                }
            }
        }

        $feeds = \App\Feed::get();
        foreach ($feeds as $feed) {
            if ($feed->created_by) {
                $user = DB::table('users')->where('id', $feed->created_by)->first();
                if ($user) {
                    $feed->update(['created_by' => $user->new_user_id]);
                }
            }

            if ($feed->updated_by) {
                $user = DB::table('users')->where('id', $feed->updated_by)->first();
                if ($user) {
                    $feed->update(['updated_by' => $user->new_user_id]);
                }
            }
        }

        $customfeeds = \App\CustomFeed::get();
        foreach ($customfeeds as $customfeed) {
            if ($customfeed->created_by) {
                $user = DB::table('users')->where('id', $customfeed->created_by)->first();
                if ($user) {
                    $customfeed->update(['created_by' => $user->new_user_id]);
                }
            }

            if ($customfeed->updated_by) {
                $user = DB::table('users')->where('id', $customfeed->updated_by)->first();
                if ($user) {
                    $customfeed->update(['updated_by' => $user->new_user_id]);
                }
            }
        }
    }

    public static function updateOrder()
    {
        $ids = [];
        \App\Order::with('product.prices', 'product.collection', 'couponApplied')->chunk(50, function ($orders) {
            foreach ($orders as $order) {
                try {
                    $meta = json_decode($order->meta);
                    $attendees = [];
                    if ($meta->attendees) {
                        if (gettype($meta->attendees) == 'string') {
                            $attendees = json_decode($meta->attendees);
                            if ($attendees->attendees) {
                                $attendees = $attendees->attendees;
                            }
                        } else {
                            $attendees = $meta->attendees;
                        }
                    }

                    $data = [
                        'attendees'        => $attendees,
                        'productPrice'     => null,
                        'vendor_id'        => null,
                        'coupon'           => $order->couponApplied,
                        'collection_id'    => null,
                        'collection_type'  => null,
                    ];

                    if ($order->product and count($order->product->prices)) {
                        $productPriceId = $order->product->prices[0]->id;
                        $productPrice = ProductPrice::withTrashed()->where('id', $productPriceId)->with('products', 'discounts')->first();
                        $data['productPrice'] = $productPrice;
                    }
                    $collection_id = null;
                    if ($order->product and $order->product->collection) {
                        if ($order->product->collection->vendor_id) {
                            $data['vendor_id'] = $order->product->collection->vendor_id;
                        }

                        $data['collection_type'] = $order->product->collection->collection_type;
                        $collection_id = $order->product->collection->id;
                        $data['collection_id'] = $collection_id;
                    }

                    $order->update(['meta' => json_encode($data), 'collection_id' =>$collection_id]);
                } catch (\Throwable $th) {
                    $ids[] = $order->id;
                }
            }
        });

        return $ids;
    }

    public static function findVendorId($email = null, $mobileRequest = false)
    {
        $vendorId = null;
        if (! $email && ! $mobileRequest) {
            return $vendorId;
        }
        if ($mobileRequest && Auth::check()) {
            $user = auth()->user();
            $user->load('staffVendors');
            $vendors = $user->staffVendors;
            if (count($vendors) > 0) {
                $vendorId = $vendors[0]->id;
            }
        } else {
            if ($email && ! Auth::check()) {
                $vendor = Vendor::where('contact_email', $email)->first();
                $vendorId = $vendor ? $vendor->id : '';
            } elseif (Auth::check()) {
                $user = auth()->user();
                $user->load('staffVendors');
                $vendors = $user->staffVendors;
                if (count($vendors) == 0) {
                    $vendorId = null;
                } else {
                    $vendorId = $vendors[0]->id;
                }
            }

            return $vendorId;
        }

        return  $vendorId;
    }

    public static function getStudentsIds($type = null)
    {
        $user = auth()->user();
        if (! $user) {
            return [];
        }
        $authId = auth()->id();
        if ($user->role_id == RoleType::Student) {
            return [$authId];
        }

        if ($type and in_array($type, [CollectionType::events, CollectionType::workshops])) {
            return [$authId];
        }

        $guardian = DB::connection('partner_mysql')->table('guardians')->where('user_id', auth()->id())->first();
        $students = DB::connection('partner_mysql')->table('student_guardian')->where('guardian_id', $guardian ? $guardian->id : '')->pluck('student_id');
        $studentsIds = DB::connection('partner_mysql')->table('students')->whereIn('id', $students)->pluck('user_id');

        return $studentsIds;
    }

    public static function isMobileRequest() 
    {
        $headers = apache_request_headers();
        $isMobile = false;
       
        if (isset($headers['mobile']) and $headers['mobile']) {
            $isMobile = true;
        }

        if (isset($headers['Mobile']) and $headers['Mobile']) {
            $isMobile = true;
        }

        if (isset($headers['deviceinfo']) and $headers['deviceinfo']) {
            $isMobile = true;
        }

        return $isMobile ;
    }
    public static function paginate(Collection $results, $pageSize)
    {
        $page = Paginator::resolveCurrentPage('page');

        $total = $results->count();

        return self::paginator($results->forPage($page, $pageSize), $total, $pageSize, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);
    }

    /**
     * Create a new length-aware paginator instance.
     *
     * @param  \Illuminate\Support\Collection  $items
     * @param  int  $total
     * @param  int  $perPage
     * @param  int  $currentPage
     * @param  array  $options
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    protected static function paginator($items, $total, $perPage, $currentPage, $options)
    {
        return Container::getInstance()->makeWith(LengthAwarePaginator::class, compact(
            'items', 'total', 'perPage', 'currentPage', 'options'
        ));
    }
}
