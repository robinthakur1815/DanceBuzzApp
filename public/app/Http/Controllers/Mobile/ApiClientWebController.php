<?php

namespace App\Http\Controllers\Mobile;

use App\Category;
use App\Http\Controllers\Controller;


use App\Lib\Util;
use Illuminate\Http\Request;

use App\Http\Resources\MobileDataCollection;
use App\Http\Resources\MobileData as MobileDataResource;

use App\Collection as AppCollection;
use App\Model\PartnerCollection;

use App\Enums\CollectionType;
use App\Enums\PublishStatus;
use App\Enums\ReviewStatus;
use App\Enums\RoleType;
use App\Helpers\LiveClassHelper;
use App\Model\Language;
use App\Student;
use App\Vendor;
use App\Guardian;
use App\Model\Partner\Service;
use Illuminate\Support\Str;


class ApiClientWebController extends Controller
{
    public function getCities()
    {
        $endDate = now()->format('Y/m/d');
        $allEvents = PartnerCollection::whereNotNull('published_content')
            ->whereIn('collection_type', [CollectionType::events, CollectionType::workshops, CollectionType::classes, CollectionType::classDeck])
            ->where('published_content->end_date', '>=', $endDate)->select('published_content')->get();
        foreach ($allEvents as $data) {
            $data->published_content = json_decode($data->published_content);
            $data['ischeck']  = true;
        }

        $cities = $allEvents->map(
            function ($data) {
                if (isset($data->published_content->city) && $data->published_content->city) {
                    return Str::title($data->published_content->city);
                }
            }
        );

        $cityArray = array_unique(array_filter($cities->all()));
        $filterCities = [];

        foreach ($cityArray as $value) {
            $filterCities[] =  $value;
        }

        return  $filterCities;
    }

    public function getCategories(Request $request)
    {

        $categories = Category::select('id', 'slug', 'name')->latest()->get();

        $categories->map(function ($item) {
            $item->name =  Str::title($item->name);
            return $item;
        });


        return $categories;

        $categories = Category::with('publishedCollections');

        if ($request->type) {
            $type = CollectionType::getValue($request->type);
            $categories = $categories->whereHas('collectionPivot', function ($query) use ($type) {
                $query->where('collection_type', $type);
            });
        }

        $categories = $categories->latest()->get();


        $categories->map(function ($item) use ($request) {
            $item->published_count =  $request->type ? count($item->publishedCollections->where('collection_type', CollectionType::getValue($request->type))) : count($item->publishedCollections);
            unset($item->publishedCollections);
        });

        $datas =  $categories->where('published_count', '>', 0)->sortBy('published_count');

        $categoriesData = [];
        foreach ($datas as $data) {
            $categoriesData[] = [
                'id'   => $data->id,
                'slug' => $data->slug,
                'name' => Str::title($data->name)
            ];
        }

        return $categoriesData;

        $categories = Category::latest()->whereNull('collection_type')->whereNull('parent_id')->select('id', 'slug', 'name')->get();

        return $categories;
    }

    public function getServices()
    {
        $services = Service::get();

        return $services;
    }

    public function getLanguage()
    {
        $languages = Language::get();

        return $languages;
    }


