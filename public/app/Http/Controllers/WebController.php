<?php

namespace App\Http\Controllers;

use App\Category;
use App\Collection as AppCollection;
use App\Country;
use App\Enums\CollectionType;
use App\Enums\MonthName;
use App\Enums\PublishStatus;
use App\Helpers\LiveClassHelper;
use App\Http\Resources\WebData as WebDataResource;
use App\Http\Resources\WebDataCollection;
use App\Http\Resources\WebPage as PageResource;
use App\Tag;
use App\WebPage;
use App\WebSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class WebController extends Controller
{
    public function getWebPageDetails($slug)
    {
        $page = WebPage::with('seo', 'sections')->where('slug', $slug)->first();

        if (! $page) {
            return response(['errors' => [__('validation.no_page')], 'status' => false, 'message' => ''], 422);
        }

        $data = json_decode($page->content);
        $page->content = $data->content;
        if ($page->seo && $page->seo->meta) {
            $page->meta = json_decode($page->seo->meta);

            unset($page->seo);
        }

        return new PageResource($page);
    }

    /**
     * Get Filtered Collection Data with pagination.
     */
    public function getFilteredCollections(Request $request, $type)
    {
        $datas = AppCollection::where('collection_type', CollectionType::getValue($type))->where('status', PublishStatus::Published)->whereNotNull('published_content')->latest();

        // if ($request->categories) {
        //     $datas =  $datas->whereJsonContains('categories', $request->categories);
        // }

        if (isset($request->is_featured)) {
            $datas = $datas->where('is_featured', 1);
        }

        if ($request->ignoreCollections && count($request->ignoreCollections) > 0) {
            $ids = array_map(function ($col) {
                return $col['id'];
            }, $request->ignoreCollections);
            $datas = $datas->whereNotIn('id', $ids);
        }

        if ($request->read_time) {
            $datas = $datas->where('published_content->read_time', '<', $request->read_time);
        }

        if ($request->start_date) {
            $datas = $datas->where('published_content->date', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $datas = $datas->where('published_content->date', '<=', $request->end_date);
        }

        if ($request->country) {
            $datas = $datas->where('published_content->country', $request->country);
        }

        if ($request->tags) {
            $datas = $datas->where(function ($q) use ($request) {
                foreach ($request->tags as $key => $tag) {
                    if ($key == 0) {
                        $q = $q->whereJsonContains('tags', $tag);
                    } else {
                        $q = $q->orWhereJsonContains('tags', $tag);
                    }
                }
            });
            // foreach ($request->tags as $key => $tag) {
            //     if ($key == 0) {
            //         $datas =  $datas->whereJsonContains('tags', $tag);
            //     } else {
            //         $datas =  $datas->orWhereJsonContains('tags', $tag);
            //     }
            // }
        }

        $datas = $datas->paginate($request->max_rows);

        $isPaginate = true;

        $datas = LiveClassHelper::checkCollectionIsBought($isPaginate, $datas);

        return new WebDataCollection($datas);
    }

    /**
     * Get Collection Data without filters and paginations.
     */
    public function getCollectionData($type, $count = null)
    {
        $datas = AppCollection::where('collection_type', CollectionType::getValue($type))->where('status', PublishStatus::Published)->whereNotNull('published_content')->latest();

        if ($count) {
            $datas = $datas->take($count);
        }

        $datas = $datas->get();
        $isPaginate = false;
        $datas = LiveClassHelper::checkCollectionIsBought($isPaginate, $datas);

        return new WebDataCollection($datas);
    }

    public function getFeaturedCollection($type = null)
    {
        // if($type == 'featured')
        // $featured = 1 ;
        // else
        // $featured = 0;
        if ($type != null) {
            $datas = AppCollection::where('collection_type', CollectionType::getValue($type))->where('status', PublishStatus::Published)->whereNotNull('published_content')->where('is_featured', 1);
        } else {
            $datas = AppCollection::whereNotNull('published_content')->where('status', PublishStatus::Published)->where('is_featured', 1)->get();
        }
        $datas = $datas->get();

        $isPaginate = false;

        $datas = LiveClassHelper::checkCollectionIsBought($isPaginate, $datas);

        // foreach($datas as $data){
        //     $publish = json_decode( $data->published_content);
        //     $data->published_content = $publish;
        // }
        return new WebDataCollection($datas);
    }

    public function getRecommendedCollection($type = null, $count = null)
    {
        $datas = AppCollection::whereNotNull('published_content')->where('status', PublishStatus::Published)->where('is_recommended', true);

        if ($type) {
            $colType = CollectionType::getValue($type);

            $datas = $datas->where('collection_type', CollectionType::getValue($type));
            if ($colType == CollectionType::events || $colType == CollectionType::workshops || $colType == CollectionType::classes) {
                $endDate = now()->format('Y/m/d');
                $datas = $datas->whereNotNull('published_content->end_date')->where('published_content->end_date', '>=', $endDate);
            }
        }

        if ($count) {
            $datas = $datas->take($count);
        }

        $datas = $datas->orderBy('title')->get();

        $isPaginate = false;

        $datas = LiveClassHelper::checkCollectionIsBought($isPaginate, $datas);

        return new WebDataCollection($datas);
    }

    public function getAllRecommendedCollection($count)
    {
        $datas = AppCollection::whereNotNull('published_content')->where('status', PublishStatus::Published)->where('is_recommended', 1)->orderBy('updated_at', 'DESC')->latest();

        if ($count) {
            $datas = $datas->take($count);
        }

        $datas = $datas->get();

        $isPaginate = false;

        $datas = LiveClassHelper::checkCollectionIsBought($isPaginate, $datas);

        return new WebDataCollection($datas);
    }

    public function getByCategory($slug)
    {
        $category = Category::where('slug', $slug)->first();
        $catId = $category->id;
        $datas = AppCollection::whereNotNull('published_content')->where('status', PublishStatus::Published)->whereJsonContains('categories', $catId)->get();

        $isPaginate = false;

        $datas = LiveClassHelper::checkCollectionIsBought($isPaginate, $datas);

        return new WebDataCollection($datas);
    }

    public function tag($slug)
    {
        $tag = Tag::where('slug', $slug)->first();
        $tagId = $tag->id;

        $datas = AppCollection::whereNotNull('published_content')->where('status', PublishStatus::Published)->whereJsonContains('tags', $tagId)->get();

        return new WebDataCollection($datas);
    }

    public function getCategoryCollections($type, $slug)
    {
        $category = Category::where('slug', $slug)->first();
        $catId = $category->id;
        $datas = AppCollection::whereNotNull('published_content')->where('status', PublishStatus::Published)->whereJsonContains('categories', $catId);

        if (CollectionType::getValue($type) == CollectionType::events) {
            $endDate = now()->format('Y/m/d');
            $datas = $datas->whereIn('collection_type', [CollectionType::events, CollectionType::workshops, CollectionType::classes])
                ->where('published_content->end_date', '>=', $endDate)
                ->with(['product.prices.discounts', 'product.productReviews']);
        } else {
            $datas = $datas->where('collection_type', CollectionType::getValue($type));
        }

        $datas = $datas->get();

        $isPaginate = false;

        $datas = LiveClassHelper::checkCollectionIsBought($isPaginate, $datas);

        return new WebDataCollection($datas);
    }

    public function collectionTypeTags($type, $slug, $count = 10)
    {
        $tag = Tag::where('slug', $slug)->first();
        if (! $tag) {
            return response(['message' =>  [__('validation.no_tag')], 'status' => false], 410);
        }

        $tagId = $tag->id;

        $datas = AppCollection::where('collection_type', CollectionType::getValue($type))->where('status', PublishStatus::Published)->whereNotNull('published_content')->latest();

        $datas = $datas->whereJsonContains('tags', $tagId)->paginate($count);

        return new WebDataCollection($datas);
    }

    public function collectionTypeSlug($type, $slug, Request $request)
    {
        $mobile = $request->mobile;
        $colType = CollectionType::getValue($type);
        $data = AppCollection::where('collection_type', $colType)
            ->whereNotNull('published_content')
            ->where('slug', $slug);
        if ($colType == CollectionType::events || $colType == CollectionType::workshops || $colType == CollectionType::classes) {
            $endDate = now()->format('Y/m/d');
            $data = $data->where('published_content->end_date', '>=', $endDate);
        }
        $data = $data->first();

        if (! $data) {
            return response(['errors' => [__('validation.no_data')], 'status' => false, 'message' => ''], 422);
        }

        $data['isdetails'] = true;
        if ($mobile) {
            $data['mobile'] = true;
        }

        return new WebDataResource($data);
    }

    public function getSingleCollectionData($type, $id)
    {
        $data = AppCollection::where('collection_type', CollectionType::getValue($type))->whereNotNull('published_content')->where('id', $id)->first();
        // return $data;
        // $data['new_content'] = json_decode($data->published_content);
        // return $data;
        return new WebDataResource($data);
    }

    public function getFilteredEvents(Request $request)
    {
        $datas = AppCollection::where('collection_type', CollectionType::events)
        ->where('status', PublishStatus::Published)->whereNotNull('published_content');

        if ($request->country) {
            $datas = $datas->where('published_content->country', $request->country);
        }

        if ($request->location) {
            $datas = $datas->where('published_content->location', 'like', "%{$request->location}%");
        }

        // if ($request->state) {
        //     $datas = $datas->where('published_content->state', 'like', "%{$request->state}%");
        // }

        if ($request->tags) {
            foreach ($request->tags as $key => $tag) {
                $datas = $datas->orWhereJsonContains('tags', $tag);
            }
        }

        if ($request->categories) {
            foreach ($request->categories as $key => $category) {
                $datas = $datas->orWhereJsonContains('categories', $category);
            }
        }

        if ($request->start_date) {
            $datas = $datas->where('published_content->end_date', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $datas = $datas->where('published_content->start_date', '<=', $request->end_date);
        }

        $datas = $datas->latest()->get();

        $isPaginate = false;

        $events = LiveClassHelper::checkCollectionIsBought($isPaginate, $datas);

        $events = new WebDataCollection($events);

        if ($request->year_id) {
            $events = $events->whereYear('start_date', $request->year_id);
        }
        if ($request->month_id) {
            $events = $events->whereMonth('start_date', $request->month_id);
        }

        if ($request->all_events) {
            $countries = Country::all();

            return ['events' => $events, 'countries' => $countries];
        } else {
            return $events;
        }
    }

    public function getSectorCollections($tag, $type = null)
    {
        $sector = Tag::where('slug', $tag)->first();
        if (! $sector) {
            return response(['message' =>  [__('validation.no_sector')], 'status' => false], 420);
        }
        $tagId = $sector->id;

        $collections = AppCollection::whereNotNull('published_content')->where('status', PublishStatus::Published)->latest()->whereJsonContains('tags', $tagId);

        if ($type) {
            $collections = $collections->where('collection_type', CollectionType::getValue($type));
        }
        $collections = $collections->get();
        $isPaginate = false;
        $collections = LiveClassHelper::checkCollectionIsBought($isPaginate, $collections);

        return  new WebDataCollection($collections);

        // return $datas->groupBy('collection_type');
    }

    public function getSearchResults(Request $request)
    {
        $collections = AppCollection::whereNotNull('published_content')->where('status', PublishStatus::Published);
        $searchText = $request->text;
        $endDate = now()->format('Y/m/d');

        $collections = $collections->where(function ($query) use ($searchText, $endDate) {
            $query = $query->whereNotNull('published_content')->whereIn('collection_type', [
                CollectionType::caseStudy, CollectionType::blogs,
            ]);

            if ($searchText) {
                $query = $query->whereRaw("UPPER(published_content) LIKE '%".strtoupper($searchText)."%'");
            }
        });

        $collections = $collections->orWhere(function ($query) use ($searchText, $endDate) {
            $query = $query->whereNotNull('published_content')->whereIn('collection_type', [
                CollectionType::events, CollectionType::workshops, CollectionType::classes,
            ])->where('published_content->end_date', '>=', $endDate);

            if ($searchText) {
                $query = $query->whereRaw("UPPER(published_content) LIKE '%".strtoupper($searchText)."%'");
            }
        });

        // if ($searchText) {
        //     $collections = $collections->whereRaw("UPPER(published_content) LIKE '%" . strtoupper($searchText) . "%'");
        // }

        $collections = $collections->latest()->get();

        $categories = Category::where('name', 'like', "%{$searchText}%")->get();

        return ['collections' => new WebDataCollection($collections), 'categories' => $categories];
    }

    public function getSettingsData(Request $request)
    {
        $datas = WebSetting::all();
        $allData = [];
        foreach ($datas as $data) {
            $data->setting_value = json_decode($data->setting_value);
            $allData[$data->setting_key] = $data->setting_value;
        }

        return $allData;
    }

    public function getCategoryGroupList($slug)
    {
        $categories = Category::with('medias')->whereHas('categoryGroups', function ($query) use ($slug) {
            $query->where('slug', $slug);
        })->get();
        $categories->map(function ($cat) {
            if ($cat->medias && count($cat->medias) > 0) {
                $cat['featured_image'] = Storage::disk('s3')->url($cat->medias[0]->url);
            }
            unset($cat['medias']);
        });

        return $categories;
    }

    public function getTagGroupList($slug, $type = null)
    {
        $groupTags = new collection;

        $tags = Tag::with('tagGroups')->get();

        $tags->map(function ($data, $key) use ($slug, $groupTags) {
            if ($data->tagGroups && count($data->tagGroups) > 0) {
                foreach ($data->tagGroups as $group) {
                    if ($slug == $group->slug) {
                        $groupTags->push($data);
                    }
                }
            }
        });

        return ['tags' => $tags, 'groupTags' => $groupTags];
    }

    public function getFilterCollectionEvents(Request $request)
    {
        $endDate = now()->format('Y/m/d');
        $datas = AppCollection::whereIn('collection_type', [CollectionType::events, CollectionType::workshops, CollectionType::classes]);
            // ->where('status', PublishStatus::Published)->whereNotNull('published_content')
            // ->where('published_content->end_date', '>=', $endDate)->with(['product.prices.discounts', 'product.productReviews']);

        if (isset($request->collection_type) && $request->collection_type) {
            $datas = $datas->whereIn('collection_type', $request->collection_type);
        }
        $tags = [];
        $event_types = [];

        // if (isset($request->tags) && $request->tags) {
        //     $tags =
        //         array_map(function ($tag) {
        //             return $tag['id'];
        //         }, $request->tags);
        // }
        // if (count($tags) > 0) {
        //     foreach ($tags as $key => $tag) {
        //         if ($key == 0) {
        //             $datas =  $datas->whereJsonContains('tags', $tag);
        //         }
        //         $datas =  $datas->orWhereJsonContains('tags', $tag);
        //     }
        // }

        if ($request->start_date) {
            $datas = $datas->where('published_content->end_date', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $datas = $datas->where('published_content->start_date', '<=', $request->end_date);
        }

        $datas = $datas->latest()->get();

        if (isset($request->recommended) && $request->recommended) {
            $datas = $datas->filter(function ($data, $key) use ($request) {
                if (isset($data->is_recommended) && $data->is_recommended) {
                    if ($data->is_recommended == $request->recommended) {
                        return $data;
                    }
                }
            });
        }

        if (isset($request->tags) && count($request->tags) > 0) {
            $datas = $datas->filter(function ($data, $key) use ($request) {
                if ($data->tags != null) {
                    $decodeJson = json_decode($data->tags);
                    $tagIds = $request->tags;
                    $diffArr = array_intersect($decodeJson, $tagIds);
                    if (count($diffArr) > 0) {
                        return $data;
                    }
                }
            });
        }

        if (isset($request->categories) && count($request->categories) > 0) {
            $datas = $datas->filter(function ($data, $key) use ($request) {
                if ($data->categories != null) {
                    $decodeJson = json_decode($data->categories);
                    $catIds = $request->categories;
                    $diffArr = array_intersect($decodeJson, $catIds);
                    if (count($diffArr) > 0) {
                        return $data;
                    }
                }
            });
        }

        foreach ($datas as $data) {
            $data->published_content = json_decode($data->published_content);
            $data['ischeck'] = true;
        }

        if ($request->city) {
            $datas = $datas->filter(function ($data, $key) use ($request) {
                if (isset($data->published_content->location)) {
                    $cityName = $data->published_content->location;

                    foreach ($request->city as $key => $data) {
                        if ($data == $cityName) {
                            return $data;
                        }
                    }
                }
            });
        }

        $datas->all();
        $isPaginate = false;
        $events = LiveClassHelper::checkCollectionIsBought($isPaginate, $datas);

        $events = new WebDataCollection($events);
        if ($request->all_events) {
            $cities = $datas->map(
                function ($data) use ($request) {
                    if (isset($data->published_content->country) && $data->published_content->country) {
                        if (isset($data->published_content->location) && $data->published_content->location) {
                            return $data->published_content->location;
                        }
                    }
                }
            );
            $cityArray = array_unique(array_filter($cities->all()));
            $filterCities = [];
            foreach ($cityArray as $key => $value) {
                $filterCities[] = $value;
            }

            return ['events' => $events, 'cities' => $filterCities];
        } else {
            return $events;
        }
    }

    public function getAllSuggestedCollection($count)
    {
        $datas = AppCollection::whereNotNull('published_content')
            ->where('published_content->is_suggested', true)
            ->where('status', PublishStatus::Published)
            ->orderBy('updated_at', 'DESC')
            ->latest();
        if ($count) {
            $datas = $datas->take($count);
        }
        $datas = $datas->get();
        $isPaginate = false;
        $events = LiveClassHelper::checkCollectionIsBought($isPaginate, $datas);

        return new WebDataCollection($events);
    }

    public function filterParticularCollections($type, Request $request)
    {
        $case_study_top = config('app.case_study_top'); // case study slug

        $datas = AppCollection::where('collection_type', CollectionType::getValue($type))
            ->whereNotNull('published_content');

        $mobile = $request->mobile;

        if ($request->search) {
            $datas = $datas->where('title', 'LIKE', '%'.$request->search.'%');
        }
        if ($request->tag) {
            $tag = Tag::where('slug', 'like', "%{$request->tag}%")->first();
            $datas = $datas->whereJsonContains('tags', $tag->id);
        }

        if (isset($request->sequence) && $request->sequence) {
            $datas = $datas->orderByRaw("CAST(JSON_EXTRACT(published_content, '$.read_time') AS UNSIGNED)");
        // \App\User::orderByRaw("CAST(JSON_EXTRACT(published_content, '$.read_time') AS UNSIGNED)")->get();
        } else {
            $datas = $datas->latest();
        }
        $isPaginate = false;
        if (isset($request->maxRows) && $request->maxRows) {
            $datas = $datas->paginate($request->maxRows);
            $isPaginate = true;
        } else {
            $datas = $datas->get();
        }

        if (CollectionType::getValue($type) == CollectionType::caseStudy) {
            $case_study_top_el = AppCollection::where('collection_type', CollectionType::getValue($type))
                ->where('status', PublishStatus::Published)
                ->whereNotNull('published_content')
                ->where('slug', $case_study_top)->get();
            // return $case_study_top_el;
            $datas = $datas->diff($case_study_top_el); // remove from collection
            $datas = $datas->prepend($case_study_top_el->first()); // add on top in collection
        }

        if (isset($request->category) && $request->category) {
            $category = Category::where('slug', 'like', "%{$request->category}%")->first();

            $datas = $datas->filter(function ($data, $key) use ($category) {
                if ($data->categories != null) {
                    $diffArr = [];
                    $decodeJson = json_decode($data->categories);
                    $catId[] = $category->id;
                    $diffArr = array_intersect($decodeJson, $catId);
                    if (count($diffArr) > 0) {
                        return $data;
                    }
                }
            });
        }

        if (isset($request->categories) && count($request->categories) > 0) {
            $datas = $datas->filter(function ($data, $key) use ($request) {
                if ($data->categories != null) {
                    $diffArr = [];
                    $decodeJson = json_decode($data->categories);
                    $eventIds = $request->categories;
                    $diffArr = array_intersect($decodeJson, $eventIds);
                    if (count($diffArr) > 0) {
                        return $data;
                    }
                }
            });
        }

        foreach ($datas as $data) {
            $data->published_content = json_decode($data->published_content);
            $data['user_id'] = null;
            $data['ischeck'] = true;
            if ($mobile) {
                $data['mobile'] = true;
            }
            if ($request->user_id) {
                $data['user_id'] = $request->user_id;
            }
        }

        if ($request->years) {
            $datas = $datas->filter(function ($data, $key) use ($request) {
                if (isset($data->published_content->date) && $data->published_content->date) {
                    $currentYear = Carbon::parse($data->published_content->date)->year;
                    $requestedYears = $request->years;
                    if (in_array($currentYear, $requestedYears)) {
                        return $data;
                    }
                }
            });
        }

        if ($request->months) {
            $datas = $datas->filter(function ($data, $key) use ($request) {
                if (isset($data->published_content->date) && $data->published_content->date) {
                    $currentMonth = Carbon::parse($data->published_content->date)->month;
                    foreach ($request->months as $month) {
                        if ($currentMonth == MonthName::getValue($month)) {
                            return $data;
                        }
                    }
                }
            });
        }

        if ($request->cities) {
            $datas = $datas->filter(function ($data, $key) use ($request) {
                if (isset($data->published_content->location)) {
                    $cityName = $data->published_content->location;
                    $requestedCities = $request->cities;
                    if (in_array($cityName, $requestedCities)) {
                        return $data;
                    }
                }
            });
        }

        $datas->all();

        $events = LiveClassHelper::checkCollectionIsBought($isPaginate, $datas);
        $events = new WebDataCollection($events);

        if ($request->with_city) {
            $cities = $datas->map(
                function ($data) use ($request) {
                    if (isset($data->published_content->location) && $data->published_content->location) {
                        if ($data->published_content->location != null) {
                            return $data->published_content->location;
                        }
                    }
                }
            );

            $cityArray = array_unique(array_filter($cities->toArray()));
            $categories = [];
            if ($request->slug) {
                $slug = $request->slug;
                $categories = Category::whereHas('categoryGroups', function ($query) use ($slug) {
                    $query->where('slug', $slug);
                })->get();
            }

            return ['data' => $events, 'cities' => $cities, 'categories' => $categories];
        } else {
            return $events;
        }
    }

    public function filterParticularCollectionsMobile($type, Request $request)
    {
        $case_study_top = config('app.case_study_top');
        $case_study_id = config('app.case_study_id'); // case study slug

        $datas = AppCollection::where('collection_type', CollectionType::getValue($type))
            ->whereNotNull('published_content');

        $mobile = $request->mobile;
        $is_featured = $request->is_featured;

        if (isset($request->sequence) && $request->sequence) {
            $datas = $datas->orderByRaw("CAST(JSON_EXTRACT(published_content, '$.read_time') AS UNSIGNED)");
        } else {
            $datas = $datas->orderByRaw("id = $case_study_id DESC");
        }

        if ($is_featured) {
            $datas = $datas->where('published_content->is_featured', 1);
        }

        if (isset($request->maxRows) && $request->maxRows) {
            $datas = $datas->paginate($request->maxRows);
        } else {
            $datas = $datas->get();
        }

        $isPaginate = false;

        if (isset($request->maxRows) && $request->maxRows) {
            $datas->getCollection()->transform(function ($data) use ($mobile, $request) {
                $data->published_content = json_decode($data->published_content);
                $data['user_id'] = null;
                $data['ischeck'] = true;
                if ($mobile) {
                    $data['mobile'] = true;
                }
                if ($request->user_id) {
                    $data['user_id'] = $request->user_id;
                }

                return $data;
            });
            $isPaginate = true;
        } else {
            foreach ($datas as $data) {
                $data->published_content = json_decode($data->published_content);
                $data['user_id'] = null;
                $data['ischeck'] = true;
                if ($mobile) {
                    $data['mobile'] = true;
                }
                if ($request->user_id) {
                    $data['user_id'] = $request->user_id;
                }
            }
            $datas->all();
        }

        $events = LiveClassHelper::checkCollectionIsBought($isPaginate, $datas);

        $events = new WebDataCollection($datas);

        return $events;
    }

    /***
     * Categories List for a collection
     */
    public function getCollectionTypeCategories(Request $request)
    {
        $categories = Category::with('publishedCollections');

        if ($request->type) {
            $type = CollectionType::getValue($request->type);
            $categories = $categories->whereHas('collectionPivot', function ($query) use ($type) {
                $query->where('collection_type', $type);
            });
        }

        $categories = $categories->latest()->get();

        $categories->map(function ($item) use ($request) {
            $item->published_count = $request->type ? count($item->publishedCollections->where('collection_type', CollectionType::getValue($request->type))) : count($item->publishedCollections);
            unset($item->publishedCollections);
        });

        return $categories->where('published_count', '>', 0)->sortBy('published_count');
    }

    /**
     * Tags List for a collection.
     */
    public function getCollectionTypeTags(Request $request)
    {
        $tags = Tag::withCount('publishedCollections');

        if ($request->type) {
            $type = CollectionType::getValue($request->type);
            $tags = $tags->whereHas('collectionPivot', function ($query) use ($type) {
                $query->where('collection_type', $type);
            });
        }
        // if ($request->sortByCount) {
        //     $tags = $tags->orderBy('published_collections_count', 'desc');
        // }

        $tags = $tags->latest()->get();

        $tags->map(function ($item) use ($request) {
            $item->published_count = $request->type ? count($item->publishedCollections->where('collection_type', CollectionType::getValue($request->type))) : count($item->publishedCollections);
            unset($item->publishedCollections);
        });

        return $tags;
    }

    public function getCollectionDataList($type, Request $request)
    {
        $datas = AppCollection::where('collection_type', CollectionType::getValue($type))
            ->whereNotNull('published_content');

        $mobile = $request->mobile;

        if ($request->search) {
            $datas = $datas->where('title', 'LIKE', '%'.$request->search.'%');
        }
        if ($request->tag) {
            $tag = Tag::where('slug', 'like', "%{$request->tag}%")->first();
            $datas = $datas->whereJsonContains('tags', $tag->id);
        }

        if (isset($request->sequence) && $request->sequence) {
            $datas = $datas->orderByRaw("CAST(JSON_EXTRACT(published_content, '$.read_time') AS UNSIGNED)");
        } else {
            $datas = $datas->latest();
        }

        if ($request->tags) {
            $datas = $datas->where(function ($q) use ($request) {
                foreach ($request->tags as $key => $tag) {
                    if ($key == 0) {
                        $q = $q->whereJsonContains('tags', $tag);
                    } else {
                        $q = $q->orWhereJsonContains('tags', $tag);
                    }
                }
            });
        }

        if ($request->categories) {
            $datas = $datas->where(function ($q) use ($request) {
                foreach ($request->categories as $key => $tag) {
                    if ($key == 0) {
                        $q = $q->whereJsonContains('categories', $tag);
                    } else {
                        $q = $q->orWhereJsonContains('categories', $tag);
                    }
                }
            });
        }

        $isPaginate = false;

        if (isset($request->maxRows) && $request->maxRows) {
            $datas = $datas->paginate($request->maxRows);
            $isPaginate = true;
        } else {
            $datas = $datas->get();
        }

        $collections = LiveClassHelper::checkCollectionIsBought($isPaginate, $datas);

        return new WebDataCollection($collections);
    }
}
