<?php

namespace App\Http\Controllers;

use App\Feed;
use App\Collection;
use Illuminate\Http\Request;
use App\Enums\CollectionType;
use App\Adapters\DynamicUrl\DynamicUrlService;

class DynamicUrlController extends Controller
{
    public function createDynamicUrlForFeed(Request $request){
        
        $feedId = $request->feed_id ;

        $feed = Feed::find($feedId);
        if(!$feed){
            return response(['message'=>'Feed Not Found ','status'=>true],422);
        }

        $du = new DynamicUrlService();

        $url = $du->createDynamicUrlForFeed($feedId,$request->refresh);

        return ['url' => $url] ;
    }

    public function createDynamicUrlForCampaign(Request $request){
        
        $campaignId = $request->campaign_id ;

        $campaign = Collection::whereNotNull('published_content')->where('collection_type',CollectionType::campaigns)->find($campaignId);
        if(!$campaign){
            return response(['message'=>'Campaign not Found ','status'=>true],422);
        }

        $du = new DynamicUrlService();

        $url = $du->createDynamicUrlForCampaign($campaignId,$request->refresh);

        return ['url' => $url] ;
    }

    public function createDynamicUrlForYoungExperts(Request $request){
        
        $collectionId = $request->id ;

        $collection = Collection::whereNotNull('published_content')->where('collection_type',CollectionType::caseStudy)->find($collectionId);
        if(!$collection){
            return response(['message'=>'Young Expert not Found ','status'=>true],422);
        }

        $du = new DynamicUrlService();

        $url = $du->createDynamicUrlForYoungExperts($collectionId,$request->refresh);

        return ['url' => $url] ;
    }
}