    public function eventListingPage(Request $request, $city = null)
    {
        try {

            // $endDate = now()->format('Y/m/d');
            // $allEvents = AppCollection::with(['product.prices.discounts', 'product.productReviews'])->whereNotNull('published_content')
            //     ->whereIn('collection_type', [CollectionType::events, CollectionType::workshops])
            //     ->where('published_content->end_date', '>=', $endDate);

            // $allClasses = AppCollection::with(['product.prices.discounts', 'product.productReviews'])->whereNotNull('published_content')
            //     ->whereIn('collection_type', [CollectionType::classes])
            //     ->where('published_content->end_date', '>=', $endDate);

            // $allLiveClasses = AppCollection::with(['product.prices.discounts', 'product.productReviews'])->whereNotNull('published_content')
            //     ->whereIn('collection_type', [CollectionType::liveClass])
            //     ->where('published_content->end_date', '>=', $endDate);

            // $allCollections = AppCollection::with(['product.prices.discounts', 'product.productReviews'])->whereNotNull('published_content')
            //     ->whereIn('collection_type', [CollectionType::liveClass, CollectionType::classes, CollectionType::events, CollectionType::workshops])
            //     ->where('published_content->end_date', '>=', $endDate)->latest()->orderBy('is_recommended');

            // if ($city) {
            //     $city = strtolower($city);
            //     $allEvents = $allEvents->whereRaw('lower(published_content->"$.location") like lower(?)', ["%{$city}%"]);
            //     $allClasses = $allClasses->whereRaw('lower(published_content->"$.location") like lower(?)', ["%{$city}%"]);
            //     $allCollections = $allCollections->whereRaw('lower(published_content->"$.location") like lower(?)', ["%{$city}%"]);
            // }

            // if ($request->categories) {
            //     $categories = json_decode($request->categories);
            //     if (is_array($categories) and count($categories)) {

            //         $allEvents = $allEvents->where(function ($query) use ($categories) {
            //             $firstId = array_shift($categories);
            //             $query->whereRaw(
            //                 'JSON_CONTAINS(categories, \'[' . $firstId . ']\')'
            //             );
            //             foreach ($categories as $id) {
            //                 $query->orWhereRaw(
            //                     'JSON_CONTAINS(categories, \'[' . $id . ']\')'
            //                 );
            //             }
            //             return $query;
            //         });

            //         $allClasses = $allClasses->where(function ($query) use ($categories) {
            //             $firstId = array_shift($categories);
            //             $query->whereRaw(
            //                 'JSON_CONTAINS(categories, \'[' . $firstId . ']\')'
            //             );
            //             foreach ($categories as $id) {
            //                 $query->orWhereRaw(
            //                     'JSON_CONTAINS(categories, \'[' . $id . ']\')'
            //                 );
            //             }
            //             return $query;
            //         });

            //         $allCollections = $allCollections->where(function ($query) use ($categories) {
            //             $firstId = array_shift($categories);
            //             $query->whereRaw(
            //                 'JSON_CONTAINS(categories, \'[' . $firstId . ']\')'
            //             );
            //             foreach ($categories as $id) {
            //                 $query->orWhereRaw(
            //                     'JSON_CONTAINS(categories, \'[' . $id . ']\')'
            //                 );
            //             }
            //             return $query;
            //         });


            //     }
            // }

            // if ($request->services) {
            //     $services = json_decode($request->services);
            //     if (is_array($services) and count($services)) {
            //         $allClasses = $allClasses->where(function ($query) use ($services) {
            //             $firstId = array_shift($services);
            //             $query->whereRaw(
            //                 'JSON_CONTAINS(services, \'[' . $firstId . ']\')'
            //             );
            //             foreach ($services as $id) {
            //                 $query->orWhereRaw(
            //                     'JSON_CONTAINS(services, \'[' . $id . ']\')'
            //                 );
            //             }
            //             return $query;
            //         });

            //         $allLiveClasses = $allLiveClasses->where(function ($query) use ($services) {
            //             $firstId = array_shift($services);
            //             $query->whereRaw(
            //                 'JSON_CONTAINS(services, \'[' . $firstId . ']\')'
            //             );
            //             foreach ($services as $id) {
            //                 $query->orWhereRaw(
            //                     'JSON_CONTAINS(services, \'[' . $id . ']\')'
            //                 );
            //             }
            //             return $query;
            //         });

            //         $allCollections = $allCollections->where(function ($query) use ($services) {
            //             $firstId = array_shift($services);
            //             $query->whereRaw(
            //                 'JSON_CONTAINS(services, \'[' . $firstId . ']\')'
            //             );
            //             foreach ($services as $id) {
            //                 $query->orWhereRaw(
            //                     'JSON_CONTAINS(services, \'[' . $id . ']\')'
            //                 );
            //             }
            //             return $query;
            //         });


            //     }
            // }

            // $allEvents = $allEvents->latest()->get();
            // $allClasses = $allClasses->latest()->get();
            // $allLiveClasses = $allLiveClasses->latest()->get();

            $total_class_count = 0;//$allCollections->where('collection_type', CollectionType::classes)->count();
            $total_live_class_count = 0;//$allCollections->where('collection_type', CollectionType::liveClass)->count();
            $total_event_count = 0; //$allCollections->where('collection_type', CollectionType::events)->count();
            $total_workshop_Count = 0;//$allCollections->where('collection_type', CollectionType::workshops)->count();


            // foreach ($allEvents as $data) {
            //     $data->published_content = json_decode($data->published_content);
            //     $data['ischeck']  = true;
            // }

            // foreach ($allClasses as $data) {
            //     $data->published_content = json_decode($data->published_content);
            //     $data['ischeck']  = true;
            // }

            // foreach ($allLiveClasses as $data) {
            //     $data->published_content = json_decode($data->published_content);
            //     $data['ischeck']  = true;
            // }

            // $recommendedEvents = $allEvents->filter(function ($data) {
            //     if (isset($data->is_recommended) && $data->is_recommended) {
            //         return $data->is_recommended;
            //     }
            // })->take(10);

            // $recommendedClasses = $allClasses->filter(function ($data) {
            //     if (isset($data->is_recommended) && $data->is_recommended) {
            //         return $data->is_recommended;
            //     }
            // })->take(10);

            // $recommendedLiveClasses = $allLiveClasses->filter(function ($data) {
            //     if (isset($data->is_recommended) && $data->is_recommended) {
            //         return $data->is_recommended;
            //     }
            // })->take(10);

            // $pageLimit = config('client.page_limit');
            // $allCollections = $allCollections->take($pageLimit)->get();

            // foreach ($allCollections as $collection) {
            //     $collection->published_content = json_decode($collection->published_content);
            //     $collection['ischeck']  = true;
            // }

            // $recommendedClasses = collect($recommendedClasses);
            // $recommendedLiveClasses = collect($recommendedLiveClasses);

            // $recommendedEvents = $recommendedClasses->union($recommendedEvents)->all();
            // $recommendedEvents = collect($recommendedEvents)->union($recommendedLiveClasses)->all();

            // $featuredEvents = $allEvents->filter(function ($data) {
            //     return $data->is_featured && $data->collection_type == CollectionType::events;
            // })->take(10);

            $request->city = $city;

            $featuredEvents = new MobileDataCollection( $this->getDashboardCollection([CollectionType::events], $request));

            // $featuredWorkshops = $allEvents->filter(function ($data) {
            //     return $data->is_featured && $data->collection_type == CollectionType::workshops;
            // })->take(10);
            $featuredWorkshops = new MobileDataCollection($this->getDashboardCollection([CollectionType::workshops], $request));


            // $featuredClasses = $allClasses->filter(function ($data) {
            //     return $data->is_featured && $data->collection_type == CollectionType::classes;
            // })->take(10);


            $featuredClasses = new MobileDataCollection( $this->getDashboardCollection([CollectionType::classes], $request));

            // $featuredLiveClasses = $allLiveClasses->filter(function ($data) {
            //     return $data->is_featured && $data->collection_type == CollectionType::liveClass;
            // })->take(10);

            $featuredLiveClasses = new MobileDataCollection($this->getDashboardCollection([CollectionType::classDeck], $request));

            $allCollections =  new MobileDataCollection($this->getDashboardCollection([CollectionType::classDeck, CollectionType::classes, CollectionType::workshops, CollectionType::events ], $request));
            $data = [];
            array_push($data, [
                'title'          => 'Recommend Planners',
                'id'             => 1,
                'type'           => 0,
                'collections'    => $allCollections,
                'count'          => 0,
                'key'            => 'is_recommended',
                'value'          => 1,
                'is_recommended' => true
            ]);

            array_push($data, [
                'title'          => 'Live Classes',
                'id'             => 2,
                'type'           => CollectionType::classDeck,
                'collections'    => $featuredLiveClasses,
                'count'          => 0,
                'key'            => 'type',
                'value'          => CollectionType::classDeck,
                'is_recommended' => false
            ]);

            array_push($data, [
                'title'          => 'Classes',
                'type'           => CollectionType::classes,

                'id'             => 3,
                'collections'    => $featuredClasses,
                'count'          => 0,
                'key'            => 'type',
                'value'          => CollectionType::classes,
                'is_recommended' => false
            ]);

            array_push($data, [
                'title'          => 'Events',
                'type'           => CollectionType::events,

                'id'             => 4,
                'collections'    => $featuredEvents,
                'count'          => 0,
                'key'            => 'type',
                'value'          => CollectionType::events,
                'is_recommended' => false
            ]);

            array_push($data, [
                'title'          => 'Workshop',
                'id'             => 5,
                'type'           => CollectionType::workshops,

                'collections'    => $featuredWorkshops,
                'count'          => 0,
                'key'            => 'type',
                'value'          => CollectionType::workshops,
                'is_recommended' => false
            ]);
            // $isPaginate = false;

            // $featuredClasses = LiveClassHelper::checkCollectionIsBought($isPaginate, $featuredClasses);

            // $featuredLiveClasses = $allClasses->filter(function ($data) {
            //     return $data->is_featured && $data->collection_type == CollectionType::liveClass;
            // })->take(10);

            // $featuredLiveClasses = LiveClassHelper::checkCollectionIsBought($isPaginate, $featuredLiveClasses);

            // $allCollections =

            // $cities = collect($allEvents->union($allClasses)->all())->map(
            //     function ($data) {
            //         if (isset($data->published_content->location) && $data->published_content->location) {
            //             return Str::title($data->published_content->location);
            //         }
            //     }
            // );

            // $cityArray = array_unique(array_filter($cities->all()));
            // $filterCities = [];

            // $categories = Category::whereIn('collection_type', [CollectionType::events, CollectionType::workshops, CollectionType::classes, CollectionType::liveClass])
            //     ->orWhereNUll('collection_type')->latest()->select('id', 'slug', 'name')->get();

            // foreach ($cityArray as $value) {
            //     $filterCities[] =  $value;
            // }

            $keys  = [
                'recommended_text' => 'Some Handpicked event for you',
                'live_class'       => 'Live Class',
                'classes'          => 'Classes',
                'events'           => 'Event',
                'workshops'        => 'Workshop',
                'planner'          => 'Planner'
            ];

            return [
                // 'recommended_events'      => new MobileDataCollection(collect($allCollections)),
                // 'featured_events'         => new MobileDataCollection(collect($featuredEvents)),
                // 'featured_workshops'      => new MobileDataCollection(collect($featuredWorkshops)),
                // 'featured_classes'        => new MobileDataCollection(collect($featuredClasses)),
                // 'featured_live_classes'   => new MobileDataCollection(collect($featuredLiveClasses)),
                'cities'                  => [],//$filterCities,
                'categories'              => [],//$categories,
                // 'total_class_count'       => $total_class_count,
                // 'total_live_class_count'  => $total_live_class_count,
                // 'total_event_count'       => $total_event_count,
                // 'total_workshop_Count'    => $total_workshop_Count,
                // 'titles'                  => $keys,
                'data'                    => $data
            ];
        } catch (\Exception $e) {
            report($e);
            return response(['message' =>  "server error", 'status' => false], 500);
        }
    }


