<?php

namespace App\Http\Controllers;

use App\Feed;
use App\Comment;
use App\CustomFeed;
use App\Enums\FeedType;
use App\Enums\RoleType;
use App\Enums\UserRole;
use App\Enums\FeedStatus;
use App\Like as LikeModel;
use App\Helpers\FeedHelper;
use App\Helpers\SlugHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\NotificationHelper;
use App\Http\Resources\FeedCollection;
use App\Http\Resources\LikeCollection;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\CommentCollection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Feed as FeedResource;
use App\Adapters\DynamicUrl\DynamicUrlService;
use App\Helpers\CommonHelper;

class FeedController extends Controller
{
    /**
     * Feeds listing.
     */
    public function getAllFeeds(Request $request)
    {   
        $user = null;
        $user = auth()->user();
        $feeds = Feed::with('feedable.medias', 'createdBy', 'updatedBy', 'likes.users.avatarMediable.media', 'comments', 'likes');
        $is_publish = $request->is_publish;

        if ($request->slug) {

            $feeds = $feeds->whereHasMorph(
                'feedable',
                [CustomFeed::class],
                function (Builder $query) use($request){
                    $query->where('slug', '=', $request->slug);
                });
            $feeds = $feeds->where('is_publish', true)->get();   

        }
        if($user != null and $user->role_id == UserRole::SuperAdmin and !$request->slug)
        {
            if($is_publish == "0"){
                $feeds = $feeds->where('is_publish','!=', true);
                
            } 
            if($is_publish == "1"){
                $feeds = $feeds->where('is_publish', true);
            }
            
            if ($request->sort_by) {
                $feeds = $feeds->latest();
            } else {
                $feeds = $feeds->orderBy('created_at', 'desc');
            }
           
            if ($request->is_partner != "null" and $request->is_partner == true) {
                $feeds = $feeds->where('is_partner', 1);
            }
            if ($request->is_partner != "null" and $request->is_partner == false) {
                $feeds = $feeds->where('is_partner', 0);
            }

            if($request->search)
            {
                $feeds = $feeds->whereHasMorph(
                    'feedable',
                    [CustomFeed::class],
                    function (Builder $query) use($request){
                        $query->where('description', 'LIKE', "%$request->search%");
                    });
   
            }
            if ($request->is_trashed != null and $request->is_trashed==true) {
           
                $feeds = $feeds->where('status', FeedStatus::Trashed);
            } else {
                $feeds = $feeds->where('status', FeedStatus::Active);
            }
            
        }  
        elseif(!$request->slug){     
        
            if ($request->sort_by) {
                $feeds = $feeds->latest();
            }else{
                $feeds = $feeds->orderBy('created_at', 'desc');
            }
            if (!$request->slug) {
                if($request->sticky == true) {
                    $feeds = $feeds->where('is_sticky', 1);
                } else {
                    $feeds = $feeds->where('is_sticky', 0);
                    }
            } 
            
            
            //Fetch all records for partner only
            if ($request->is_partner != "null" and $request->is_partner == true) {
                $feeds = $feeds->where('is_partner', 1);
            }
            if ($request->is_partner != "null" and $request->is_partner == false) {
                $feeds = $feeds->where('is_partner', 0);
            }
            $feeds = $feeds->where('status', FeedStatus::Active)->where('is_publish', true);
        }
        $isPaginate = false;

        if ($request->max_rows) {
            $maxRows = (int) $request->max_rows;
            $feeds = $feeds->paginate($maxRows);
            $isPaginate  = true;
        }


        if ($request->user_id and $request->user_id != 'null' and $user->role_id != UserRole::SuperAdmin) {
            $userId = $request->user_id;
            if ($request->max_rows) {
                $feeds->getCollection()->transform(function ($feed) use ($userId) {
                    $feed['isAuthLiked'] = false;
                    $createdBy = $feed->createdBy ;
                    $feed['createdBy']   = $feed->updatedBy ;
                    $feed['updatedBy']   = $createdBy ;
                    $feed['userId'] = $userId;
                    $likes = $feed['likes'];
                    if ($likes && count($likes) > 0) {
                        $feed['isAuthLiked'] = ($likes && count($likes->where('created_by', $userId)->where('is_liked', true)->toArray())) ? true : false;
                    }

                    return $feed;
                });
            } else {
                $feeds->map(function ($feed) use ($userId) {
                    $feed['isAuthLiked'] = false;
                    $feed['userId'] = $userId;
                    $likes = $feed['likes'];
                    $createdBy = $feed->createdBy ;
                    $feed['createdBy']   = $feed->updatedBy ;
                    $feed['updatedBy']   = $createdBy ;

                    if ($likes && count($likes) > 0) {
                        $feed['isAuthLiked'] = ($likes && count($likes->where('created_by', $userId)->where('is_liked', true)->toArray())) ? true : false;
                    }

                    return $feed;
                });
            }

            //  $feeds->map(function ($feed) use ($userId) {
            //         $feed['isAuthLiked'] = false;
            //         $feed['userId'] = $userId;
            //         $likes = $feed['likes'];
            //         if ($likes && count($likes) > 0) {
            //             $feed['isAuthLiked'] = ($likes && count($likes->where('created_by', $userId)->where('is_liked', true)->toArray())) ? true : false;
            //         }

            //         return $feed;
            //     });
        }

        $feeds = FeedHelper::addViews($feeds, $isPaginate);

        if ($request->slug) {
            // return $feeds; 
            return new FeedResource($feeds->first());
        }
        return new FeedCollection($feeds);
    }



