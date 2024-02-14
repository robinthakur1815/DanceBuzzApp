<?php

namespace App\Http\Controllers;

use App\Tag;
use App\User;
use App\Vendor;
use App\TagGroup;
use App\Enums\RoleType;
use App\Enums\UserRole;
use App\Helpers\SlugHelper;
use App\TagCollectionPivot;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Enums\CollectionType;
use App\Http\Resources\TagGroupCollection;

class TagController extends Controller
{
    public function saveTags(Request $request)
    {
        $user = auth()->user();

        if (! $user) {
            $user = User::where('role_id', UserRole::SuperAdmin)->where('is_active', true)->first();
        }

        $slugHelper = new SlugHelper();
        $slug = $slugHelper->slugify($request->name);

        if($user->role_id == RoleType::SuperAdmin && $request->vendor_id){

            $vendor = Vendor::where('id',$request->vendor_id)->first();

            $user   = User::find($vendor->created_by);

        }
/* 
        if($user->role_id == RoleType::Vendor || $user->role_id==RoleType::School){


        } */

        $alreadyExist = Tag::where('slug', $slug)->where('created_by',$user->id)->first();
        if ($alreadyExist && $alreadyExist->id != $request->id) {
            return response(['errors' => ['error' => ['Tag already exist']], 'status' => false, 'message' => ''], 422);
        }

        $attributes = [
            'name' => $request->name,
            'slug' => $slug,
            'updated_by' => $user->id,
        ];
        if ($request->is_featured) {
            $attributes['is_featured'] = $request->is_featured;
        }
        if (isset($request->type)) {
            $attributes['collection_type'] = $request->type;
        }

        // if ($user->role_id == UserRole::Admin || $user->role_id == UserRole::Approver) {
        //     $attributes['is_user_defined'] = false;
        // }
        if ($request->is_user_defined) {
            $attributes['is_user_defined'] = $request->is_user_defined;
        }

        if ($request->id) {
            $tag = Tag::find($request->id);
            $tag->update($attributes);
        } else {
            $attributes['created_by'] = $user->id;
            $tag = Tag::create($attributes);
        }

        return $tag;
    }

    public function deleteTag(Request $request, $id)
    {
        $user = auth()->user();
        $tag = Tag::find($id);
        $tag->collectionPivot()->delete();
        $tag->delete();

        return response(['message' =>  'Tag deleted successfully', 'status' => false], 200);
    }

    public function getAllTags(Request $request)
    {   
        $user = auth()->user(); 
        if($user->role_id == RoleType::SuperAdmin && $request->vendor_id){

            $vendor = Vendor::where('id',$request->vendor_id)->first();
            if($vendor){
                $user   = User::find($vendor->created_by);

            }
        }
        /* if($user->role_id == RoleType::Vendor || RoleType::School){

            $user   = $user = auth()->user(); 

        } */

        $tags = Tag::where('created_by',$user->id)->latest();
        if ($request->maxRows) {
            $tags = $tags->paginate($request->maxRows);
        } else {
            $tags = $tags->get();
        }
        // $tags = Tag::get();
        return $tags;
    }

    public function getPaginateTags(Request $request)
    {   

        $user = auth()->user(); 
        if($user->role_id == RoleType::SuperAdmin && $request->vendor_id){

            $vendor = Vendor::where($request->vendor_id)->first();
            if($vendor){
                $user   = User::find($vendor->created_by);

            }
        }
        $tags = Tag::where('created_by',$user->id)->latest();
        $isMobile = $request->mobile;
        if (! $isMobile) {
            $tags = $tags->with('createdBy', 'parentTag');
        }
        if ($request->search) {
            $tags = $tags->where('name', 'like', "%{$request->search}%");
        }

        if ($isMobile) {
            $types = [CollectionType::events, CollectionType::workshops];
            $tags = $tags->whereIn('collection_type', $types);
        }

        if ($request->maxRows) {
            $tags = $tags->paginate($request->maxRows);
        } else {
            $tags = $tags->get();
        }

        if ($isMobile) {
            $tags->getCollection()->transform(function ($tag) {
                $data = [
                    'id'   => $tag->id,
                    'name' => Str::title($tag->name),
                ];

                return $data;
            });
        }

        return $tags;
    }

    public function saveTagGroup(Request $request)
    {
        $user = auth()->user();
        $slugHelper = new SlugHelper();
        $slug = $slugHelper->slugify($request->name);

        $alreadyExist = TagGroup::where('slug', $slug)->first();
        if ($alreadyExist && $alreadyExist->id != $request->id) {
            return response(['errors' => ['error' => ['Tag group already exist']], 'status' => false, 'message' => ''], 422);
        }

        $attributes = [
            'name' => $request->name,
            'slug' => $slug,
            'updated_by' => $user->id,
        ];

        if (isset($request->collection_type)) {
            $attributes['collection_type'] = $request->collection_type;
        }

        if ($request->id) {
            $group = TagGroup::find($request->id);
            $group->update($attributes);
            $group->save();
        } else {
            $attributes['created_by'] = $user->id;
            $group = TagGroup::create($attributes);
        }
        $group->load('tags');
        $tagIds = [];
        if ($request->tags) {
            $tagIds = array_map(function ($tag) {
                return $tag['id'];
            }, $request->tags);
        }
        $group->tags()->sync($tagIds);

        return $group;
    }

    public function getTagGroupList(Request $request)
    {
        $groups = TagGroup::with('tags')->latest();
        if ($request->search) {
            $groups = $groups->where('name', 'like', "%{$request->search}%");
        }
        if ($request->maxRows) {
            $groups = $groups->paginate($request->maxRows);
        } else {
            $groups = $groups->get();
        }

        return new TagGroupCollection($groups);
    }

    public function deleteTagGroup(Request $request)
    {
        $group = TagGroup::where('id', $request->id)->first();
        // $group->tags()->detach();
        $group->tagPivots()->delete();
        $group->delete();

        return response(['message' =>  'Tag Group deleted successfully', 'status' => false], 200);
    }
}