    public function collectionDetailPage($slug)
    {
        $collection = AppCollection::where('status',  PublishStatus::Published)->where('slug', $slug)->whereNotNull('published_content')->first();
        if (!$collection) {
            return response(['errors' => ['collection' => ["collection not found or not in published state"]], 'status' => false, 'message' => ''], 422);
        }
        $collection['isdetails'] = true;
        return new MobileDataResource($collection);
    }


    public function collectionFilteredListing(Request $request)
    {
        $user = auth()->user();
        $endDate = now()->format('Y/m/d');
        $datas = AppCollection::whereNotNull('published_content');

            // ->where('status',  PublishStatus::Published);

        $type = $request->type;
        if ($type != CollectionType::campaignsType) {
            $datas =  $datas->where('published_content->end_date', '>=', $endDate);
        }

        $search = $request->search;

        if ($request->city) {
            $city = strtolower($request->city);
            if ($type != CollectionType::classDeck) {
                $datas = $datas->whereRaw('lower(published_content->"$.location") like lower(?)', ["%{$city}%"]);
            }
        }

        if ($request->ratings || $request->rating) {
            $datas = $datas->whereHas('productReviews', function ($query) use ($request) {
                // Id from web
                if ($request->ratings) {
                    $min = $request->ratings == 1 ?  $request->ratings :  $request->ratings - 1;
                    $max = $request->ratings == 1 ?  $request->ratings :  $request->ratings + 1;
                }

                // Enum from mobile
                if ($request->rating) {
                    $min = $request->ratings == 1 ?  $request->rating :  $request->rating - 1;
                    $max = $request->rating == 1 ?  $request->rating :  $request->rating + 1;
                }

                if ($max == 1) {
                    return $query->havingRaw('AVG(product_reviews.rating) <= ?', [$max])
                    ->where('review_status', ReviewStatus::Approved);
                }else{
                    return $query->havingRaw('AVG(product_reviews.rating) > ?', [$min])
                    ->havingRaw('AVG(product_reviews.rating) < ?', [$max])
                    ->where('review_status', ReviewStatus::Approved);
                }
            });
        }



        if ($request->categories) {
            // $categories = json_decode($request->categories);
            if (is_array($request->categories)) {
                $categories = $request->categories;
            }else{
                $categories = json_decode($request->categories);
            }
            if (is_array($categories) and count($categories)) {
                $datas = $datas->where(function ($query) use ($categories) {
                    $firstId = array_shift($categories);
                    $query->whereRaw(
                        'JSON_CONTAINS(categories, \'[' . $firstId . ']\')'
                    );
                    foreach ($categories as $id) {
                        $query->orWhereRaw(
                            'JSON_CONTAINS(categories, \'[' . $id . ']\')'
                        );
                    }
                    return $query;
                });
            }
        }


        if ($request->services and in_array($type, [CollectionType::classes, CollectionType::classDeck])) {

            if (is_array($request->services)) {
                $services = $request->services;
            }else{
                $services = json_decode($request->services);
            }
            if (is_array($services) and count($services)) {
                $datas = $datas->where(function ($query) use ($services) {
                    $firstId = array_shift($services);
                    $query->whereRaw(
                        'JSON_CONTAINS(services, \'[' . $firstId . ']\')'
                    );
                    foreach ($services as $id) {
                        $query->orWhereRaw(
                            'JSON_CONTAINS(services, \'[' . $id . ']\')'
                        );
                    }
                    return $query;
                });
            }
        }

        $campaignType = $request->type_id;
        if ($type) {
            $collections = [CollectionType::events, CollectionType::workshops, CollectionType::classes,  CollectionType::classDeck, CollectionType::campaigns];
            $endDateValid = false;
            $startDateValid = false;
            if (in_array($type, $collections)) {
                $endDateValid = true;
                $startDateValid = true;
            }
            $datas = $datas->where('collection_type',  $type);
            
            if($user and $user->role_id == RoleType::Student and $type == CollectionType::campaigns){       
                $student = Student::where('user_id',$user->id)->first();
                $studentVendorId = $student->vendor_id;
                if($studentVendorId){
                    $studentVendor = Vendor::find($studentVendorId);
                    $studentVendorUserId = $studentVendor->created_by;
                    $datas =  $datas->where('is_private', true)
                    ->where('created_by', $studentVendorUserId)
                    ->orWhere('is_private',false);
            
                }                    
            }
            if($user and $user->role_id == RoleType::Guardian and $type == CollectionType::campaigns){ 
                $students = [];
                $guardian = Guardian::where('user_id', $user->id)->first();
                $guardian->load('students');
                $students = $guardian->students;
                $studentVendorUserIds = [];
                foreach($students as $student){
                    // $studentVendorUserIds = [];
                    $studentVendorId = $student->vendor_id;
                    if($studentVendorId){
                        $studentVendor = Vendor::find($studentVendorId);
                        $studentVendorUserIds[] = $studentVendor->created_by;
                    }
                }
                $datas =  $datas->where('is_private', true)
                    ->whereIn('created_by', $studentVendorUserIds)
                    ->orWhere('is_private',false);        

            }

            
            $endDate = now()->format('Y/m/d');
            if ($endDateValid) {
                $datas = $datas->where('published_content->end_date', '>=', $endDate);
            }
            if ($startDateValid) {
                // $datas = $datas->where('published_content->start_date', '<=', $endDate);
            }
        }

        if ($request->language_id) {
            $datas = $datas->where('published_content->language_id', $request->language_id);
        }

        // Language Enum (for mobile)
        if ($request->languages) {
            $datas = $datas->whereIn('published_content->language_id', $request->languages);
        }

        if ($campaignType) {
            $datas =  $datas->where('published_content->campaign_type->id', $campaignType);
        }

        if (isset($request->is_featured) and $request->is_featured) {
            $datas =  $datas->where('is_featured', true);
        }

        if (isset($request->is_recommended) and $request->is_recommended) {
            $datas =  $datas->where('is_recommended', true);
        }

        if ($search) {
            $datas = $datas->where('title', 'like', "%${search}%");
        }

        if (isset($request->has_categories) && $request->has_categories) {
            $datas =  $datas->whereNotNull('categories');
        }



        if ($request->most_selling) {
            $datas = $datas->has('confirmOrders')
                ->withCount('confirmOrders')
                ->orderBy('confirm_orders_count', 'desc');
        } else {
            $datas = $datas->latest();
        }

        // if ($request->read_time) {
        //     $datas =  $datas->where('published_content->read_time', '<', $request->read_time);
        // }

        // if ($request->start_date) {
        //     $datas =  $datas->where('published_content->date', '>=', $request->start_date);
        // }

        // if ($request->end_date) {
        //     $datas =  $datas->where('published_content->date', '<=', $request->end_date);
        // }
        $isPaginate = false;
        if ($request->max_rows) {
            $datas = $datas->paginate($request->max_rows);
            $isPaginate = true;
        } else {
            $datas = $datas->get();
        }

        $collections = LiveClassHelper::checkCollectionIsBought($isPaginate, $datas);

        // foreach ($collections as $collection) {
        //     $collection->published_content = json_decode($collection->published_content);
        //     $collection['ischeck']  = true;
        // }

        // return collect($datas);

        return new MobileDataCollection($collections);
    }


