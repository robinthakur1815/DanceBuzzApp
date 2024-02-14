<?php

namespace App\Http\Controllers;

use DB;
use App\Tag;
use App\User;
use App\Story;
use Validator;
use App\Vendor;
use App\Comment;
use App\Product;
use App\Category;
use Carbon\Carbon;
use App\SpamReport;
use App\Enums\RoleType;
use App\Enums\UserRole;
use App\Jobs\PublishBlog;
use App\CollectionVersion;
use App\Helpers\WebHelper;
use App\Like as LikeModel;
use App\Helpers\SlugHelper;
use App\Helpers\UserHelper;
use App\TagCollectionPivot;
use App\Enums\PublishStatus;
use App\Helpers\ImageHelper;
use App\Http\Resources\Blog;
use Illuminate\Http\Request;
use App\Enums\CollectionType;
use App\Exports\StoriesExport;
use App\Model\Partner\Service;
use App\Enums\NotificationType;
use App\Model\Partner\Location;
use App\CategoryCollectionPivot;
use App\Collection as DataModel;
use App\Model\NotificationModel;
// use App\Helpers\NotificationHelper;
use App\Enums\ClassPublishStatus;
use App\Helpers\CollectionHelper;
use App\Model\Partner\VendorClass;
use Alaouy\Youtube\Facades\Youtube;
use Maatwebsite\Excel\Facades\Excel;
use App\Adapters\DynamicUrl\DynamicUrlService;
use App\Http\Resources\BlogCollection;
use App\Http\Resources\CommentCollection;
use App\Http\Resources\Blog as BlogResource;
use App\Http\Resources\StudentStoryResource;
use App\Model\Partner\State as PartnerState;
use App\Http\Resources\StudentStoryResourceCollection;
use Illuminate\Support\Facades\DB as FacadesDB;


