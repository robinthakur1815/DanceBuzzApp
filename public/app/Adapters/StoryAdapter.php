<?php

namespace App\Adapters;

use DB;
use App\User;
use App\Story;
use App\Vendor;
use App\Collection;
use App\Enums\RoleType;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use App\Enums\CollectionType;
use App\Enums\StoryType;
use App\Http\Resources\StoryResource;
use Illuminate\Support\Facades\Facade;
use App\Http\Resources\StoryResourceCollection;

final class StoryAdapter extends Facade
{
    public static function cmsStories(Request $request)
    {
        $stories = Story::with('student', 'campaign', 'category', 'subCategory')->latest();
        
        $user = auth()->user();
        if ($user->role_id == RoleType::SuperAdmin and $request->vendor_id ) {
            $vendor = Vendor::find($request->vendor_id);
            $user = User::find($vendor->created_by);
            $stories = $stories->whereHas('campaign', function ($query) use ($user) {
                       $query->where('created_by', $user->id);
                });       
            
        }
        if ($user->role_id == RoleType::Vendor || $user->role_id == RoleType::School) {
           
            $stories = $stories->whereHas('campaign', function ($query) use ($user) {
                       $query->where('created_by', $user->id);
                });       
            
        }
       

        if (isset($request['search']) and $request['search']) {
            $searchText = $request['search'];

            $stories = $stories->where(function ($q) use ($searchText) {
                $q =
                    // Campaign Name Search
                    $q->whereHas('campaign', function ($query) use ($searchText) {
                        $query->where('title', 'like', "%{$searchText}%");
                    })
                    // Category Name Search
                    ->orWhereHas('category', function ($query) use ($searchText) {
                        $query->where('title', 'like', "%{$searchText}%");
                    })
                    // Student Name Search
                    // ->orWhereHas('student', function ($query) use ($searchText) {
                    //     $query->where('name', 'like', "%{$searchText}%");
                    // })
                    // Sub category Name Search
                    ->orWhereHas('subCategory', function ($query) use ($searchText) {
                        $query->where('title', 'like', "%{$searchText}%");
                    });

                return $q;
            });
        }

    /*    if (isset($request['user_id']) and $request['user_id']) {
            $stories = $stories->where('student_user_id', $request['user_id']);
        }
    */  
        if ($user->role_id != UserRole::SuperAdmin && $user->role_id != UserRole::Approver && $user->role_id != RoleType::Vendor && $user->role_id != RoleType::School) {
            $stories = $stories->where('student_user_id', $user->id);
        }

        if (isset($request['is_shoppable']) and $request['is_shoppable']) {
            $stories = $stories->where('is_shoppable', $request['is_shoppable']);
        }


        if (isset($request['category_id']) and $request['category_id']) {
            $stories = $stories->where('category_id', $request['category_id']);
        }

        if (isset($request['sub_category_id']) and $request['sub_category_id']) {
            $stories = $stories->where('sub_category_id', $request['sub_category_id']);
        }

        if (isset($request['campaign_id']) and $request['campaign_id']) {
            $stories = $stories->where('campaign_id', $request['campaign_id']);
        }
        if(isset($request['campaign_stories']) and $request['campaign_stories']){
            if (isset($request['status']) and $request['status']) {
                $stories = $stories->where('status', $request['status']);
            }
        }else{

        if (isset($request['status']) and $request['status']) {
            $stories = $stories->where('status', $request['status']);
        }else{
            $stories = $stories->where('status', '!=', StoryType::Rejected);
         } 
      }
      if (isset($request['isTrashed']) and $request['isTrashed']) {
        $stories = $stories->onlyTrashed();
    }

        if (isset($request['max_rows']) and $request['max_rows']) {
            $stories = $stories->paginate($request['max_rows']);
        } else {
            $stories = $stories->get();
        }

        if (isset($request['no_resource']) and $request['no_resource']) {
            return $stories;
        } else {
            return new StoryResourceCollection($stories);
        }
    }

    public static function cmsStoryShow(Request $request, $id)
    {
        $story = Story::with('student', 'campaign', 'category', 'subCategory')->withTrashed()->find($id);
        $story['isDetails'] = true;

        return new StoryResource($story);
    }

    public static function SingleStudentStories(Request $request)
    {
        $stories = Story::with('student', 'campaign', 'category', 'subCategory')->where('student_user_id', $request->id)->paginate($request->max_rows);
        foreach ($stories as $story) {
            $story['isDetails'] = true;
        }

        return new StoryResourceCollection($stories);
    }

    private function getStories($request, $id = null)
    {
        $stories = Story::latest();

        if (isset($request['user_id']) and $request['user_id']) {
            $stories = $stories->where('student_user_id', $request['user_id']);
        }

        if (isset($request['category_id']) and $request['category_id']) {
            $stories = $stories->where('category_id', $request['category_id']);
        }

        if (isset($request['sub_category_id']) and $request['sub_category_id']) {
            $stories = $stories->where('sub_category_id', $request['sub_category_id']);
        }

        if (isset($request['campaign_id']) and $request['campaign_id']) {
            $stories = $stories->where('campaign_id', $request['campaign_id']);
        }

        if ($id) {
            return $stories->where('id', $id)->first();
        }

        if (isset($request['max_rows']) and $request['max_rows']) {
            $stories = $stories->paginate($request['max_rows']);
        } else {
            $stories = $stories->get();
        }

        return $stories;
    }
}
