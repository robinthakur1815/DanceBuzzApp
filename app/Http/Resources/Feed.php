<?php

namespace App\Http\Resources;

use App\Enums\FeedType;
use App\Enums\RoleType;
use App\Helpers\UserHelper;
use App\Http\Resources\User as UserResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class Feed extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // if ($this->createdBy && $this->createdBy['id']) {
        //     $this->createdBy['no_temp_profile'] = true;
        // }

        if ($this->createdBy && $this->createdBy['id']) {
            $this->createdBy['no_temp_profile'] = true;
        }
        
        $view_count = 0;
        if ($this->view_count) {
            $view_count = (int)$this->view_count;
        }

        $isCustome =  $this->feedable_type == config('app.custom_feed_model') ? true : false;
        
        $saveContent = null;
        $slug = '';
        $webUrl = "";
        $mobile = UserHelper::isMobileRequest(); 
        if(!$mobile and $isCustome and $this->feedable->saved_content) {
            $saveContent = json_decode($this->feedable->saved_content);
        }

       if($isCustome) {
            $slug = $this->feedable->slug;
            $webUrl = config('app.client_url').'/mobile/feed/'.$slug;
            // $webUrl = config('client.short_friend_url');
       }


        $created_at_date = $this->created_at->diffForHumans();


// 455932


        if (isset($this->isAuthLiked)) {
            $data = [
                'postId'             => $this->id,
                'createdBy'          => $this->createdBy ? new UserResource($this->createdBy) : null,
                'updatedBy'          => $this->updatedBy ? new UserResource($this->updatedBy) : null,
                'createdAt'          => $this->created_at,
                'updatedAt'          => $this->updated_at,
                'created_at_date'    => $created_at_date,
                'images'             => [],
                'description'        => $this->feedable->description,
                'title'              => $this->feedable->title,
                'slug'               => $slug,
                'web_url'            => $webUrl,
                'url'                => $isCustome ? $this->feedable->url : '',
                'excerpt'            => null,
                'likesCount'         => count($this->likes->where('is_liked', true)->where('created_by', '!=', $this->userId)),
                'commentsCount'      => count($this->comments->where('is_active', true)->where('is_publish', true)),
                'isAuthLiked'        => $this->isAuthLiked,
                'isCustom'           => $isCustome ? true : false,
                'excerpt'            => $isCustome ? $this->feedable->excerpt : '',
                'isSticky'           => $this->is_sticky ? true : false,
                'feed_type'          => $this->feedable->type ? $this->feedable->type : FeedType::NormalFeeds,
                'view_count'         => $view_count,
                'is_partner'         => $this->is_partner ? true : false
            ];
        } else {
            $data = [
                'postId'             => $this->id,
                'createdBy'          => $this->createdBy ? new UserResource($this->createdBy) : null,
                'updatedBy'          => $this->updatedBy ? new UserResource($this->updatedBy) : null,
                'feedableId'         => $this->feedable->id,
                'isSticky'           => $this->is_sticky ? true : false,
                'createdAt'          => $this->created_at,
                'updatedAt'          => $this->updated_at,
                'created_at_date'    => $created_at_date,
                'status'             => $this->status,
                'sequence'           => $this->sequence,
                'images'             => [],
                'description'        => $this->feedable->description,
                'title'              => $this->feedable->title,
                'slug'               => $slug,
                'web_url'            => $webUrl,
                'url'                => $isCustome ? $this->feedable->url : '',
                'excerpt'            => $isCustome ? $this->feedable->excerpt : '',
                'isCustom'           => $isCustome ? true : false,
                'feed_type'          => $this->feedable->type ? $this->feedable->type : FeedType::NormalFeeds,
                'likesCount'         => count($this->likes->where('is_liked', true)->where('created_by', '!=', $this->userId)),
                'commentsCount'      => count($this->comments->where('is_active', true)->where('is_publish', true)),
                'view_count'         => $view_count,
                'is_partner'         => $this->is_partner ? true : false,
                'is_publish'         => $this->is_publish ? true : false,
            ];
        }

        $data['save_content'] = $saveContent;

        $images = [];
        if ($this->feedable->medias && count($this->feedable->medias) > 0) {
            $images = $this->feedable->medias->map(function ($item) {
                return  Storage::url($item->url);
            });
            $data['images'] = $images;
        } else {
            // $data['excerpt'] = $this->feedable->title;
            // $data['description'] = null;
        }

        $data['dynamicurl'] = $this->getDynamicUrl() ? $this->getDynamicUrl() : "";

        // if(isset($this->with_user) && $this->with_user){
        // $data['users'] = $this->likes && count($this->likes) > 0 ? $this->getLikeUsers($this->likes) : null;
        // }
        return $data;
    }

    private function getLikeUsers($likes)
    {
        $users = collect();

        foreach ($likes as $like) {
            $url = null;
            if (! $like->users['avatarMediable']) {
                $url = '';
            } else {
                $url = Storage::url($like->users['avatarMediable']['media']['url']);
            }

            $users->push(
                [
                    'name' => $like->users['name'],
                    'id' => $like->users['id'],
                    'name' => $like->users['name'],
                    'email' => $like->users['email'],
                    'phone' => $like->users['phone'],
                    'is_active' => $like->users['is_active'],
                    'role_id' => $this->role_id,
                    'created_at' =>  $like->users['created_at'],
                    'user_type' => $like->users['role_id'] ? RoleType::getKey($like->users['role_id']) : '',
                    'profile_image' => $url,
                ]
            );
        }

        return $users;
    }

    private function getDynamicUrl()
    {
        if (!$this->relationLoaded('dynamicurls')) {
            $this->load('dynamicurls');
        }

        if ($this->dynamicurls->count() > 0) {
            return $this->dynamicurls->last()->url;
        }
        return null;
    }
}