    public function saveFeedData(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title'        => 'required|string|max:750',
                'description'  => 'required|string|max:2500',
            ]);

            if ($validator->fails()) {
                return response(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
            }
            if ($request->title == '' && !$request->images) {
                return response(['errors' => [[__('validation.desc_image')]], 'status' => false, 'message' => ''], 422);
            }

            $user = auth()->user();


            // $user = null;
            // if (Auth::guard('api')->user()) {
            //     $user = Auth::guard('api')->user();
            // }

            DB::transaction(function () use ($request, $user) {
                // if ($request->is_custom) {
                if ($request->images) {
                    $image = $request->images['0'];
                } else {
                    $image = null;
                }
                $slugData = SlugHelper::getSlugAndNameFeed($request->title);
                $custom_data = [
                    'slug'          => $slugData->slug,
                    'title'         => $slugData->title,
                    'description'   => $request->description,
                    'excerpt'       => $request->excerpt,
                    'saved_content' => $request->meta ? json_encode(['meta' => $request->meta]) : json_encode(['meta' => self::meta($request->title, $request->description, $image)]),
                    'url'           => $request->url,
                    'updated_by'    => $user ? $user->id : null,
                    'type'          => $request->is_custom ? FeedType::NormalFeeds : FeedType::YouTubeFeeds,
                ];
                if ($request->id) {
                    $custom_feed = CustomFeed::find($request->feedable_id);
                    $slugData = SlugHelper::getSlugAndNameFeed($request->title, $custom_feed);

                    $custom_data['slug'] = $slugData->slug;
                    $custom_data['title'] = $slugData->title;

                    $custom_feed->update($custom_data);
                    $feed = Feed::firstOrCreate(['feedable_id' => $custom_feed->id, 'feedable_type' => \App\CustomFeed::class]);
                    $feed->update([
                        // 'sequence' => $request->sequence,
                        'is_sticky'  => $request->is_sticky,
                        'updated_by' => $user ? $user->id : null,
                        'is_partner' => $request->is_partner,
                    ]);
                } else {
                    $custom_data['created_by'] = $user ? $user->id : null;
                    $custom_feed = CustomFeed::create($custom_data);

                    $feedData = [
                        'feedable_id'   => $custom_feed->id,
                        'feedable_type' => \App\CustomFeed::class,
                        // 'sequence'      => $request->sequence,
                        'is_sticky'     => $request->is_sticky,
                        'status'        => FeedStatus::Active,
                        'created_by'    => $user ? $user->id : null,
                        'updated_by'    => $user ? $user->id : null,
                        'is_partner'    => $request->is_partner,
                    ];
                    $feed = Feed::Create($feedData);
                    $dynamic = new DynamicUrlService();
                    $dynamic->createDynamicUrlForFeed($feed->id);

                    NotificationHelper::collection($custom_feed, true); // send notification
                    // }
                }
                $custom_feed->mediables()->delete();
                if ($request->images) {
                    $images = $request->images;
                    foreach ($images as $image) {
                        $custom_feed->mediables()->create(['name' => $image['name'], 'media_id' => $image['id'], 'created_by' => $user ? $user->id : null]);
                    }
                }
            });

           

            return response(['message' =>  [__('validation.success')], 'status' => true], 200);
        } catch (\Exception $e) {
            report($e);

            return response(['message' =>  [__('validation.server_err')], 'status' => false], 500);
        }
    }
    public static function meta($title, $description, $image = null)
    {
        return [
            "meta_title"           => $title,
            "meta_keywords"        => $title,
            "meta_description"     => $description ? $description : $title,
            "og_title"             => $title,
            "og_description"       => $description ? $description : $title,
            "og_type"              => $title,
            "og_site_name"         => "DanceBuzz",
            "og_image_alt"         => "DanceBuzz",
            "og_image"             => $image,
            "twitter_title"        => $title,
            "twitter_description"  => $description ? $description : $title,
            "twitter_site"         => "DanceBuzz",
            "twitter_card"         => "DanceBuzz",
            "twitter_image_alt"    => "DanceBuzz",
            "twitter_image"        => $image
        ];
    }

    public function getFeedData($id)
    {
        $feed = Feed::with('feedable.medias','dynamicurls')->find($id);
        $feed->is_custom = false;
        if ($feed->feedable_type = \App\CustomFeed::class) {
            $content = $feed->feedable->saved_content;
            $feed->is_custom = true;
            $feed->saved_content = $content ? json_decode($content) : null;
            $feed->feedable->medias->map(function ($media) {
                $media->full_url = Storage::url($media->url);
                return $media;
            });
        }
        $feeds = FeedHelper::addViews(collect([$feed]));
        $feed = $feeds->first();

        if ($feed->dynamicurls->count() > 0) {
            $feed->dynamic_url = $feed->dynamicurls->last()->url;
        }else{
            $feed->dynamic_url = '';
        } 
        
        return $feed;
    }




    public function updateFeedStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id'        => 'required',
                'status'    => 'required',
            ]);
            if ($validator->fails()) {
                return response(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
            }
            $user = auth()->user();
            // $user = null;
            // if (Auth::guard('api')->user()) {
            //     $user = Auth::guard('api')->user();
            // }
            $feed = Feed::find($request->id);
            if (!$feed) {
                return response(['errors' => ['not_exist' => [__('validation.feed_not_exist')]], 'status' => false, 'message' => ''], 422);
            }
            if ($feed->status == $request->status) {
                return response(['errors' => ['updated' => [__('validation.feed_updated')]], 'status' => false, 'message' => ''], 422);
            }
            $feed->update([
                'status'     => $request->status,
                'updated_by' => $user ? $user->id : null,
            ]);

            return $feed;
        } catch (\Exception $e) {
            report($e);

            return response(['message' =>  [__('validation.server_err')], 'status' => false], 500);
        }
    }
      
    public function disableEnableFeed(Request $request){
         
        try {
            $validator = Validator::make($request->all(), [
            'id'        => 'required',
            'is_publish'    => 'required',
            ]);
            if ($validator->fails()) {
                return response(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
            }
            $user = auth()->user();
            if ($user->role_id == UserRole::SuperAdmin) {
                $feed = feed::where('id', $request->id)->where('status', true)->first();
                if (!$feed) {
                    return response(['errors' => ['comment' => [__('validation.feed_NotFound')]], 'status' => false, 'message' => 'Comment not found'], 422);
                }
            if($request->is_publish == true){
              $feed->where('id', $request->id)->update(['is_publish' => true]);
              return response(['status' => true, 'message' => [__('validation.feed_enabled')], ], 201);
            }else{
               $feed->where('id', $request->id)->update(['is_publish' => false]);
               return response(['status' => true, 'message' => [__('validation.feed_disabled')], ], 201);
            }
            }  else{
           

            return response(['errors' => ['comment' => [__('validation.feed_disable')]], 'status' => false, 'message' => 'User is not autherized'], 422);
        } 
        } catch (\Exception $e) {
        report($e);

        return response(['message' =>  [__('validation.server_err')], 'status' => false], 500);
        }      
    }

    public function getFeedList(Request $request)
    {
        $likes = LikeModel::where('likable_id', $request->id)->where('likable_type', \App\Feed::class)->where('is_liked', 1)->get();

        // $userIds = collect();
        // foreach ($likes as $key => $like) {
        //     $userIds->push(
        //         $like->created_by
        //     );
        // }

        // if (isset($request->max_rows) && ($request->max_rows)) {
        //     $users = User::whereIn('id', $userIds)->paginate($request->max_rows);
        // } else {
        //     $users = User::whereIn('id', $userIds)->get();
        // }

        // if (isset($request->isMobile) && $request->isMobile) {
        //     foreach ($users as $user) {
        //         $user['isMobile'] = true;
        //     }
        // }

        // return new UserCollection($users);
    }

    public function getFeedLikeList(Request $request)
    {
        $likes = LikeModel::with('users')->where('likable_id', $request->id)->where('likable_type', \App\Feed::class)->where('is_liked', 1);

        if (isset($request->max_rows) && ($request->max_rows)) {
            $likes = $likes->paginate($request->max_rows);
        } else {
            $likes = $likes->get();
        }

        if (isset($request->isMobile) && $request->isMobile) {
            foreach ($likes as $like) {
                $user['isMobile'] = true;
            }
        }

        return new LikeCollection($likes);
    }

    /**
     * Submit New Like or unlike it.
     */
    public function savelikeUnlikeFeed(Request $request)
    {
        try {
            $user = auth()->user();

            $validator = Validator::make($request->all(), [
                'id'        => 'required',
                'is_post'   => 'required',
                // 'user_id'   => 'required'
            ]);
            if ($validator->fails()) {
                return response(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
            }
            if ($request->is_post) {
                $data = [
                    'likable_type' => config('app.feed_model'),
                    'likable_id' => $request->id,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
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
                return response(['status' => true, 'message' => [__('validation.post_updated')], 'data' => $like], 201);
            }
        } catch (\Exception $e) {
            report($e);

            return response(['message' =>  [__('validation.server_err')], 'status' => false], 500);
        }
    }

    /**
     * Submit New Comment it.
     */
    public function saveFeedComment(Request $request)
    {
        try {
            $user = auth()->user();

            $validator = Validator::make($request->all(), [
                'id'        => 'required',
                'text'      => 'required',
                'is_post'   => 'required',
                // 'user_id'   => 'required'
            ]);
            if ($validator->fails()) {
                return response(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
            }
            if ($request->is_post) {
                $data = [
                    'comment'          => $request->text,
                    'is_active'        => true,
                    'commentable_type' => config('app.feed_model'),
                    'commentable_id'   => $request->id,
                    'created_by'       => $user->id,
                    'updated_by'       => $user->id,
                ];
                $comment = Comment::Create($data);

                return response([
                    'status' => true,
                    'message' => [__('validation.post_updated')],
                    'data' => $comment,
                ], 201);
            }

            return response(['message' =>  [__('validation.server_err')], 'status' => false], 500);
        } catch (\Exception $e) {
            report($e);

            return response(['message' =>  [__('validation.server_err')], 'status' => false], 500);
        }
    }
    public function saveSuperAdminFeedComment(Request $request){
        try{ 
            $user = auth()->user();
            
            $validator = Validator::make($request->all(), [
                'id'        => 'required',
                'text'      => 'required',
                'is_post'   => 'required',
                'comment_id' => 'required'
                // 'user_id'   => 'required'
            ]);
            if ($validator->fails()) {
                return response(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
            }
            
            if ($request->is_post) {
                $data = [
                    'comment'          => $request->text,
                    'is_active'        => true,
                    'commentable_type' => config('app.feed_model'),
                    'commentable_id'   => $request->id,
                    'parent_comment_id' => $request->comment_id,
                    'created_by'       => $user->id,
                    'updated_by'       => $user->id,
                    'role_id'          => $user->role_id,
                ];
                $comment = Comment::Create($data);
                return response([
                    'status' => true,
                    'message' => [__('validation.post_updated')],
                    'data' => $comment,
                ], 201);
            }
            return response(['message' =>  [__('validation.server_err')], 'status' => false], 500);
             
        } catch (\Exception $e) {
        report($e);
        return response(['message' =>  [__('validation.server_err')], 'status' => false], 500);
        }
    }
    public function editSuperAdminFeedComment(Request $request){

        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'id'        => 'required',
            'comment_id' => 'required',
            'parent_comment_id' => 'required',
            'text'        => 'required'
            // 'user_id'   => 'required'
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
        }
        $comment = Comment::where('id',$request->comment_id)->where('parent_comment_id', $request->parent_comment_id)->first();
        if($comment){
            if($user->role_id != $comment->role_id ){
                return response(['error' => ['not allowed'], 'status' =>false, 'message' => ''], 422);
            }
        }else{
            return response(['error' => ['comment not found'], 'status' =>false, 'message' => ''], 422);
        }
        
        if($comment){
            $data = [
                'comment'          => $request->text,
                'is_active'        => true,
                'commentable_type' => config('app.feed_model'),
                'commentable_id'   => $request->id,
                'parent_comment_id' => $request->parent_comment_id,
                'created_by'       => $user->id,
                'updated_by'       => $user->id,
            ];
            $comment->update($data);
            $comment->refresh();
                
            
        }
        return response([
            'status' => true,
            'message' => [__('validation.post_updated')],
            'data' => $comment,
        ], 201);


    }
    public function deleteSuperAdminComment(Request $request){
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'id'        => 'required',
            'user_id'   => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
        }
        $userId = $request->user_id;
        $comment = Comment::where('id', $request->id)->where('created_by', $userId)->where('is_active', true)->first();
        if (!$comment) {
            return response(['errors' => ['comment' => [__('validation.comment_delete')]], 'status' => false, 'message' => 'Comment not found or already deleted'], 422);
        }
        $comment->where('id', $request->id)->update(['is_active' => false]);
        return response(['status' => true, 'message' => [__('validation.post_updated')], 'data' => $comment], 201);
    }

    public function disableEnableComment(Request $request)
    {
        try {
            $user = auth()->user();


            $validator = Validator::make($request->all(), [
                'id'        => 'required',
                'is_publish' => 'required',

            ]);
            if ($validator->fails()) {
                return response(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
            }
            if ($user->role_id == UserRole::SuperAdmin) {
                $comment = Comment::where('id', $request->id)->where('is_active', true)->first();
                if (!$comment) {
                    return response(['errors' => ['comment' => [__('validation.comment_NotFound')]], 'status' => false, 'message' => 'Comment not found'], 422);
                }
                $ChildcommentIds = CommonHelper::commentID($request->id);
                $ChildcommentIds = CommonHelper::nestedToSingle($ChildcommentIds);
                                      
                                    
                if ($request->is_publish == true) {
                    $comment->where('id', $request->id)->update(['is_publish' => true]);
                    if($ChildcommentIds){
                        $Childcomments = Comment::whereIn('id',$ChildcommentIds);
                        $Childcomments->update(['is_publish' => true]);
                    }
                   
                    return response(['status' => true, 'message' => [__('validation.comment_enabled')],], 201);
                } else {
                    $comment->where('id', $request->id)->update(['is_publish' => false]);
                    if($ChildcommentIds){
                        $Childcomments = Comment::whereIn('id',$ChildcommentIds);
                        $Childcomments->update(['is_publish' => false]);
                    }
                    return response(['status' => true, 'message' => [__('validation.comment_disabled')],], 201);
                }
            } else

                return response(['errors' => ['comment' => [__('validation.comment_disable')]], 'status' => false, 'message' => 'User is not autherized'], 422);
        } catch (\Exception $e) {
            report($e);

            return response(['message' =>  [__('validation.server_err')], 'status' => false], 500);
        }
    }
    public function deleteComment(Request $request)
    {
        try {
            $user = auth()->user();

            $validator = Validator::make($request->all(), [
                'id'        => 'required',
                'user_id'   => 'required',
            ]);
            if ($validator->fails()) {
                return response(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
            }
            $comment = Comment::where('id', $request->id)->where('created_by', $user->id)->where('is_active', true)->first();
            $ChildcommentIds = CommonHelper::commentID($request->id);
            $ChildcommentIds = CommonHelper::nestedToSingle($ChildcommentIds);
            

            if (!$comment) {
                return response(['errors' => ['comment' => [__('validation.comment_delete')]], 'status' => false, 'message' => 'Comment not found or already deleted'], 422);
            }
            $comment->update(['is_active' => false]);
            $Childcomments = Comment::whereIn('id',$ChildcommentIds);
            $Childcomments->update(['is_active' => false]);

            return response(['status' => true, 'message' => [__('validation.post_updated')], 'data' => $comment], 201);
        } catch (\Exception $e) {
            report($e);

            return response(['message' =>  [__('validation.server_err')], 'status' => false], 500);
        }
    }


    public function deletePartnerComment(Request $request)
    {
        try {
            $user = auth()->user();

            $validator = Validator::make($request->all(), [
                'id'        => 'required',
                'user_id'   => 'required',
            ]);
            if ($validator->fails()) {
                return response(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
            }
            $comment = Comment::where('id', $request->id)->where('created_by', $user->id)->where('is_active', true)->where('is_partner', true)->first();
            if (!$comment) {
                return response(['errors' => ['comment' => [__('validation.comment_delete')]], 'status' => false, 'message' => 'Comment not found or already deleted'], 422);
            }
            $comment->update(['is_active' => false]);

            return response(['status' => true, 'message' => [__('validation.post_updated')], 'data' => $comment], 201);
        } catch (\Exception $e) {
            report($e);

            return response(['message' =>  [__('validation.server_err')], 'status' => false], 500);
        }
    }

    /**
     * Feed Comments Listing.
     */
    public function getAllFeedComments(Request $request)
    {
        try {
            $user = null;
            $user = auth()->user();
            $validator = Validator::make($request->all(), [
                'id'        => 'required',
                'is_post'   => 'required',
            ]);
            if ($validator->fails()) {
                return response(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
            }
            
            if ($user != null and $user->role_id == UserRole::SuperAdmin) {
                    $comments = Comment::with('spams', 'user')->where('is_active', true)->where('commentable_id', $request->id);
            }else{
                    $comments = Comment::with('spams', 'user')->where('is_publish', true)->where('is_active', true)->where('commentable_id', $request->id);
            }
            
            if ($request->search) {
                $comments =  $comments->where('comment', 'like', "%{$request->search}%");
            }

            if ($request->is_post) {
                $comments = $comments->where('commentable_type', config('app.feed_model'))
                            ->where('parent_comment_id', null);
            }

            // if ($request->user_id) {
            //     $userId = $request->user_id;
            //     $comments = $comments->whereHas('spamreports', function ($q) use ($userId) {
            //         $q->where('created_by', '!=', $userId);
            //     });
            // }

            if ($request->max_rows) {
                $comments = $comments->latest()->paginate($request->max_rows);
            } else {
                $comments = $comments->latest()->get();
            }

            // $userIds = [];
            // $users   = collect([]);
            // foreach ($comments as  $comment) {
            //     $userIds[] = $comment->created_by;
            // }

            // if (count($userIds)) {
            //     $users = UserHelper::users($userIds);
            // }

            // $comments->getCollection()->transform(function ($comment) use ($users) {
            //     $user = $users->where('id', $comment->created_by)->first();
            //     $comment->user = $user;
            //     return $comment;
            // });

            return new CommentCollection($comments);
        } catch (\Exception $e) {
            report($e);

            return response(['message' =>  [__('validation.server_err')], 'status' => false], 500);
        }
    }

    public function commentReportSpam(Request $request)
    {
        try {
            $user = auth()->user();

            $validator = Validator::make($request->all(), [
                'id'  => 'required',
            ]);

            if ($validator->fails()) {
                return response(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
            }

            $comment = Comment::with('spams')->where('id', $request->id)->first();
            if (!$comment) {
                return response(['message' =>  [__('validation.server_err')], 'status' => false], 500);
            }

            $isSpam = false;
            if (count($comment->spams)) {
                $authSpam = $comment->spams->where('created_by', auth()->id())->first();
                if ($authSpam) {
                    $isSpam = true;
                    $authSpam->update([
                        'updated_by' => $user->id,
                        'status'     => 1,
                    ]);
                }
            }

            if (!$isSpam) {
                $data = [
                    'description'     => $request->description,
                    'updated_by'      => $user->id,
                    'created_by'      => auth()->id(),
                    'status'          => 1,
                ];
                $comment->spams()->create($data);
            }

            return response(['status' => true, 'message' => [__('validation.post_updated')]], 201);
        } catch (\Exception $e) {
            report($e);

            return response(['message' =>  [__('validation.server_err')], 'status' => false], 500);
        }
    }

    public function createDynamicUrlForFeed(Request $request){
        
        $feedId = $request->feed_id ;

        $feed = Feed::find($feedId);
        if(!$feed){
            return response(['message'=>'Feed Not Found ','status'=>true],422);
        }

        $du = new DynamicUrlService();

        $url = $du->createDynamicUrlForFeed($feedId,$request->refresh);

        return $url ;
    }
}