    public function recommendedLiveClasses(Request $request)
    {
        $type = $request->type;
        // $is_web = $request->is_web;

        //$collection_type_name =  $type ? ucwords(preg_replace('/([a-z])([A-Z])/', "\\1 \\2", (CollectionType::getKey($type)))) : null;
        $collection_type_name = Util::collectionTypeLookup($type);
        // $interested =  $this->getCollection($request, null, null, false, null);
        // $interested_count = $this->getCollection($request, null, null, true, null);
        $most_ordered =  $this->getCollection($request, null, null, false, 'confirmOrders');
        $most_ordered_count = $this->getCollection($request, null, null, true, 'confirmOrders');
        $all_collection_count = $this->getCollection($request, null, null, true, null);

        $all_collections = $this->getCollection($request, null, 1, false, null);
        $features = $this->getCollection($request, 'is_featured', 1, false, null);
        $features_count = $this->getCollection($request, 'is_featured', 1, true, null);
        $recommended = $this->getCollection($request, 'is_recommended', 1, false, null);
        $recommended_count =  $this->getCollection($request, 'is_recommended', 1, true, null);

        // $interested = $interested_count ? new MobileDataCollection($this->mapCollection($interested)) : [];
        $collections = $all_collection_count ? new MobileDataCollection($this->mapCollection($all_collections)) : [];
        $mostSelling = $most_ordered_count ? new MobileDataCollection($this->mapCollection($most_ordered)) : [];
        $features = $features_count ? new MobileDataCollection($this->mapCollection($features)) : [];
        $recommended = $recommended_count ? new MobileDataCollection($this->mapCollection($recommended)) : [];
        $datas = [];

        if ($type == CollectionType::classDeck) {

            // $english = $this->getCollection($request, 'published_content->language_id', 1, false, null);
            // $english_count =  $this->getCollection($request, 'published_content->language_id', 1, true, null);
            // $hindi =  $this->getCollection($request, 'published_content->language_id', 2, false, null);
            // $hindi_count  = $this->getCollection($request, 'published_content->language_id', 2, true, null);

            // $hindi = $hindi_count ?  new MobileDataCollection($this->mapCollection($hindi)) : [];
            // $english = $english_count ?  new MobileDataCollection($this->mapCollection($english)) : [];


            // if ($features_count) {
            //     array_push($datas, [
            //         'title'       => 'Featured ' . $collection_type_name,
            //         'id'          => 5,
            //         'collections' => $features,
            //         'count'       => $features_count,
            //         'key'         => 'is_featured',
            //         'value'       => true
            //     ]);
            // }

            // if ($all_collection_count) {
            //     array_push($datas, [
            //         'title'       => 'All ' . $collection_type_name,
            //         'id'          => 7,
            //         'collections' => $collections,
            //         'count'       => $all_collection_count,
            //         'key'         => '',
            //         'value'       => false
            //     ]);
            // }

            // if ($recommended_count) {
            //     array_push($datas, [
            //         'title'       => 'Recommended ' . $collection_type_name,
            //         'id'          => 6,
            //         'collections' => $recommended,
            //         'count'       => $recommended_count,
            //         'key'         => 'is_recommended',
            //         'value'       => true
            //     ]);
            // }



            // if ($english_count) {
            //     array_push($datas, [
            //         'title'       => 'English ' . $collection_type_name,
            //         'id'          => 1,
            //         'collections' => $english,
            //         'count'       => $english_count,
            //         'key'         => 'language_id',
            //         'value'       => 1,
            //         'is_language' => true
            //     ]);
            // }

            // if ($hindi_count) {
            //     array_push($datas, [
            //         'title'       => 'Hindi ' . $collection_type_name,
            //         'id'          => 2,
            //         'collections' => $hindi,
            //         'count'       => $hindi_count,
            //         'key'         => 'language_id',
            //         'value'       => 2,
            //         'is_language' => true
            //     ]);
            // }

            // if ($interested_count) {
            //     array_push($datas, [
            //         'title'       => 'You might be interested',
            //         'id'          => 3,
            //         'collections' => $interested,
            //         'count'       => $interested_count,
            //         'key'         => 'is_interested',
            //         'value'       => true
            //     ]);
            // }

            // if ($most_ordered_count) {
            //     array_push($datas, [
            //         'title'       => 'Most Popular ' . $collection_type_name,
            //         'id'          => 4,
            //         'collections' => $mostSelling,
            //         'count'       => $most_ordered_count,
            //         'key'         => 'most_selling',
            //         'value'       => true
            //     ]);
            // }

            // return $datas;
        }


        // if ($features_count) {
            array_push($datas, [
                'title'       => 'Featured ' . $collection_type_name,
                'id'          => 5,
                'collections' => $features,
                'count'       => $features_count,
                'key'         => 'is_featured',
                'value'       => true
            ]);
        // }

        // if ($all_collection_count) {
            array_push($datas, [
                'title'       => 'All ' . $collection_type_name,
                'id'          => 7,
                'collections' => $collections,
                'count'       => $all_collection_count,
                'key'         => '',
                'value'       => false
            ]);
        // }




        // if ($recommended_count) {
            array_push($datas, [
                'title'       => 'Recommended ' . $collection_type_name,
                'id'          => 6,
                'collections' => $recommended,
                'count'       => $recommended_count,
                'key'         => 'is_recommended',
                'value'       => true
            ]);
        // }

        // if ($most_ordered_count) {
            array_push($datas, [
                'title'       => 'Most Popular ' . $collection_type_name,
                'id'          => 4,
                'collections' => $mostSelling,
                'count'       => $most_ordered_count,
                'key'         => 'most_selling',
                'value'       => true
            ]);
        // }



        // if ($interested_count) {
        //     array_push($datas, [
        //         'title'       => 'You might be interested',
        //         'id'          => 3,
        //         'collections' => $interested,
        //         'count'       => $interested_count,
        //         'key'         => 'is_interested',
        //         'value'       => true
        //     ]);
        // }

        return $datas;
    }


