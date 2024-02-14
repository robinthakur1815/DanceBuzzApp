<?php

namespace  App\Helpers;

use Illuminate\Support\Facades\Facade;
use Carbon\Carbon;
use App\Comment;
use App\Enums\CollectionType;

class CommonHelper extends Facade
{
    public static function collectionData($collectionData)
    {
        $published_content = json_decode($collectionData->published_content);
        $startDate= "";
        if (isset($published_content->start_date) and $published_content->start_date) {
            $startDate = Carbon::createFromFormat('Y/m/d', $published_content->start_date);
        }

        $startTime  = "";
        $endDate    = "";
        $endTime    = "";
        $type       = $collectionData->collection_type;

        if (isset($published_content->start_time)) {
            $startDateTime = Carbon::parse($published_content->start_time);
            $startTime = $startDateTime->format('h:i A');
        }

        if (isset($published_content->end_date) and $published_content->end_date) {
            $endStampDate = Carbon::createFromFormat('Y/m/d', $published_content->end_date);
            $endDate = $endStampDate->format('d M, Y');
        }

        if (isset($published_content->end_time)) {
            $endDateTime = Carbon::parse($published_content->end_time);
            $endTime = $endDateTime->format('h:i A');
        }

        $collectionTitle = "";
        if ($type == CollectionType::classDeck) {
            $collectionTitle = "Live Class";
        }

        if ($type == CollectionType::classes) {
            $collectionTitle = "Class";
        }

        if ($type == CollectionType::events) {
            $collectionTitle = "Event";

        }

        if ($type == CollectionType::workshops) {
            $collectionTitle = "Workshop";
        }

        $url = self::webUrl($type, $collectionData->slug);

        $collection = new \stdClass();
        $collection->start_date = $startDate;
        $collection->start_time = $startTime;
        $collection->end_date   = $endDate;
        $collection->end_time   = $endTime;
        $collection->collectionTitle   = $collectionTitle;
        $collection->url   = $url;

        $collection->title = isset($collectionData->title) ? $collectionData->title : "";
        return $collection;
    }


    private static function webUrl($collection_type, $slug)
    {
        $url = "";
        if ($collection_type == CollectionType::classes) {
            $url = '/class/'.$slug;
        }

        if ($collection_type == CollectionType::classDeck) {
            $url = '/live-class/'.$slug;
        }


        if ($collection_type == CollectionType::events) {
            $url = '/event/'.$slug;
        }

        if ($collection_type == CollectionType::workshops) {
            $url = '/workshop/'.$slug;
        }

        $web_url = config('app.client_url').$url;

        return $web_url;
    }

    public static function commentID($id){
        $commentIds = [];
        $Childcomments = Comment::where('parent_comment_id', $id)->where('is_active', true)->latest()->pluck('id')->toArray();
        if(count($Childcomments) == 0){
           return $commentIds;
        }
        else{
          //  $Childcomments = Comment::whereIn('parent_comment_id', $Childcomments)->where('is_active', true)->latest()->pluck('id')->toArray();
            
            foreach($Childcomments as $childcomment){
                $commentIds[] = array_merge([$childcomment],self::commentID($childcomment));
                
            }
            return $commentIds;
            
        }
    }
    public static function nestedToSingle(array $array)
    {
        $singleDimArray = [];

        foreach ($array as $item) {

            if (is_array($item)) {
                $singleDimArray = array_merge($singleDimArray, Self::nestedToSingle($item));

            } else {
                $singleDimArray[] = $item;
            }
        }

        return $singleDimArray;
    }
}
