<?php

namespace  App\Helpers;

use App\Enums\CollectionViewType;
use Illuminate\Support\Facades\DB;

class FeedHelper
{
    
    public static function addViews($feeds, $isPaginate = false)
    {
        if ($isPaginate) {
            $feeds->getCollection()->transform(function($feed) {
                $feed->view_count = self::countViews($feed->id);
                return $feed;
            });
        }else{
            $feeds->map(function($feed) {
                $feed->view_count = self::countViews($feed->id);
                return $feed;
            });
        }

        return $feeds;
    }

    public static function countViews($typeId)
    {
        $type = CollectionViewType::Feed;
        $views = DB::connection('partner_mysql')
                    ->table('collection_views')
                    ->where('type', $type )
                    ->where('type_id', $typeId)
                    ->sum('view_count');

        return $views;
    }

}