    private function getCollection($request, $key = null, $value = null, $isCount = false, $withCount = null)
    {
        $type = $request->type;
        $is_web = $request->is_web;
        $endDate = now()->format('Y/m/d');
        $datas = AppCollection::whereNotNull('published_content')
            ->where('published_content->end_date', '>=', $endDate)
            ->where('status',  PublishStatus::Published);
        if ($type) {
            $datas =  $datas->where('collection_type', $type);
        } else {
            $datas =  $datas->where('collection_type', CollectionType::classDeck);
        }

        if ($request->services and in_array($type, [CollectionType::classes, CollectionType::classDeck])) {
            $services = json_decode($request->services);
            if (is_array($services) and count($services)) {
                $datas = $datas->where(function ($query) use ($services) {
                    $firstId = array_shift($services);
                    $query->whereRaw(
                        'JSON_CONTAINS(services, \'[' . $firstId . ']\')'
                    );
                    foreach ($services as $id) {
                        $query->orWhereRaw(
                            'JSON_CONTAINS(services, \'[' . $id . ']\')'
                        );
                    }
                    return $query;
                });
            }
        }

        if ($request->categories) {
            $categories = json_decode($request->categories);
            if (is_array($categories) and count($categories)) {
                $datas = $datas->where(function ($query) use ($categories) {
                    $firstId = array_shift($categories);
                    $query->whereRaw(
                        'JSON_CONTAINS(categories, \'[' . $firstId . ']\')'
                    );
                    foreach ($categories as $id) {
                        $query->orWhereRaw(
                            'JSON_CONTAINS(categories, \'[' . $id . ']\')'
                        );
                    }
                    return $query;
                });
            }
        }

        if ($request->language_id) {
            $datas = $datas->where('published_content->language_id', $request->language_id);
        }

        if (isset($request->is_featured) and $request->is_featured) {
            $datas =  $datas->where('is_featured', true);
        }

        if (isset($request->is_recommended) and $request->is_recommended) {
            $datas =  $datas->where('is_recommended', true);
        }

        if ($request->city) {
            $city = strtolower($request->city);
            $datas = $datas->whereRaw('lower(published_content->"$.location") like lower(?)', ["%{$city}%"]);
        }

        if ($key && $value) {
            $datas = $datas->where($key, $value);
        }

        if ($withCount) {
            $datas = $datas->has('confirmOrders')
                ->withCount('confirmOrders')
                ->orderBy('confirm_orders_count', 'desc');
        } else {
            $datas = $datas->latest();
        }

        if (!$isCount) {
            $pageLimit = config('client.page_limit');
            $webPageLimit = config('client.web_page_limit');
            $datas =  $datas->take($is_web ? $webPageLimit : $pageLimit)->get();
            $isPaginate = false;
            $datas = LiveClassHelper::checkCollectionIsBought($isPaginate, $datas);
            return $datas;
        } else {
            return $datas->count();
        }
    }