class CollectionController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if($user->role_id == RoleType::SuperAdmin and $request->vendor_id){
            $vendor = Vendor::find($request->vendor_id);
            $user = User::find($vendor->created_by);

        }
        // if (!$user->can('view', DataModel::class)) {
        //     return response(['errors' => ['subtask' => ["user is not authorised"]], 'status' => false, 'message' => ''], 403);
        // }

        $blogs = DataModel::with('medias')->with('vendor', 'product.packages')->withCount('stories')->latest();
           
        if ($request->type == CollectionType::campaignsType && $request->vendor_type == 1 ) {
            $blogs = $blogs->where('collection_type', CollectionType::campaignsType)
                           ->where('status',PublishStatus::Published)
                             ->where('saved_content->is_for_vendor', true)->get();
            return new BlogCollection($blogs);                 
        }elseif ($request->type == CollectionType::campaignsType && $request->vendor_type == 2 ) {
            $blogs = $blogs->where('collection_type', CollectionType::campaignsType)
                             ->where('status',PublishStatus::Published)
                             ->where('saved_content->is_for_school', true)->get();
            return new BlogCollection($blogs);                 
        }elseif($request->type == CollectionType::campaignCategories && ($user->role_id == RoleType::School || $user->role_id == RoleType::Vendor || $user->role_id == RoleType::VendorStaff ||  $user->role_id == RoleType::SchoolRepresentative)){
            $blogs = $blogs->where('collection_type', CollectionType::campaignCategories)
                           ->where('status',PublishStatus::Published)->get();
            return new BlogCollection($blogs);                 
        }
        
        else{
            $blogs = $blogs->where('collection_type', $request->type);
        }
        
        if ($user->role_id != UserRole::SuperAdmin && $user->role_id != UserRole::Approver) {
            $blogs = $blogs->where('created_by', $user->id);
        }
        if(($user->role_id == RoleType::VendorStaff || $user->role_id == RoleType::SchoolRepresentative) && $request->type == CollectionType::sponsers){
            $vendorId = DB::connection('partner_mysql')->table('staff_vendor')->where('user_id', $user->id)->pluck('vendor_id');
            $vendor = Vendor::where('id', $vendorId)->first();
            $blogs = $blogs->where('created_by', $user->id)
                            ->orWhere('created_by', $vendor->created_by);
            $blogs = $blogs->where('collection_type', $request->type);
        }
        if(($user->role_id == RoleType::Vendor || $user->role_id == RoleType::School) && $request->type == CollectionType::sponsers){
            $userIds = [];
            $vendor = Vendor::where('created_by', $user->id)->first();
            $userIds = DB::connection('partner_mysql')->table('staff_vendor')->where('vendor_id', $vendor->id)->pluck('user_id')->toArray();
            $blogs = $blogs->where('created_by', $user->id)
                            ->orWhereIn('created_by', $userIds);
            $blogs = $blogs->where('collection_type', $request->type);
        }
        


        if ($request->search) {
            $blogs = $blogs->where('title', 'like', "%{$request->search}%");
        }

        if ($request->isTrashed) {
            $blogs = $blogs->onlyTrashed();
        }
        
        
        if ($request->status) {
            $blogs = $blogs->where('status', $request->status);
        }
        if ($request->campaignType) {
            $blogs = $blogs->where('saved_content->campaign_type->id', $request->campaignType);
        }

        if ($request->onlyPublished) {
            $blogs = $blogs->whereNotNull('published_at');
        }
        if( isset($request->is_private)  and $request->type == CollectionType::campaigns){
            $blogs = $blogs->where('is_private', $request->is_private);
        }

        if ($request->maxRows) {
            $blogs = $blogs->paginate($request->maxRows);
        } else {
            $blogs = $blogs->get();
        }
        //     foreach($blogs as $blog){
        //         $decrypt = json_decode($blog , true);
        //         var_dump($decrypt);
        //     // var_dump(json_decode($blog->published_content , true));
        // }
        // if (!$request->is_filtered) {
        //     return $blogs;
        // } else {
        return new BlogCollection($blogs);
        // }
    }
    public function CampaignsCsv(Request $request)
    {
            $user = auth()->user();
            if($user->role_id == RoleType::SuperAdmin and $request->vendor_id){
                $vendor = Vendor::find($request->vendor_id);
                $user = User::find($vendor->created_by);
    
            }
            // if (!$user->can('view', DataModel::class)) {
            //     return response(['errors' => ['subtask' => ["user is not authorised"]], 'status' => false, 'message' => ''], 403);
            // }
    
            $blogs = DataModel::with('medias')->with('vendor', 'product.packages')->withCount('stories')->latest();
            
    
            if ($request->type) {
                $blogs = $blogs->where('collection_type', $request->type);
            }
    
            if ($user->role_id != UserRole::SuperAdmin && $user->role_id != UserRole::Approver) {
                $blogs = $blogs->where('created_by', $user->id);
            }
    
            if ($request->search) {
                $blogs = $blogs->where('title', 'like', "%{$request->search}%");
            }
    
            if ($request->isTrashed) {
                $blogs = $blogs->onlyTrashed();
            }
    
            if ($request->status) {
                $blogs = $blogs->where('status', $request->status);
            }
            if ($request->campaignType) {
                $blogs = $blogs->where('saved_content->campaign_type->id', $request->campaignType);
            }
    
            if ($request->onlyPublished) {
                $blogs = $blogs->whereNotNull('published_at');
            }
            if($request->is_private and $request->type == CollectionType::campaigns){
                $blogs = $blogs->where('is_private', $request->is_private);
            } else {
                $blogs = $blogs->get();
            } 
            
            $bloges = Blog::collection($blogs)->resolve();
            //return $bloges;
        $headers = [
            'Name',
            'Campaign Type',
            'Enrolled student',
            'Updated BY',
            'status',
            'Updated At',
            
        ];
        $bodies = [];
        foreach ($bloges as $data) {
            $body = [
                $data['title'],
                $data['type'],
                $data['stories_count'],
                $data['published_by'],
                PublishStatus::getkey($data['status']),
                $data['created_at']->format('d/m/Y h:i A'),
            ];
            array_push($bodies, $body);
        }
        return Excel::download(new StoriesExport($headers, $bodies), 'Campaigns.csv');
    }
    public function CampaignSponsarsCsv(Request $request)
    {
            $user = auth()->user();
            if($user->role_id == RoleType::SuperAdmin and $request->vendor_id){
                $vendor = Vendor::find($request->vendor_id);
                $user = User::find($vendor->created_by);
    
            }
            $blogs = DataModel::with('medias')->with('vendor', 'product.packages')->withCount('stories')->latest();
            if ($request->type) {
                $blogs = $blogs->where('collection_type', $request->type);
            }
    
            if ($user->role_id != UserRole::SuperAdmin && $user->role_id != UserRole::Approver) {
                $blogs = $blogs->where('created_by', $user->id);
            }
    
            if ($request->search) {
                $blogs = $blogs->where('title', 'like', "%{$request->search}%");
            }
    
            if ($request->isTrashed) {
                $blogs = $blogs->onlyTrashed();
            }
    
            if ($request->status) {
                $blogs = $blogs->where('status', $request->status);
            }
            if ($request->campaignType) {
                $blogs = $blogs->where('saved_content->campaign_type->id', $request->campaignType);
            }
    
            if ($request->onlyPublished) {
                $blogs = $blogs->whereNotNull('published_at');
            }
            if($request->is_private and $request->type == CollectionType::campaigns){
                $blogs = $blogs->where('is_private', $request->is_private);
            } else {
                $blogs = $blogs->get();
            }
        $headers = [
            'Name',
            'Created BY',
            'status',
            'Created At',
            
        ];
        $bodies = [];
        foreach ($blogs as $data) {
            $Items =  json_decode($data->saved_content ,true) ;
            $body = [
                $data->title,
                $data  && isset($Items['current_user']['name']) ? $Items['current_user']['name'] :"N/A",
                PublishStatus::getkey($data->status),
                $data->created_at->format('d/m/Y h:i A'),
            ];
            array_push($bodies, $body);
        }
        return Excel::download(new StoriesExport($headers, $bodies), 'sponsars.csv');
    }
    public function StudentStory(Request $request)
    {   
            $classid= $request->classid;
            $students =Story::where('campaign_id', $classid)->select('student_user_id')->get();
            $studentstory = User::wherein('id', $students)->paginate();
            return $studentstory;
        }
    public function StudentStoryCsv(Request $request)
    {   
        $classid= $request->classid;
        $students =Story::where('campaign_id', $classid)->select('student_user_id')->get();
            $studentstory = User::wherein('id', $students)->get();
           
        $headers = [
            'Id',
            'Name',
            'UserName',
            'Email',
            'phone',
            'status',
            'Regesterat',
        ];
        $bodies = [];
        foreach ($studentstory as $data) {
            $body = [
               $data->id,
               $data->name,
               $data->username,
               $data->email,
               $data->phone,
               $data->is_active,
               $data->created_at,
            ];
            array_push($bodies, $body);
        }
       return Excel::download(new StoriesExport($headers, $bodies), 'StudentStoriesCSv.csv');
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if($user->role_id == RoleType::SuperAdmin and $request->vendor_id){
            $vendor = Vendor::find($request->vendor_id);
            $user = User::find($vendor->created_by);

        }
        if (!$user->can('create', DataModel::class)) {
            return response(['errors' => ['subtask' => ['user can not create the following task']], 'status' => false, 'message' => ''], 403);
        }

        $vendor_class_id = null;
        $vendor_id = null;

        $location_id = isset($request->location_id) ? $request->location_id : null;

        $vendorClassStatus = ClassPublishStatus::Published;

        if ($request->status == PublishStatus::Draft || $request->status == PublishStatus::Submitted) {
            $vendorClassStatus = ClassPublishStatus::Draft;
        }

        if ($request->type == CollectionType::classes && $request->vendor) {
            $result = $this->validateClass($request->start_date, $request->end_date, $request);
            $errors = $result['errors'];

            if (!$result['status']) {
                return response(['errors' =>  ['alreadyExist' => [$errors]], 'status' => false, 'message' => ''], 422);
            }

            $createdClass = $this->saveVendorClass($user, $request, 1, null, $vendorClassStatus);

            $vendor_class_id = $createdClass['class']['id'];
            // $vendor_id = $request->vendor;
            $location_id = $createdClass['location_id'];
        } elseif ($request->type == CollectionType::classes && !$request->vendor) {
            $vendorClassData = CollectionHelper::kcVendorData();

            $datas = [

                'vendor_id'   => $vendorClassData['vendor_id'],
                'user_id'     => $vendorClassData['user_id'],
                'location_id' => $vendorClassData['location_id'],
                'service_id'  => $vendorClassData['service_id'],
                'owner_id'  => $vendorClassData['user_id'],
                'name' => $request->title,
                'created_by' => $user->id,
                'start_date' =>  Carbon::createFromFormat('Y/m/d', $request->start_date),
                'start_time'           => Carbon::createFromFormat('h:i:s', $request->start_time),
                'end_time'             => Carbon::createFromFormat('h:i:s', $request->end_time),
                'frequencey_per_month' => $request->frequency,
                'is_publish'            => true,

            ];

            $vendor_id = $vendorClassData['vendor_id'];

            $_class = VendorClass::create($datas);

            $vendor_class_id = $_class->id;
        }

        if ($vendorClassStatus == ClassPublishStatus::Published) {
            CollectionHelper::createActivityLog($vendor_class_id, 'create', $user->id);
        }

        $slugHelper = new SlugHelper();

        $slug = $slugHelper->slugify($request->title);
        $slugs = DataModel::where('slug', $slug)->where('collection_type', $request->type)->first();

        if ($slugs) {
            return response(['errors' =>  ['alreadyExist' => ['already Exist with same name.']], 'status' => false, 'message' => ''], 422);
        }

        if ($location_id) {
            $request['location_id'] = $location_id;
        }

        $saved_content = $this->saveRequestContent($request);

        $attributes = [
            'title'           => $request->title,
            'slug'            => $slug,
            'created_by'      => $user->id,
            'updated_by'      => $user->id,
            'status'          => PublishStatus::Draft,
            'saved_content'   => json_encode($saved_content),
            'collection_type' => $request->type,
            'vendor_id'       => $vendor_id,
            
        ];
        if($request->type == CollectionType::campaigns){
            $attributes['is_private'] = $request->is_private;
        }

        if ($request->type == CollectionType::classes && isset($request->vendor_services) && isset($request->vendor_services['id'])) {
            $attributes['services'] = [(int) $request->vendor_services['id']];
        }

        $attributes['vendor_class_id'] = $vendor_class_id;

        if (isset($request->vendor) && $request->vendor && $request->vendor['id']) {
            $attributes['vendor_id'] = $request->vendor['id'];
        }

        if (isset($request->is_featured) && $request->is_featured) {
            $attributes['is_featured'] = $request->is_featured;
        }

        if (isset($request->is_recommended) && $request->is_recommended) {
            $attributes['is_recommended'] = $request->is_recommended;
        }

        if ($request->type != CollectionType::campaigns && $request->type != CollectionType::campaignCategories) {
            if (isset($request->categories) && $request->categories) {
                $categoryIds = array_map(function ($cat) {
                    return isset($cat['id']) ? $cat['id'] : null;
                }, $request->categories);
                $attributes['categories'] = json_encode($categoryIds);
            }
        } elseif ($request->type == CollectionType::campaigns) {
            if (isset($request->categories) && $request->categories) {
                $categoryIds = array_map(function ($cat) {
                    return isset($cat['id']) ? $cat['id'] : null;
                }, $request->categories);
                $attributes['categories'] = json_encode($categoryIds);
            }
        } elseif ($request->type == CollectionType::campaignCategories) {
            if (isset($request->sub_categories) && $request->sub_categories) {
                $categoryIds = array_map(function ($cat) {
                    return isset($cat['id']) ? $cat['id'] : null;
                }, $request->sub_categories);
                $attributes['categories'] = json_encode($categoryIds);
            }
        }

        $tagIds = [];
        if (isset($request->tags) && $request->tags) {
            $tagIds = array_map(function ($tag) {
                return $tag['id'];
            }, $request->tags);
        }
        $attributes['tags'] = json_encode($tagIds);
        // array_unique(array_merge($tagIds, $otherTagIds))

        $blog = DataModel::create($attributes);

        $blog->load('medias');

        /*
         * Creating Pivot for categories
         */
        if (isset($request->categories) && $request->categories && $request->type != CollectionType::campaigns) {
            $categories = array_map(function ($cat) {
                return $cat['id'];
            }, $request->categories);

            foreach ($categories  as $catId) {
                $blog->categoryPivot()->create(['category_id' => $catId, 'collection_id' => $blog->id, 'collection_type' => $blog->collection_type]);
            }
        }

        /*
         * Creating Pivot for tags
         */
        if (isset($request->tags) && $request->tags) {
            $tagIds = array_map(function ($tag) {
                return $tag['id'];
            }, $request->tags);
            foreach ($tagIds  as $tagId) {
                $blog->tagPivot()->create(['tag_id' => $tagId, 'collection_id' => $blog->id, 'collection_type' => $blog->collection_type]);
            }
        }

        if (isset($request->meta) && $request->meta) {
            $data = [
                'meta' => json_encode($request->meta),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ];
            $seo = $blog->seos()->create($data);
        }

        if (isset($request->featured_image) && $request->featured_image) {
            $blog->mediables()->create([
                'media_id'   => $request->featured_image['id'],
                'name'       => $request->featured_image['name'],
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        }
        if (isset($request->file) && $request->file) {
            $blog->fileables()->create([
                'file_id'    => $request->file['id'],
                'name'       => $request->file['filename'],
                'created_by' => $user->id,
            ]);
        }

        // if ($request->status == PublishStatus::Published) {
        //     if (!$user->can('publish', $blog)) {
        //         // $blog->forceDelete();
        //         return response(['errors' => ['authError' => ["User is not authorized publishing the collection. Saved as Draft"]], 'status' => false, 'message' => ''], 422);
        //     }
        //     $data = [
        //         'published_content' => json_encode($saved_content),
        //         'status'            => PublishStatus::Published,
        //         'published_by'      => $user->id,
        //         'published_at'      => Carbon::now(),
        //     ];

        //     $blog->update($data);
        //     $blog->refresh();
        //     // PublishBlog::dispatch($blog, $user)->onQueue('publish');
        // }

        if ($request->status == PublishStatus::Published) {
            if (!$user->can('publish', $blog)) {
                // $blog->forceDelete();
                return response(['errors' => ['authError' => ['User is not authorized publishing the collection. Saved as Draft']], 'status' => false, 'message' => ''], 422);
            }
            $data = [
                'published_content' => json_encode($saved_content),
                'status'            => PublishStatus::Published,
                'published_by'      => $user->id,
                'published_at'      => Carbon::now(),
            ];

            $blog->update($data);
            if (in_array($request->type, [CollectionType::campaigns, CollectionType::classes, CollectionType::events, CollectionType::workshops])) {
                // NotificationHelper::collection($blog); // send notification
                // $this->addUpdateProduct( $blog);
            }

            $blog->refresh();
        }
        $this->addUpdateProduct($blog);
        if ($request->status == PublishStatus::Submitted) {
            $data = [
                'status'            => PublishStatus::Submitted,
            ];
            $blog->update($data);
            $blog->refresh();
        }

       if( $blog->collection_type == CollectionType::campaigns){
        $dynamic = new DynamicUrlService();
        $dynamic->createDynamicUrlForCampaign($blog->id);
       }

        return new BlogResource($blog);
    }

    private function saveRequestContent($request)
    {
        $saved_content = $request->all();

        return $saved_content;
    }

    public function getCollectionDetails($id)
    {
        $user = auth()->user();
        $blog = DataModel::with('seos')->find($id);
        if (!$blog) {
            return response(['errors' => 'Collection not Found', 'status' => false, 'message' => ''], 422);
        }
        if (!$user->can('editCollection', $blog)) {
            return response(['errors' => ['authError' => ['User is not authorized for this action']], 'status' => false, 'message' => ''], 422);
        }
        $blog->details = true;

        if ($blog->collection_type == CollectionType::classes || $blog->collection_type == CollectionType::classDeck) {
            $blog->statusPublished = CollectionHelper::checkPublishedStatus($blog->vendor_class_id);
        }

        return new BlogResource($blog);
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();

        $location_id = isset($request->location_id) ? $request->location_id : null;

        $vendorClassStatus = ClassPublishStatus::Published;

        if ($request->status == PublishStatus::Draft || $request->status == PublishStatus::Submitted) {
            $vendorClassStatus = ClassPublishStatus::Draft;
        }
        $collection = DataModel::find($id);

        if ($request->type == CollectionType::classes) {
            $vendorClassStatus = $request->status;
            $vendorResponse = CollectionHelper::updateClassApiCall($collection->vendor_class_id, $request);
            if ($vendorResponse['code'] != 200) {
                return response($vendorResponse['response'], $vendorResponse['code']);
            }
        }

        if ($vendorClassStatus == ClassPublishStatus::Published && $request->type == CollectionType::classes) {
            if ($collection->vendor_class_id) {
                CollectionHelper::createActivityLog($collection->vendor_class_id, 'update', $user->id);
            }
        }

        if ($request->type == CollectionType::classes && $request->vendor) {
            $result = $this->validateClass($request->start_date, $request->end_date, $request);
            $errors = $result['errors'];
            if (!$result['status']) {
                return response(['errors' =>  ['alreadyExist' => [$errors]], 'status' => false, 'message' => ''], 422);
            }

            $createdClass = $this->saveVendorClass($user, $request, 0, $id, $vendorClassStatus);

            $location_id = $createdClass['location_id'];
        }

        $slugHelper = new SlugHelper();
        $slug = $slugHelper->slugify($request->title);
        $slugs = DataModel::where('slug', $slug)
            ->where('collection_type', $request->type)
            ->where('id', '!=', $id)->first();
        if ($slugs) {
            return response(['errors' =>  ['alreadyExist' => ['Already Exist with same name.']], 'status' => false, 'message' => ''], 422);
        }

        $blog = DataModel::with('medias', 'categoryPivot')->find($id);
        if (!$blog) {
            return response(['errors' =>  ['notFound' => ['Collection not Found']], 'status' => false, 'message' => ''], 422);
        }

        if (!$user->can('updateAny', $blog)) {
            return response(['errors' => ['authError' => ['User is not authorized to update']], 'status' => false, 'message' => ''], 422);
        }

        if ($location_id) {
            $request['location_id'] = $location_id;
        }

        $saved_content = $this->saveRequestContent($request);

        if ($blog->status == PublishStatus::Submitted && $request->status == PublishStatus::Published) {
            $content = $this->updateSubmittedContent($blog, $request, false);
            $blog->update($content);
        }

        if ($request->status == PublishStatus::Published) {
            $collections = [NotificationType::Campaign, NotificationType::EventCollection, NotificationType::ClassCollection, NotificationType::WorkShopCollection];
            $notificationModel = NotificationModel::whereIn('data->action', $collections)->where('data->action_id', $blog->id)->first();
            if (!$notificationModel) {
                // NotificationHelper::collection($blog);
            }
        }

        if ($request->status == PublishStatus::Draft || PublishStatus::Published) {
            $attributes = [
                'title'             => $request->title,
                'slug'              => $slug,
                'updated_by'        => $user->id,
                'saved_content'     => json_encode($saved_content),
                'collection_type'   => $request->type,
                'published_at'      => null,
                'published_by'      => null,
                'status'            => $request->status,
                'published_content' => null,
            ];
            if($request->type == CollectionType::campaigns){
                $attributes['is_private'] = $request->is_private;
            }
        }

        if ($request->status == PublishStatus::Submitted) {
            $attributes = [
                'updated_by'      => $user->id,
                'saved_content'   => json_encode($saved_content),
                'collection_type' => $request->type,
                'published_at'    => null,
                'published_by'    => null,
                'status'          => PublishStatus::Submitted,
            ];
        }

        if ($request->type == CollectionType::classes && isset($request->vendor_services) && isset($request->vendor_services['id'])) {
            $attributes['services'] = [(int) $request->vendor_services['id']];
        }

        // $attributes = [
        //     'title' => $request->title,
        //     'slug'         => $slug,
        //     'updated_by' => $user->id,
        //     'status' => PublishStatus::Draft,
        //     'saved_content' => json_encode($saved_content),
        //     'collection_type' => $request->type,
        //     'published_content' => null,
        //     'published_at' => null,
        //     'published_by' => null,
        //     'vendor_id'  => null
        // ];

        if (isset($request->vendor) && $request->vendor && $request->vendor['id'] && $request->status != PublishStatus::Submitted) {
            $attributes['vendor_id'] = $request->vendor['id'];
        }

        if (isset($request->is_featured) && $request->status != PublishStatus::Submitted) {
            $attributes['is_featured'] = $request->is_featured;
        }
        if (isset($request->is_recommended) && $request->status != PublishStatus::Submitted) {
            $attributes['is_recommended'] = $request->is_recommended;
        }

        $categories = [];

        if ($request->type != CollectionType::campaigns && $request->type != CollectionType::campaignCategories && $request->status != PublishStatus::Submitted) {
            $blog->categoryPivot()->delete();
            if (isset($request->categories) && $request->categories) {
                $categories = array_map(function ($cat) {
                    return $cat['id'];
                }, $request->categories);

                foreach ($categories  as $catId) {
                    $blog->categoryPivot()->create(['category_id' => $catId, 'collection_id' => $blog->id, 'collection_type' => $blog->collection_type]);
                }
                // $blog->categoryPivot()->create(['category_id' => $categories]);
            }
            $attributes['categories'] = json_encode($categories);
        } elseif ($request->type == CollectionType::campaigns && $request->status != PublishStatus::Submitted) {
            $attributes['categories'] = null;
            if (isset($request->categories) && $request->categories) {
                $categories = array_map(function ($cat) {
                    return isset($cat['id']) ? $cat['id'] : null;
                }, $request->categories);
                $attributes['categories'] = json_encode($categories);
            }
            // $attributes['categories'] = json_encode($categories);
        } elseif ($request->type == CollectionType::campaignCategories && $request->status != PublishStatus::Submitted) {
            $attributes['categories'] = null;
            if (isset($request->sub_categories) && $request->sub_categories) {
                $categories = array_map(function ($cat) {
                    return isset($cat['id']) ? $cat['id'] : null;
                }, $request->sub_categories);
            }
            // $attributes['categories'] = json_encode($sub_categories);
        }

        if ($request->status != PublishStatus::Submitted) {
            $attributes['categories'] = json_encode($categories);
            $tagIds = [];
            $blog->tagPivot()->delete();
        }

        if (isset($request->tags) && $request->tags && $request->status != PublishStatus::Submitted) {
            $tagIds = array_map(function ($tag) {
                return $tag['id'];
            }, $request->tags);
            foreach ($tagIds  as $tagId) {
                $blog->tagPivot()->create(['tag_id' => $tagId, 'collection_id' => $blog->id, 'collection_type' => $blog->collection_type]);
            }
            $attributes['tags'] = json_encode($tagIds);
        }

        $blog->update($attributes);

        if (isset($request->meta) && $request->meta && $request->status != PublishStatus::Submitted) {
            $blog->load('seos');

            $seoData = [
                'meta' => json_encode($request->meta),
                'updated_by' => $user->id,
            ];
            if ($blog->seos != null) {
                $seo = $blog->seos()->update($seoData);
            } else {
                $seoData['created_by'] = $user->id;
                $seo = $blog->seos()->create($seoData);
            }
        }
        if ($request->featured_image && $request->status != PublishStatus::Submitted) {
            $blog->mediables()->delete();
            $blog->mediables()->create([
                'media_id'   => $request->featured_image['id'],
                'name'       => $request->featured_image['name'],
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        }
        if (isset($request->file) && $request->file && $request->status != PublishStatus::Submitted) {
            $blog->fileables()->delete();
            $blog->fileables()->create([
                'file_id'    => $request->file['id'],
                'name'       => $request->file['filename'],
                'created_by' => $user->id,
            ]);
        }

        if ($request->status == PublishStatus::Published) {
            if (!$user->can('publish', $blog)) {
                return response(['errors' => ['authError' => ['User is not authorized for publishing the Blogs']], 'status' => false, 'message' => ''], 422);
            }
            $data = [
                'published_content' => json_encode($saved_content),
                'status'            => PublishStatus::Published,
                'published_by'      => $user->id,
                'published_at'      => Carbon::now(),
            ];

            $blog->update($data);
            $blog->refresh();
            // PublishBlog::dispatch($blog, $user)->onQueue('publish');
        }

        if (in_array($request->type, [CollectionType::campaigns, CollectionType::classes, CollectionType::events, CollectionType::workshops])) {
            // NotificationHelper::collection($blog); // send notification
            $this->addUpdateProduct($blog);
        }

        return new BlogResource($blog);
    }

    public function deleteCollection(Request $request)
    {
        $user = auth()->user();
        $blogIds = $request->collectionIds;
        foreach ($blogIds as $id) {
            $blog = DataModel::with('categoryPivot', 'tagPivot')->find($id);
            if (!$blog) {
                return response(['errors' =>  ['Collection not Found'], 'status' => false, 'message' => ''], 422);
            }
            if (!$user->can('delete', $blog)) {
                return response(['errors' => ['authError' => ['User is not authorized for this action']], 'status' => false, 'message' => ''], 422);
            }
            if (CollectionType::campaignsType == $request->type) {
                $campaign = DataModel::where('collection_type', CollectionType::campaigns)
                    ->where('saved_content->campaign_type->id', $id)->first();

                if ($campaign != null) {
                    return response(['errors' => ['This campaign type is attached to a campaign'], 'status' => false, 'message' => $campaign->id], 422);
                } else {
                    $blog->delete();
                }
            }
            if (CollectionType::campaignSubCategories == $request->type) {
                $campaign = DataModel::where('collection_type', CollectionType::campaigns)
                ->whereJsonContains('saved_content->categories', ['sub_categories' =>['id' => $id] ])->first();

                if ($campaign != null) {
                    return response(['errors' => ['This Sub category is attached to a campaign'], 'status' => false, 'message' => $campaign->id], 422);
                } 
                $campaignCategory = DataModel::where('collection_type', CollectionType::campaignCategories)
                ->whereJsonContains('saved_content->sub_categories', ['id' => $id])->first();
                if ($campaignCategory != null) {
                    return response(['errors' => ['This Sub category is attached to a category'], 'status' => false,], 422);
                } else {
                    $blog->delete();
                }
           

            }
            if (CollectionType::campaignCategories == $request->type) {
                $campaign = DataModel::where('collection_type', CollectionType::campaigns)
                ->whereJsonContains('saved_content->categories', ['id' => $id])->first();

                if ($campaign != null) {
                    return response(['errors' => ['This Category is attached to a campaign'], 'status' => false, 'message' => $campaign], 422);
                } else {
                    $blog->delete();
                }
            }
            if (CollectionType::sponsers == $request->type) {
                $campaign = DataModel::where('collection_type', CollectionType::campaigns)
                ->whereJsonContains('saved_content->sponsors', ['id' => $id])->first();

                if ($campaign != null) {
                    return response(['errors' => ['This Sponsers is attached to a campaign'], 'status' => false, 'message' => $campaign->id], 422);
                } else {
                    $blog->delete();
                }
            } else {
                $blog->delete();
            }
        }
        return response(['message' =>  'Collection deleted successfully', 'status' => false], 200);
    }

    public function restoreCollection(Request $request)
    {
        $user = auth()->user();
        $blogIds = $request->collectionIds;
        foreach ($blogIds as $id) {
            $blog = DataModel::withTrashed()->with('categoryPivot', 'tagPivot')->find($id);
            if (!$blog) {
                return response(['errors' =>  ['Collection not Found'], 'status' => false, 'message' => ''], 422);
            }
            if (!$user->can('restore', $blog)) {
                return response(['errors' => ['authError' => ['User is not authorized for this action']], 'status' => false, 'message' => ''], 422);
            }
            $blog->restore();
        }

        return response(['message' =>  'Collection restored successfully', 'status' => false], 200);
    }

    public function getVersionDetails(Request $request)
    {
        $version = CollectionVersion::find($request->id);

        return $version;
    }

    public function updateCollectionStatus(Request $request)
    {
        $user = auth()->user();
        $check = true;
        $blogs = $request->collectionIds;
        foreach ($blogs as $id) {
            $blog = DataModel::find($id);
            if (!$blog) {
                return response(['errors' =>  ['Collection not Found'], 'status' => false, 'message' => ''], 422);
            }
            $saved_content = $blog->saved_content;
            if (CollectionType::campaignsType == $blog->collection_type) {
                $campaign = DataModel::where('collection_type', CollectionType::campaigns)
                    ->where('saved_content->campaign_type->id', $id)->first();
                if ($campaign != null) {
                    $check = false;
                    $error = 'This campaign type is attached to a campaign';
                } 
            }
            if (CollectionType::campaignSubCategories == $blog->collection_type) {
                $campaign = DataModel::where('collection_type', CollectionType::campaigns)
                ->whereJsonContains('saved_content->categories', ['sub_categories' =>['id' => $id] ])->first();
                if ($campaign != null) {
                    $check = false;
                     $error = 'This Sub category is attached';
                } 
                $campaignCategory = DataModel::where('collection_type', CollectionType::campaignCategories)
                ->whereJsonContains('saved_content->sub_categories', ['id' => $id])->first();
                if ($campaignCategory != null) {
                    $check = false;
                    $error = 'This Sub category is attached';
                }
                      
            }
            if (CollectionType::campaignCategories == $blog->collection_type) {
                $campaign = DataModel::where('collection_type', CollectionType::campaigns)
                ->whereJsonContains('saved_content->categories', ['id' => $id])->first();
                if ($campaign != null) {
                    $error = 'This Category is attached to a campaign';
                    $check = false;
                } 
            }
            if (CollectionType::sponsers == $blog->collection_type) {
                $campaign = DataModel::where('collection_type', CollectionType::campaigns)
                ->whereJsonContains('saved_content->sponsors', ['id' => $id])->first();
                if ($campaign != null) {
                    $error = 'This Sponsers is attached to a campaign';
                    $check = false;
                } 
            } 

            
            
            if ($check) {
                if ($blog->status == PublishStatus::Submitted && $request->status == PublishStatus::Published) {
                    $savedContent = json_decode($blog->saved_content);
                    $content = $this->updateSubmittedContent($blog, $savedContent, true);
                    $blog->update($content);
                }

                if ($request->status == PublishStatus::Published) {
                    $data = [
                        'published_content' => $saved_content,
                        'status'            => PublishStatus::Published,
                        'published_by'      => $user->id,
                        'published_at'      => Carbon::now(),
                    ];

                    $blog->update($data);
                    $blog->refresh();
                    // PublishBlog::dispatch($blog, $user)->onQueue('publish');
                } else {
                    $data = [
                        'status'            => PublishStatus::Draft,
                        'published_content' => null,
                        'published_at'      => null,
                        'published_by'      => null,
                        'updated_by'        => $user->id,
                    ];
                    $blog->update($data);
                }
            }else{
                return response(['errors' =>  $error, 'status' => false, 'message' => ''], 422);
            }
        }

        return response(['message' =>  'Status updated', 'status' => true], 200);
    }
    public function changePrivateCollectionStatus(Request $request){

        $user = auth()->user();
        $blogs = $request->collectionIds;
        foreach ($blogs as $id) {

            $blog = DataModel::find($id);
            if(!$blog)
            {
                return response(['error' => ["collection not found"], 'status' => false, 'message' => ""], 422);
            }
            if($blog and $blog->collection_type == CollectionType::campaigns){
                if($request->is_private == true){
                    $data = [
                        'is_private'     =>  true,
                        'updated_by'     =>  $user->id   

                    ];
                $blog->update($data);
                }else{
                    $data = [
                        'is_private'     =>  false,
                        'updated_by'     =>  $user->id   

                    ];
                $blog->update($data);    
                }
            }
        }

        return response(['message' =>  'Status updated', 'status' => true], 200);

    }

    public function duplicateCollection(Request $request)
    {
        $blog = DataModel::where('id', $request->id)->first();
        if (!$blog) {
            return response(['errors' => 'Collection not Found', 'status' => false, 'message' => ''], 422);
        }
        $user = auth()->user();

        if (!$user->can('create', DataModel::class)) {
            return response(['errors' => ['authError' => ['User is not authorized for this action']], 'status' => false, 'message' => ''], 422);
        }
        $attributes['title'] = $blog->title;
        $attributes['saved_content'] = $blog->published_content;
        $attributes['created_by'] = $user->id;
        $attributes['updated_by'] = $user->id;
        $attributes['is_featured'] = $blog->is_featured;
        $attributes['is_recommended'] = $blog->is_recommended;
        $attributes['collection_type'] = $blog->collection_type;
        $attributes['status'] = PublishStatus::Draft;

        $newblog = DataModel::create($attributes);

        return new BlogResource($newblog);
    }

    private function saveVendorClass($user, $request, $update, $id, $vendorClassStatus)
    {
        try {
            $userId = $user->id;
            $vendorId = $request->vendor ? $request->vendor['id'] : '';
            $vendor = Vendor::find($request->vendor['id']);
            $class = null;
            $endDate = Carbon::createFromFormat('Y/m/d', $request->end_date);

            $startDate = Carbon::createFromFormat('Y/m/d', $request->start_date);

            $location_id = null;
            $stateData = null;
            if (!$request->location_id) {
                $request->state_id = $request->state;
                $stateData = WebHelper::state($request);
                if (!$stateData) {
                    return response(['message' =>  'state not found', 'status' => true], 422);
                }
            }

            DB::transaction(function () use ($request, &$class, $userId, $vendorId, $user, $startDate, $endDate, $vendor, $update, $id, &$location_id, $vendorClassStatus, $stateData) {
                $data = [
                    'name'                 => $request->title,
                    'start_date'           => $startDate,
                    'end_date'             => $endDate,
                    'start_time'           => Carbon::createFromFormat('h:i:s', $request->start_time),
                    'end_time'             => Carbon::createFromFormat('h:i:s', $request->end_time),
                    'frequencey_per_month' => $request->frequency,
                    'created_by'           => $user->id,
                    'is_publish'           => true,
                ];

                $data['vendor_id'] = $vendorId;
                $data['owner_id'] = $vendor ? $vendor->created_by : '';
                if ($request->location_id) {
                    $data['location_id'] = $request->location_id;
                    $location_id = $request->location_id;
                } else {

                    $locationData = [
                        'user_id' => $userId,
                        'vendor_id' => $vendorId,
                        'zipcode' => $request->zipcode,
                        'city'  => $request->city,
                        'contact_email' => $vendor->contact_email,
                        'contact_phone1' => $vendor->contact_phone1,
                        'contact_phone2' => $vendor->contact_phone2 ? $vendor->contact_phone2 : $vendor->contact_phone1,
                        'address' => $request->location,
                        'state_id' => $stateData->id,
                        'is_publish' =>  $vendorClassStatus,
                    ];

                    $addedLocation = Location::create($locationData);

                    $data['location_id'] = $addedLocation->id;

                    $location_id = $addedLocation->id;
                }
                if ($update) {
                    $data['service_id'] = $request->vendor_services ? $request->vendor_services['id'] : null;
                    $class = VendorClass::create($data);
                } else {
                    $collection = DataModel::find($id);
                    $class = VendorClass::where('id', $collection->vendor_class_id)->first();
                    $class->update($data);
                }
            });

            if ($class) {
                // $class->load('location');
                return ['message' =>  'successfully class created', 'status' => true, 'class' => $class, 'location_id' => $location_id];
            }

            return response(['message' =>  'server error', 'status' => false], 500);

            return response(['message' =>  'success', 'status' => true, 'class' => $class], 201);
        } catch (\Exception $e) {
            report($e);

            return response(['message' =>  'server error', 'status' => false], 500);
        }
    }

    private function validateClass($start, $end, $request)
    {
        if (!$request->location_id) {
            if (!$request->location) {
                return ['errors' => 'Please Select a valid location', 'status' => false];
            }
            if (!$request->city) {
                return ['errors' => 'Please Select a valid city', 'status' => false];
            }

            if (!$request->state) {
                return ['errors' => 'Please Select a valid State', 'status' => false];
            }

            if (!$request->zipcode) {
                return ['errors' => 'Please Select a valid zipcode', 'status' => false];
            }
        }

        if ($request->state) {
            // $name = $request->state;
            // $stateData = PartnerState::where('name', 'like', "%${name}%")->first();
            $request->state_id = $request->state;
            $stateData = WebHelper::state($request);
            if (!$stateData) {
                return response(['message' =>  'state not found', 'status' => true], 422);
            }
        }

        // if($start == $end){
        //     return ['errors' => 'Start Date cannot be equal to end date' , 'status' => false,];
        // }
        $startDate = Carbon::createFromFormat('Y/m/d', $start);
        $now = Carbon::now();
        $currentDate = Carbon::createFromFormat('Y/m/d', $now->format('Y/m/d'));

        $endDate = $end;
        if ($endDate) {
            $endDate = Carbon::createFromFormat('Y/m/d', $end);
            $startEndDiff = $endDate->lt($startDate);
            $nowDiff = $currentDate->gt($endDate);
            if ($nowDiff) {
                return ['errors' => 'end date should be current date or future date', 'status' => false];
            }
            if ($startEndDiff) {
                return ['errors' => 'end date should not be greater than start date', 'status' => false];
            }
        }

        return ['status' => true, 'errors' => 'ssss'];
    }

    public function getVendorServices(Request $request)
    {
        if (!$request->id) {
            return  \App\Model\Partner\Service::get();
        }
        $vendor = Vendor::with('services')->where('id', $request->id)->first();
        if($vendor->vendor_type == 2){
            return  \App\Model\Partner\Service::get();
        }else{
            return $vendor['services'];
        }
       
    }

    private function updateSubmittedContent($blog, $request, $statusUpdate)
    {
        $slugHelper = new SlugHelper();

        $slug = $slugHelper->slugify($request->title);

        $user = auth()->user();

        $attributes = [
            'title' => $request->title,
            'slug' => $slug,
        ];

        if (isset($request->is_featured)) {
            $attributes['is_featured'] = $request->is_featured;
        }
        if (isset($request->is_recommended)) {
            $attributes['is_recommended'] = $request->is_recommended;
        }

        if ($statusUpdate) {
            $blog->categoryPivot()->delete();
            $categories = [];

            if (isset($request->vendor) && $request->vendor && $request->vendor->id) {
                $attributes['vendor_id'] = $request->vendor->id;
            }

            if ($request->type != CollectionType::campaigns && $request->type != CollectionType::campaignCategories) {
                $blog->categoryPivot()->delete();
                $categories = [];
                if (isset($request->categories) && $request->categories) {
                    $categories = array_map(function ($cat) {
                        return $cat->id;
                    }, $request->categories);

                    foreach ($categories  as $catId) {
                        $blog->categoryPivot()->create(['category_id' => $catId, 'collection_id' => $blog->id, 'collection_type' => $blog->collection_type]);
                    }
                }
                $attributes['categories'] = json_encode($categories);
            } elseif ($request->type == CollectionType::campaigns) {
                $attributes['categories'] = null;
                if (isset($request->categories) && $request->categories) {
                    $categoryIds = array_map(function ($cat) {
                        return isset($cat->id) ? $cat->id : null;
                    }, $request->categories);
                    $attributes['categories'] = json_encode($categoryIds);
                }
            } elseif ($request->type == CollectionType::campaignCategories) {
                $attributes['categories'] = null;
                if (isset($request->sub_categories) && $request->sub_categories) {
                    $categoryIds = array_map(function ($cat) {
                        return isset($cat->id) ? $cat->id : null;
                    }, $request->sub_categories);
                }
            }

            $tagIds = [];
            $blog->tagPivot()->delete();

            if (isset($request->tags) && $request->tags) {
                $tagIds = array_map(function ($tag) {
                    return $tag->id;
                }, $request->tags);
                foreach ($tagIds  as $tagId) {
                    $blog->tagPivot()->create(['tag_id' => $tagId, 'collection_id' => $blog->id, 'collection_type' => $blog->collection_type]);
                }
            }

            $attributes['tags'] = json_encode($tagIds);
            $blog->update($attributes);

            if (isset($request->meta) && $request->meta) {
                $blog->load('seos');

                $seoData = [
                    'meta' => json_encode($request->meta),
                    'updated_by' => $user->id,
                ];
                if ($blog->seos != null) {
                    $seo = $blog->seos()->update($seoData);
                } else {
                    $seoData['created_by'] = $user->id;
                    $seo = $blog->seos()->create($seoData);
                }
            }
            if ($request->featured_image) {
                $blog->mediables()->delete();
                $blog->mediables()->create([
                    'media_id'   => $request->featured_image->id,
                    'name'       => $request->featured_image->name,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
            }
            if (isset($request->file) && $request->file) {
                $blog->fileables()->delete();
                $blog->fileables()->create([
                    'file_id'    => $request->file->id,
                    'name'       => $request->file->filename,
                    'created_by' => $user->id,
                ]);
            }
        } else {
            $blog->categoryPivot()->delete();
            $categories = [];

            if (isset($request->vendor) && $request->vendor && $request->vendor['id']) {
                $attributes['vendor_id'] = $request->vendor['id'];
            }

            if ($request->type != CollectionType::campaigns && $request->type != CollectionType::campaignCategories) {
                $blog->categoryPivot()->delete();
                $categories = [];
                if (isset($request->categories) && $request->categories) {
                    $categories = array_map(function ($cat) {
                        return $cat['id'];
                    }, $request->categories);

                    foreach ($categories  as $catId) {
                        $blog->categoryPivot()->create(['category_id' => $catId, 'collection_id' => $blog->id, 'collection_type' => $blog->collection_type]);
                    }
                }
                $attributes['categories'] = json_encode($categories);
            } elseif ($request->type == CollectionType::campaigns) {
                $attributes['categories'] = null;
                if (isset($request->categories) && $request->categories) {
                    $categoryIds = array_map(function ($cat) {
                        return isset($cat['id']) ? $cat['id'] : null;
                    }, $request->categories);
                    $attributes['categories'] = json_encode($categoryIds);
                }
            } elseif ($request->type == CollectionType::campaignCategories) {
                $attributes['categories'] = null;
                if (isset($request->sub_categories) && $request->sub_categories) {
                    $categoryIds = array_map(function ($cat) {
                        return isset($cat['id']) ? $cat['id'] : null;
                    }, $request->sub_categories);
                }
            }

            $tagIds = [];
            $blog->tagPivot()->delete();

            if (isset($request->tags) && $request->tags) {
                $tagIds = array_map(function ($tag) {
                    return $tag['id'];
                }, $request->tags);
                foreach ($tagIds  as $tagId) {
                    $blog->tagPivot()->create(['tag_id' => $tagId, 'collection_id' => $blog->id, 'collection_type' => $blog->collection_type]);
                }
            }

            $attributes['tags'] = json_encode($tagIds);
            $blog->update($attributes);

            if (isset($request->meta) && $request->meta) {
                $blog->load('seos');

                $seoData = [
                    'meta' => json_encode($request->meta),
                    'updated_by' => $user->id,
                ];
                if ($blog->seos != null) {
                    $seo = $blog->seos()->update($seoData);
                } else {
                    $seoData['created_by'] = $user->id;
                    $seo = $blog->seos()->create($seoData);
                }
            }
            if ($request->featured_image) {
                $blog->mediables()->delete();
                $blog->mediables()->create([
                    'media_id'   => $request->featured_image['id'],
                    'name'       => $request->featured_image['name'],
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
            }
            if (isset($request->file) && $request->file) {
                $blog->fileables()->delete();
                $blog->fileables()->create([
                    'file_id'    => $request->file['id'],
                    'name'       => $request->file['filename'],
                    'created_by' => $user->id,
                ]);
            }
        }

        return $attributes;
    }

    public function userRoleTesting(Request $request)
    {
        $user = auth()->user();

        $user->update(
            [
                'role_id' => $request->role_id,
            ]
        );

        return $user;
    }

    /**
     * Submit New Comment it.
     */
    public function saveCollectionComment(Request $request)
    {
        try {
            $user = auth()->user();

            $validator = Validator::make($request->all(), [
                'collection_id'        => 'required',
                'comment'      => 'required',
                'collection_type' => 'required',
            ]);
            if ($validator->fails()) {
                return response(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
            }

            $data = [
                'comment'          => $request->comment,
                'is_active'        => true,
                'commentable_type' => config('app.collection_model'),
                'commentable_id'   => $request->collection_id,
                'collection_type'  => $request->collection_type,
            ];

            if ($request->comment_id) {
                $data['updated_by'] = $user->id;

                $comment = Comment::find($request->comment_id);

                if (!$comment) {
                    return response(['message' =>  'Comment Not Found', 'status' => false], 422);
                }
                $comment->update($data);
            } else {
                $data['created_by'] = $user->id;

                $comment = Comment::Create($data);
            }

            return response([
                'status' => true,
                'message' => 'Comment Added successfully',
                'data' => $comment,
            ], 201);

            return response(['message' =>  'server error', 'status' => false], 500);
        } catch (\Exception $e) {
            report($e);

            return response(['message' =>  'server error', 'status' => false], 500);
        }
    }

    public function deleteComment(Request $request)
    {
        try {
            $user = auth()->user();

            $validator = Validator::make($request->all(), [
                'id'        => 'required',
            ]);
            if ($validator->fails()) {
                return response(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
            }
            $comment = Comment::where('id', $request->id)->where('created_by', $user->id)->where('is_active', true)->first();
            if (!$comment) {
                return response(['errors' => ['comment' => ['Comment not found or already deleted']], 'status' => false, 'message' => 'Comment not found or already deleted'], 422);
            }
            $comment->update(['is_active' => false]);

            return response(['status' => true, 'message' => 'Comment deleted successfully'], 201);
        } catch (\Exception $e) {
            report($e);

            return response(['message' =>  'server error', 'status' => false], 500);
        }
    }

    /**
     * Feed Comments Listing.
     */
    public function getAllCollectionComments(Request $request)
    {
        $user = auth()->user();
        // return $user;
        try {
            $validator = Validator::make($request->all(), [
                'collection_id'        => 'required',
            ]);
            if ($validator->fails()) {
                return response(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
            }

            $comments = Comment::where('is_active', true)->where('collection_type', $request->collection_id)->where(
                'commentable_type',
                config('app.collection_model')

            );

            if ($request->max_rows) {
                $comments = $comments->latest()->paginate($request->max_rows);
            } else {
                $comments = $comments->latest()->get();
            }

            $userIds = [];
            $users = collect([]);
            foreach ($comments as  $comment) {
                $userIds[] = $comment->created_by;
            }

            if (count($userIds)) {
                $users = UserHelper::users($userIds);
            }

            $comments->transform(function ($comment) use ($users) {
                $user = $users->where('id', $comment->created_by)->first();
                $comment->user = $user;

                return $comment;
            });

            // if ($request->user_id) {
            $userId = auth()->user();
            $comments->transform(function ($comment) use ($userId) {
                $comment['isAuthLiked'] = false;
                $likes = $comment['likes'];
                if ($likes && count($likes) > 0) {
                    $comment['isAuthLiked'] = ($likes && count($likes->where('created_by', $userId)->where('is_liked', true)->toArray())) ? true : false;
                }

                return $comment;
            });
            // }

            return new CommentCollection($comments);
        } catch (\Exception $e) {
            report($e);

            return response(['message' =>  'server error', 'status' => false], 500);
        }
    }

    /**
     * Submit New Like or unlike it.
     */
    public function savelikeUnlikeComment(Request $request)
    {
        try {
            $user = auth()->user();

            $validator = Validator::make($request->all(), [
                'collection_id'        => 'required',
                'collection_type'   => 'required',
            ]);
            if ($validator->fails()) {
                return response(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
            }
            $data = [
                'likable_type' => config('app.collection_model'),
                'likable_id' => $request->collection_id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'collection_type'  => $request->collection_type,
            ];
            $like = LikeModel::where($data)->first();
            if ($like) {
                $is_liked = $like->is_liked;
                $like->update(['is_liked' => !$is_liked]);
                $like->refresh();
            } else {
                $like = LikeModel::create($data);
            }

            // if (!$request->is_liked) {
            //     $like->update(['is_liked' => false]);
            // }
            return response(['status' => true, 'message' => 'Post updated successfully', 'data' => $like], 201);
        } catch (\Exception $e) {
            report($e);

            return response(['message' =>  'server error', 'status' => false], 500);
        }
    }

    public function commentReportSpam(Request $request)
    {
        try {
            $user = auth()->user();

            $validator = Validator::make($request->all(), [
                'id'        => 'required',
            ]);
            if ($validator->fails()) {
                return response(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
            }

            $data = [
                'reportable_type' => \App\Comment::class,
                'reportable_id'   => $request->id,
                'created_by'      => $user->id,
            ];
            $report = SpamReport::firstOrCreate($data);

            $report->update([
                'description'     => $request->description,
                'updated_by'      => $user->id,
                'status'          => 1,
            ]);

            return response(['status' => true, 'message' => 'Post updated successfully', 'data' => $report], 201);
        } catch (\Exception $e) {
            report($e);

            return response(['message' =>  'server error', 'status' => false], 500);
        }
    }

    public function getCollectionData(Request $request)
    {
        $blogs = DataModel::with('medias')->with('vendor')->latest();
        // if ($request->type) {
        $blogs = $blogs->where('collection_type', CollectionType::faqs);
        // }

        if ($request->search) {
            $blogs = $blogs->where('title', 'like', "%{$request->search}%");
        }
        if ($request->isTrashed) {
            $blogs = $blogs->onlyTrashed();
        }

        $blogs = $blogs->whereNotNull('published_at');

        if ($request->maxRows) {
            $blogs = $blogs->paginate($request->maxRows);
        } else {
            $blogs = $blogs->get();
        }

        foreach ($blogs as $blog) {
            $blog['onlyForMobile'] = true;
        }

        return new BlogCollection($blogs);
    }

    /**
     * Add or update product.
     */
    private function addUpdateProduct($collection)
    {
        $productData = [
            'name'              => $collection->title,
            'slug'              => $collection->slug,
            'description'       => $collection->description,
            'stock'             => 1,
            'status'            => PublishStatus::Published,
            'created_by'        => $collection->created_by,
            'updated_by'        => $collection->updated_by,
        ];

        $product = Product::where('collection_id', $collection->id)->first();

        if ($product) {
            $product->update($productData);
        } else {
            Product::create($productData);
        }

        return true;
    }
}