    private function mapCollection($collections)
    {
        // foreach($collections as $collection){
        //     $collection->published_content = json_decode($collection->published_content);
        //     $collection['ischeck']  = true;
        //     return $collection;
        // }

        // return $collections;
        return $collections;
    }

    private function getDashboardCollection($types, $request)
    {
        // $types = [CollectionType::liveClass, CollectionType::classes, CollectionType::events, CollectionType::workshops];
        $endDate = now()->format('Y/m/d');
        $city = $request->city;
        $allCollections = AppCollection::with(['product.prices.discounts', 'product.productReviews'])
        ->where('status',  PublishStatus::Published)
        ->whereNotNull('published_content')
        ->whereIn('collection_type', $types )
        ->where('published_content->end_date', '>=', $endDate)
        ->orderBy('is_recommended')
        ->orderBy('is_featured')
        ->latest();

        if ($city) {
            $city = strtolower($city);
            $allCollections = $allCollections->whereRaw('lower(published_content->"$.location") like lower(?)', ["%{$city}%"]);
        }

        if ($request->categories) {
            $categories = json_decode($request->categories);
            if (is_array($categories) and count($categories)) {
                $allCollections = $allCollections->where(function ($query) use ($categories) {
                    $firstId = array_shift($categories);
                    $query->whereRaw(
                        'JSON_CONTAINS(categories, \'[' . $firstId . ']\')'
                    );
                    foreach ($categories as $id) {
                        $query->orWhereRaw(
                            'JSON_CONTAINS(categories, \'[' . $id . ']\')'
                        );
                    }
                    return $query;
                });
            }
        }

        if ($request->services) {
            $services = json_decode($request->services);
            if (is_array($services) and count($services)) {
                $allCollections = $allCollections->where(function ($query) use ($services) {
                    $firstId = array_shift($services);
                    $query->whereRaw(
                        'JSON_CONTAINS(services, \'[' . $firstId . ']\')'
                    );
                    foreach ($services as $id) {
                        $query->orWhereRaw(
                            'JSON_CONTAINS(services, \'[' . $id . ']\')'
                        );
                    }
                    return $query;
                });
            }
        }

        $pageLimit = config('client.page_limit');
        $allCollections = $allCollections->take($pageLimit)->get();
        foreach ($allCollections as $data) {
            $data->published_content = json_decode($data->published_content);
            $data['ischeck']  = true;
        }
        $isPaginate = false;

        $allCollections = LiveClassHelper::checkCollectionIsBought($isPaginate, $allCollections);

        return collect($allCollections);
    }
}
